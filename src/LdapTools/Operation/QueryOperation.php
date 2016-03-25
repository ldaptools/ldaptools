<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Operation;

use LdapTools\Exception\LdapQueryException;
use LdapTools\Query\OperatorCollection;

/**
 * Represents an operation to query LDAP and return a result set.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class QueryOperation implements LdapOperationInterface
{
    use LdapOperationTrait;

    /**
     * Scope name to LDAP function mappings.
     */
    const SCOPE = [
        'SUBTREE' => 'subtree',
        'ONELEVEL' => 'onelevel',
        'BASE' => 'base',
    ];

    /**
     * @var array
     */
    protected $functionMap = [
        self::SCOPE['SUBTREE'] => 'ldap_search',
        self::SCOPE['ONELEVEL'] => 'ldap_list',
        self::SCOPE['BASE'] => 'ldap_read',
    ];

    /**
     * @var array
     */
    protected $properties = [
        'baseDn' => null,
        'filter' => null,
        'attributes' => [],
        'pageSize' => null,
        'usePaging' => null,
        'scope' => self::SCOPE['SUBTREE'],
    ];

    /**
     * @param null|string|OperatorCollection $filter
     * @param array $attributes
     */
    public function __construct($filter = null, array $attributes = [])
    {
        $this->properties['filter'] = $filter;
        $this->properties['attributes'] = $attributes;
        $this->properties['scope'] = self::SCOPE['SUBTREE'];
    }

    /**
     * Get the base DN for the LDAP query operation.
     *
     * @return string
     */
    public function getBaseDn()
    {
        return $this->properties['baseDn'];
    }

    /**
     * Get th filter for the LDAP query operation.
     *
     * @return string
     */
    public function getFilter()
    {
        return $this->properties['filter'];
    }

    /**
     * Get the page size used for the LDAP query operation.
     *
     * @return int|null
     */
    public function getPageSize()
    {
        return $this->properties['pageSize'];
    }

    /**
     * Get the scope type for a query type operation.
     *
     * @return null|string
     */
    public function getScope()
    {
        return $this->properties['scope'];
    }

    /**
     * Get either: The attributes selected for a query operation. The attributes to be set for an add operation.
     *
     * @return array|null
     */
    public function getAttributes()
    {
        return $this->properties['attributes'];
    }

    /**
     * Get whether or not paging should be used for the query operation.
     *
     * @return bool|null
     */
    public function getUsePaging()
    {
        return $this->properties['usePaging'];
    }

    /**
     * Set the scope of the LDAP query.
     *
     * @param $scope
     * @return $this
     * @throws LdapQueryException
     */
    public function setScope($scope)
    {
        if (!in_array($scope, self::SCOPE)) {
            throw new LdapQueryException(sprintf(
                'Scope type "%s" is invalid. See the QueryOperation::SCOPE[] constant for valid types.', $scope
            ));
        }
        $this->properties['scope'] = $scope;

        return $this;
    }

    /**
     * Set the base DN for the LDAP query operation.
     *
     * @param string $baseDn
     * @return $this
     */
    public function setBaseDn($baseDn)
    {
        $this->properties['baseDn'] = $baseDn;

        return $this;
    }

    /**
     * Set the LDAP filter used by the operation.
     *
     * @param string|OperatorCollection $filter
     * @return $this
     */
    public function setFilter($filter)
    {
        $this->properties['filter'] = $filter;

        return $this;
    }

    /**
     * Set the page size used by the LDAP query operation.
     *
     * @param int $pageSize
     * @return $this
     */
    public function setPageSize($pageSize)
    {
        $this->properties['pageSize'] = $pageSize;

        return $this;
    }

    /**
     * Set the attributes selected or added to/from LDAP (add or select operation).
     *
     * @param array $attributes
     * @return $this;
     */
    public function setAttributes(array $attributes)
    {
        $this->properties['attributes'] = $attributes;

        return $this;
    }

    /**
     * Set whether or not paging should be used for the query operation.
     *
     * @param bool $paging
     * @return $this
     */
    public function setUsePaging($paging)
    {
        $this->properties['usePaging'] = $paging;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments()
    {
        return [
            $this->properties['baseDn'],
            $this->getLdapFilter(),
            $this->properties['attributes'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getLdapFunction()
    {
        return $this->functionMap[$this->properties['scope']];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Query';
    }

    /**
     * {@inheritdoc}
     */
    public function getLogArray()
    {
        return $this->mergeLogDefaults([
            'Filter' => $this->getLdapFilter(),
            'Base DN' => $this->properties['baseDn'],
            'Attributes' => implode(',', $this->properties['attributes']),
            'Scope' => $this->properties['scope'],
            'Use Paging' => var_export($this->properties['usePaging'], true),
            'Page Size' => $this->properties['pageSize'],
        ]);
    }

    /**
     * @return string
     */
    protected function getLdapFilter()
    {
        $filter = $this->properties['filter'];
        if ($filter instanceof OperatorCollection) {
            $filter = $filter->toLdapFilter();
        }
        
        return $filter;
    }
}
