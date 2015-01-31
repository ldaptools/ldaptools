<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools;

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
        return $this->buildQuery()->where($attributes)->getLdapQuery()->execute();
    }

    /**
     * @param array $attributes
     * @return mixed
     */
    public function findOneBy(array $attributes)
    {
        $results = $this->buildQuery()->where($attributes)->getLdapQuery()->execute();

        return reset($results) ?: [];
    }

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

        if (!$this->schema->hasAttribute($attribute)) {
            throw new \RuntimeException(
                sprintf('To call "%s" you must define the attribute "%s" in your schema.', $method, $attribute)
            );
        }

        if (1 == count($arguments)) {
            return $this->$method([ $attribute => $arguments[0] ]);
        } else {
            return $this->$method(array_merge([ $attribute => array_shift($arguments)], $arguments));
        }
    }

    public function buildQuery()
    {
        $lqb = new LdapQueryBuilder($this->ldap);

        if (!empty($this->attributes)) {
            $lqb->select($this->attributes);
        }

        return $lqb->from($this->schema);
    }

    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }
}
