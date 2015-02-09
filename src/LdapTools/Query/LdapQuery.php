<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Query;

use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Factory\HydratorFactory;
use LdapTools\Schema\LdapObjectSchema;

/**
 * Executes and hydrates a LDAP query.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapQuery
{
    /**
     * A subtree scope queries the complete directory from the base dn.
     */
    const SCOPE_SUBTREE = 'subtree';

    /**
     * A single level scope queries the directory one level from the base dn.
     */
    const SCOPE_ONELEVEL = 'onelevel';

    /**
     * A base scope reads the entry from the base dn.
     */
    const SCOPE_BASE = 'base';

    /**
     * @var string The scope level for the search.
     */
    protected $scope = self::SCOPE_SUBTREE;

    /**
     * @var LdapConnectionInterface
     */
    protected $ldap;

    /**
     * @var HydratorFactory
     */
    protected $hydratorFactory;

    /**
     * @var LdapObjectSchema[]
     */
    protected $schemas = [];

    /**
     * @var array The LDAP attribute names to retrieve as passed to this query.
     */
    protected $attributes = [];

    /**
     * @var null|string The BaseDN search scope.
     */
    protected $baseDn = null;

    /**
     * @var null|int The paging size to use.
     */
    protected $pageSize = null;

    /**
     * @var string The LDAP filter.
     */
    protected $ldapFilter = '';

    /**
     * @param LdapConnectionInterface $ldap
     */
    public function __construct(LdapConnectionInterface $ldap)
    {
        $this->ldap = $ldap;
        $this->hydratorFactory = new HydratorFactory();
    }

    /**
     * Execute a query based on the set parameters. Optionally choose a mode to hydrate the results in.
     *
     * @param string $hydratorType A hyrdrator type constant from the factory.
     * @return mixed
     */
    public function execute($hydratorType = HydratorFactory::TO_ARRAY)
    {
        $hydrator = $this->hydratorFactory->get($hydratorType);
        $attributes = empty($this->schemas) ? $this->attributes : $this->getAttributesToLdap($this->attributes);

        if (!empty($this->schemas)) {
            $hydrator->setSelectedAttributes($this->attributes);
            $hydrator->setLdapObjectSchemas(...$this->schemas);
        }

        return $hydrator->hydrateAllFromLdap($this->ldap->search(
            $this->ldapFilter,
            $attributes,
            $this->baseDn,
            $this->scope,
            $this->pageSize
        ));
    }

    /**
     * Set the LDAP filter.
     *
     * @param string $ldapFilter
     * @return $this
     */
    public function setLdapFilter($ldapFilter)
    {
        $this->ldapFilter = $ldapFilter;

        return $this;
    }

    /**
     * The LDAP filter.
     *
     * @return string
     */
    public function getLdapFilter()
    {
        return $this->ldapFilter;
    }

    /**
     * Set the BaseDN search scope.
     *
     * @param string $baseDn
     * @return $this
     */
    public function setBaseDn($baseDn)
    {
        $this->baseDn = $baseDn;

        return $this;
    }

    /**
     * The BaseDN search scope.
     *
     * @return null|string
     */
    public function getBaseDn()
    {
        return $this->baseDn;
    }

    /**
     * Set the paging size.
     *
     * @param int $pageSize
     * @return $this
     */
    public function setPageSize($pageSize)
    {
        $this->pageSize = $pageSize;

        return $this;
    }

    /**
     * The paging size.
     *
     * @return int|null
     */
    public function getPageSize()
    {
        return $this->pageSize;
    }

    /**
     * Set the LDAP attributes to get.
     *
     * @param array $attributes
     * @return $this
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * The LDAP attributes to get.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set the scope for this query.
     *
     * @param string $scope One of the SCOPE_* constants.
     * @return $this
     */
    public function setScope($scope)
    {
        if (!defined('self::'.strtoupper('SCOPE_'.$scope))) {
            throw new \InvalidArgumentException(sprintf('The scope type "%s" is invalid.', $scope));
        }

        $this->scope = $scope;

        return $this;
    }

    /**
     * Get the current scope for this query.
     *
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * Set the LDAP schema objects to be used for the results.
     *
     * @param LdapObjectSchema ...$schemas
     * @return $this
     */
    public function setLdapObjectSchemas(LdapObjectSchema ...$schemas)
    {
        $this->schemas = $schemas;

        return $this;
    }

    /**
     * Get the LdapObjectSchemas added to this query.
     *
     * @return LdapObjectSchema[] LdapObjectSchemas
     */
    public function getLdapObjectSchemas()
    {
        return $this->schemas;
    }
    /**
     * If there are schemas present, then translate selected attributes to retrieve to their LDAP names.
     *
     * @param array $attributes
     * @return array
     */
    protected function getAttributesToLdap(array $attributes)
    {
        $schema = $this->schemas[0];
        $newAttributes = [];

        foreach ($attributes as $attribute) {
            $newAttributes[] = $schema->getAttributeToLdap($attribute);
        }

        return $newAttributes;
    }
}
