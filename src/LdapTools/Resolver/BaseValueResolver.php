<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Resolver;

use LdapTools\AttributeConverter\AttributeConverterInterface;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Factory\AttributeConverterFactory;
use LdapTools\Schema\LdapObjectSchema;

/**
 * The base value resolver for sending data back to LDAP.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
abstract class BaseValueResolver
{
    /**
     * Any converter options defined by the schema object.
     *
     * @var array
     */
    protected $options = [];

    /**
     * @var LdapConnectionInterface
     */
    protected $connection;

    /**
     * @var LdapObjectSchema
     */
    protected $schema;

    /**
     * @var string|null The full DN of the LDAP entry for this context.
     */
    protected $dn;

    /**
     * @var int The LDAP operation type that the converter is working against.
     */
    protected $type;

    /**
     * @var array Attribute names that were merged into a single attribute.
     */
    protected $aggregated = [];

    /**
     * Iterate through the aggregates for a specific LDAP value to build up the value it should actually be.
     *
     * @param array $toAggregate
     * @param mixed $values
     * @param AttributeConverterInterface $converter
     * @return array|string The final value after all possible values have been iterated through.
     */
    abstract protected function iterateAggregates(array $toAggregate, $values, AttributeConverterInterface $converter);

    /**
     * @param LdapObjectSchema $schema
     * @param int $type
     */
    public function __construct(LdapObjectSchema $schema, $type)
    {
        $this->type = $type;
        $this->schema = $schema;
        $this->options = $schema->getConverterOptions();
    }

    /**
     * Set the LDAP connection for the current context.
     *
     * @param LdapConnectionInterface $connection
     */
    public function setLdapConnection(LdapConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Set the DN for the entry whose values are being converted.
     *
     * @param string $dn
     */
    public function setDn($dn)
    {
        $this->dn = $dn;
    }

    /**
     * Get the values for an attribute after applying any converters.
     *
     * @param mixed $values
     * @param string $attribute
     * @param string $direction
     * @param AttributeConverterInterface|null $aggregate
     * @return array
     */
    protected function getConvertedValues($values, $attribute, $direction, $aggregate = null)
    {
        $values = is_array($values) ? $values : [$values];
        $converter = null;

        foreach ($values as $index => $value) {
            $converter = is_null($aggregate) ? $this->getConverterWithOptions($this->schema->getConverter($attribute)) : $aggregate;
            $converter->setAttribute($attribute);
            $values[$index] = $converter->$direction($value);
        }
        if (!is_null($converter) && $converter->getShouldAggregateValues() && is_null($aggregate) && $direction == 'toLdap') {
            $values = $this->convertAggregateValues($attribute, $values, $converter);
        }

        return $values;
    }

    /**
     * Loops through all the attributes that are to be aggregated into a single attribute for a specific converter.
     *
     * @param string $attribute
     * @param array $values
     * @param AttributeConverterInterface $converter
     * @return array
     */
    protected function convertAggregateValues($attribute, array $values, AttributeConverterInterface $converter)
    {
        $this->aggregated[] = $attribute;
        $converterName = $this->schema->getConverter($attribute);
        $toAggregate = array_keys($this->schema->getConverterMap(), $converterName);

        $values = (count($values) == 1) ? reset($values) : $values;
        $converter->setLastValue($values);
        $values = $this->iterateAggregates($toAggregate, $values, $converter);

        return is_array($values) ? $values : [$values];
    }

    /**
     * Get an instance of a converter with its options set.
     *
     * @param string $converterName The name of the converter from the schema.
     * @return AttributeConverterInterface
     */
    protected function getConverterWithOptions($converterName)
    {
        $converter = AttributeConverterFactory::get($converterName);

        if (isset($this->options[$converterName])) {
            $converter->setOptions($this->options[$converterName]);
        }
        if ($this->connection) {
            $converter->setLdapConnection($this->connection);
        }
        if ($this->dn !== null) {
            $converter->setDn($this->dn);
        }
        $converter->setOperationType($this->type);

        return $converter;
    }
}
