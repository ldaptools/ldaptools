<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Object;

use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Query\LdapQueryBuilder;
use LdapTools\Schema\LdapObjectSchema;

/**
 * Provides easy access to query a LDAP object type by specific attributes.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapObjectRepository
{
    /**
     * @var string An explicitly set hydration mode.
     */
    protected $hydrationMode;

    /**
     * @var array The default attributes to return
     */
    protected $attributes = [];

    /**
     * @var LdapObjectSchema
     */
    protected $schema;

    /**
     * @var LdapConnectionInterface
     */
    protected $ldap;

    /**
     * @param LdapObjectSchema $schema
     * @param LdapConnectionInterface $ldap
     */
    public function __construct(LdapObjectSchema $schema, LdapConnectionInterface $ldap)
    {
        $this->schema = $schema;
        $this->ldap = $ldap;
    }

    /**
     * @param array $attributes
     * @return mixed
     */
    public function findBy(array $attributes)
    {
        $query = $this->buildLdapQuery()->where($attributes)->getLdapQuery();

        return  $this->hydrationMode ? $query->execute($this->hydrationMode) : $query->execute();
    }

    /**
     * @param array $attributes
     * @return mixed
     */
    public function findOneBy(array $attributes)
    {
        $query = $this->buildLdapQuery()->where($attributes)->getLdapQuery();

        return  $this->hydrationMode ? $query->getSingleResult($this->hydrationMode) : $query->getSingleResult();
    }
    
    /**
     * @return mixed
     */
    public function findAll()
    {
        $query = $this->buildLdapQuery()->getLdapQuery();

        return  $this->hydrationMode ? $query->execute($this->hydrationMode) : $query->execute();
    }

    /**
     * Determines which method to actually call.
     *
     * @param string $method
     * @param mixed $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        if (!preg_match('/^(findOneBy|findBy)(.*)$/', $method, $matches)) {
            throw new \RuntimeException(
                sprintf('The method name should begin with "findOneBy" or "findBy". "%s" is unknown.', $method)
            );
        }
        if (empty($arguments)) {
            throw new \RuntimeException(
                sprintf('The method name should begin with "findOneBy" or "findBy". "%s" is unknown.', $method)
            );
        }
        $method = $matches[1];
        $attribute = lcfirst($matches[2]);

        if (1 == count($arguments)) {
            return $this->$method([ $attribute => $arguments[0] ]);
        } else {
            return $this->$method(array_merge([ $attribute => array_shift($arguments)], $arguments));
        }
    }

    /**
     * Get the LdapQueryBuilder with the defaults for this repository type.
     *
     * @return LdapQueryBuilder
     */
    public function buildLdapQuery()
    {
        $lqb = new LdapQueryBuilder($this->ldap);

        if (!empty($this->attributes)) {
            $lqb->select($this->attributes);
        }

        return $lqb->from($this->schema);
    }

    /**
     * Set the default attributes to select.
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
     * Get the default attributes that will be selected.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set the hydration mode to use for the results.
     *
     * @param string $hydrationMode
     * @return $this
     */
    public function setHydrationMode($hydrationMode)
    {
        $this->hydrationMode = $hydrationMode;

        return $this;
    }

    /**
     * Get the hydration mode used for the results.
     *
     * @return string
     */
    public function getHydrationMode()
    {
        return $this->hydrationMode;
    }
}
