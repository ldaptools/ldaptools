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
use LdapTools\AttributeConverter\OperationGeneratorInterface;
use LdapTools\BatchModify\BatchCollection;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Factory\AttributeConverterFactory;
use LdapTools\Operation\LdapOperationInterface;
use LdapTools\Query\OperatorCollection;
use LdapTools\Schema\LdapObjectSchema;
use LdapTools\Utilities\LdapUtilities;

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
     * @var LdapOperationInterface|null
     */
    protected $operation;

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
     * @var array Attribute names that should be removed as the result of an operation generator.
     */
    protected $remove = [];

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
    public function __construct(LdapObjectSchema $schema = null, $type)
    {
        $this->type = $type;
        $this->schema = $schema;
        if ($schema) {
            $this->options = $schema->getConverterOptions();
        }
    }

    /**
     * Set the LDAP operation being executed.
     *
     * @param LdapOperationInterface|null $operation
     */
    public function setOperation(LdapOperationInterface $operation = null)
    {
        $this->operation = $operation;
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
     * Factory method for instantiation.
     *
     * @param LdapObjectSchema|null $schema
     * @param BatchCollection|OperatorCollection|array $values
     * @param int $type
     * @return AttributeValueResolver|BatchValueResolver
     */
    public static function getInstance(LdapObjectSchema $schema = null, $values, $type)
    {
        $instance = AttributeValueResolver::class;
        
        if ($values instanceof BatchCollection) {
            $instance = BatchValueResolver::class;
        } elseif ($values instanceof OperatorCollection) {
            $instance = OperatorValueResolver::class;
        }
        
        return new $instance($schema, $values, $type);
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
        $converter = is_null($aggregate) ? $this->getConverterWithOptions($this->schema->getConverter($attribute)) : $aggregate;

        if (is_null($aggregate) && $converter->getShouldAggregateValues() && $direction == 'toLdap') {
            $values = $this->convertAggregateValues($attribute, $values, $converter);
        } else {
            $values = $this->doConvertValues($attribute, $values, $direction, $aggregate);
        }
        if ($converter instanceof OperationGeneratorInterface && $converter->getRemoveOriginalValue()) {
            $this->remove[] = $attribute;
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

        /**
         * Only aggregate values going back the same LDAP attribute, as it's possible for the a converter to have many
         * different attributes assigned to it.
         *
         * @todo Probably a better way to do this...
         */
        $aggregateToLdap = $this->schema->getAttributeToLdap($attribute);
        foreach ($toAggregate as $i => $aggregate) {
            if ($this->schema->getAttributeToLdap($aggregate) !== $aggregateToLdap) {
                unset($toAggregate[$i]);
            }
        }
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
        if ($converter instanceof OperationGeneratorInterface) {
            $converter->setOperation($this->operation);
        }
        $converter->setOperationType($this->type);

        return $converter;
    }

    /**
     * Convert a set of values for an attribute.
     *
     * @param string $attribute
     * @param array $values
     * @param string $direction
     * @param AttributeConverterInterface|null $converter
     * @return mixed
     */
    protected function doConvertValues($attribute, array $values, $direction, AttributeConverterInterface $converter = null)
    {
        $converter = is_null($converter) ? $this->getConverterWithOptions($this->schema->getConverter($attribute)) : $converter;
        $converter->setAttribute($attribute);

        if ($converter->getIsMultiValuedConverter()) {
            $values = $converter->$direction($values);
        } else {
            foreach ($values as $index => $value) {
                $values[$index] = $converter->$direction($value);
            }
        }

        return $values;
    }

    /**
     * Encodes any values with the needed type for LDAP.
     *
     * @param array|string $values
     * @return array
     */
    protected function encodeValues($values)
    {
        if (is_null($this->connection) || $this->type == AttributeConverterInterface::TYPE_SEARCH_FROM) {
            return $values;
        }
        $encoded = is_array($values) ? $values : [$values];

        foreach ($encoded as $index => $value) {
            if (is_string($value)) {
                $encoded[$index] = LdapUtilities::encode($value, $this->connection->getConfig()->getEncoding());
            }
        }

        // This is to pass it back the same way it was received. ldap_modify_batch is picky about values being an array.
        return is_array($values) ? $encoded : reset($encoded);
    }
}
