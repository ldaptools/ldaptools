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

use LdapTools\AttributeConverter\AttributeConverterInterface;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Factory\AttributeConverterFactory;
use LdapTools\Schema\LdapObjectSchema;

/**
 * Converts attribute values to the expected type for the context.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class AttributeValueResolver
{
    /**
     * The LDAP entry in [ 'attribute' => 'value' ] form.
     *
     * @var array
     */
    protected $entry = [];

    /**
     * @var LdapObjectSchema
     */
    protected $schema;

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
     * @var string|null The full DN of the LDAP entry for this context.
     */
    protected $dn;

    /**
     * @var array If the attribute was converted using an aggregate it will be placed here so it can be skipped.
     */
    protected $converted = [];

    /**
     * @var array
     */
    protected $aggregated = [];

    /**
     * @var bool Whether or not the conversion process is dealing with a batch modification structure.
     */
    protected $isBatch = false;

    /**
     * @var int The current batch index number being processed.
     */
    protected $currentBatchIndex = 0;

    /**
     * @param LdapObjectSchema $schema
     * @param array $entry The [ attribute => value ] entries.
     * @param int $type The LDAP operation type. See AttributeConverterInterface::TYPE_*.
     */
    public function __construct(LdapObjectSchema $schema, array $entry, $type)
    {
        $this->schema = $schema;
        $this->entry = $entry;
        $this->options = $schema->getConverterOptions();
        $this->type = $type;
    }

    /**
     * Convert values from LDAP.
     *
     * @return array
     */
    public function fromLdap()
    {
        return $this->convert($this->entry, false);
    }

    /**
     * Convert values to LDAP.
     *
     * @param bool $isBatch Whether or not this is a batch modification operation.
     * @return array
     */
    public function toLdap($isBatch = false)
    {
        $this->isBatch = $isBatch;

        return $isBatch ? $this->convertBatch($this->entry) : $this->convert($this->entry);
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
     * Converts all the values within a batch specification array.
     *
     * @param array $batches
     * @return array
     */
    protected function convertBatch(array $batches)
    {
        foreach ($batches as $index => $batch) {
            if (!$this->batchCanConvert($batch, $index)) {
                continue;
            }
            $this->currentBatchIndex = $index;
            $batches[$index]['values'] = $this->getConvertedValues($batch['values'], $batch['attrib'], 'toLdap');

            if (in_array($batch['attrib'], $this->aggregated)) {
                $batches[$index]['attrib'] = $this->schema->getAttributeToLdap($batch['attrib']);
            }
        }

        return $this->removeAggregatedValues($batches);
    }

    /**
     * Perform the attribute conversion process.
     *
     * @param array $attributes
     * @param bool $toLdap
     * @return array
     */
    protected function convert(array $attributes, $toLdap = true)
    {
        $direction = $toLdap ? 'toLdap' : 'fromLdap';

        foreach ($attributes as $attribute => $values) {
            if (!$this->schema->hasConverter($attribute) || isset($this->converted[$attribute])) {
                continue;
            }
            $values = $this->getConvertedValues($values, $attribute, $direction);
            if (in_array($attribute, $this->aggregated)) {
                $attribute = $this->schema->getAttributeToLdap($attribute);
            }
            $attributes[$attribute] = (count($values) == 1) ? reset($values) : $values;
        }

        return $this->removeAggregatedValues($attributes);
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
        if (!is_null($converter) && $converter->getShouldAggregateValues() && is_null($aggregate)) {
            $values = $this->convertAggregateValues($attribute, $direction, $values, $converter);
        }

        return $values;
    }

    /**
     * Get an instance of a converter with its options set.
     *
     * @param string $converterName The name of the converter from the schema.
     * @return \LdapTools\AttributeConverter\AttributeConverterInterface
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
        if ($this->dn) {
            $converter->setDn($this->dn);
        }
        $converter->setOperationType($this->type);

        return $converter;
    }

    /**
     * Loops through all the attributes that are to be aggregated into a single attribute for a specific converter.
     *
     * @param string $attribute
     * @param string $direction
     * @param array $values
     * @param AttributeConverterInterface $converter
     * @return array
     */
    protected function convertAggregateValues($attribute, $direction, array $values, AttributeConverterInterface $converter)
    {
        $this->aggregated[] = $attribute;
        $converterName = $this->schema->getConverter($attribute);
        $toAggregate = array_keys($this->schema->getConverterMap(), $converterName);

        $values = (count($values) == 1) ? reset($values) : $values;
        $converter->setLastValue($values);

        if ($this->isBatch) {
            $values = $this->iterateBatchAggregates($toAggregate, $values, $converter);
        } else {
            $values = $this->iterateAggregates($toAggregate, $values, $attribute, $direction, $converter);
        }

        return is_array($values) ? $values : [$values];
    }

    /**
     * Cleans up the entry/batch array by removing any values that were aggregated into one.
     *
     * @param array $entry
     * @return array
     */
    protected function removeAggregatedValues(array $entry)
    {
        foreach ($this->converted as $value) {
            if (isset($entry[$value])) {
                unset($entry[$value]);
            }
        }

        return $entry;
    }

    /**
     * Determine if a specific batch is correctly formatted and needs conversion.
     *
     * @param array $batch
     * @param int $index
     * @return bool
     */
    protected function batchCanConvert(array $batch, $index)
    {
        if (!isset($batch['attrib']) || (!$this->schema->hasConverter($batch['attrib']) || in_array($index, $this->converted))) {
            return false;
        }

        return (isset($batch['modtype']) && ($batch['modtype'] != LDAP_MODIFY_BATCH_REMOVE_ALL) && isset($batch['values']));
    }

    /**
     * Iterate through the aggregates for a specific LDAP value to build up the value it should actually be.
     *
     * @param array $toAggregate
     * @param mixed $values
     * @param string $attribute
     * @param string $direction
     * @param AttributeConverterInterface $converter
     * @return array|string The final value after all possible values have been iterated through.
     */
    protected function iterateAggregates(array $toAggregate, $values, $attribute, $direction, AttributeConverterInterface $converter)
    {
        foreach ($toAggregate as $aggregate) {
            if (!isset($this->entry[$aggregate]) || ($aggregate == $attribute)) {
                continue;
            }
            $values = $this->getConvertedValues($this->entry[$aggregate], $aggregate, $direction, $converter);
            $converter->setLastValue($values);
            $this->converted[] = $aggregate;
        }

        return $values;
    }

    /**
     * Iterate through the aggregates on a batch modification for a specific LDAP value to build up the value it should
     * actually be.
     *
     * @param array $toAggregate The array of attribute names that make up the value for the one attribute.
     * @param mixed $values The values from the first conversion process
     * @param AttributeConverterInterface $converter
     * @return array|string The final value after all possible values have been iterated through.
     */
    protected function iterateBatchAggregates($toAggregate, $values, AttributeConverterInterface $converter)
    {
        // Make sure the validate the current batch as well.
        $this->validateBatchAggregate($this->entry[$this->currentBatchIndex]);
        $batches = $this->getBatchesForAttributes($toAggregate);

        foreach ($batches as $index => $batch) {
            $this->validateBatchAggregate($batch);
            $values = $this->getConvertedValues($batch['values'], $batch['attrib'], 'toLdap', $converter);
            $converter->setLastValue($values);
            $this->converted[] = $index;
        }

        return $values;
    }

    /**
     * Given an array of attribute names, get all of the batches they have with their respective indexes
     *
     * @param array $attributes
     * @return array
     */
    protected function getBatchesForAttributes(array $attributes)
    {
        $batches = [];

        foreach ($this->entry as $index => $batch) {
            // Case insensitive lookup for the batch attribute, carry on to the next if not found...
            if (!(isset($batch['attrib']) && in_array(strtolower($batch['attrib']), array_map('strtolower', $attributes)))) {
                continue;
            }
            // Only add it if it isn't the current batch being processed. It's possible that 'set' was called multiple
            // times, even though that probably wouldn't make much sense...
            if ($this->currentBatchIndex != $index) {
                $batches[$index] = $batch;
            }
        }

        return $batches;
    }

    /**
     * When aggregating a set of values they need to be modified with 'set'. The other methods ('reset', 'add', or
     * 'remove') are not valid. Additionally, all batch modification array keys should be present.
     *
     * @param array $batch
     */
    protected function validateBatchAggregate(array $batch)
    {
        if (!isset($batch['modtype']) || ($batch['modtype'] != LDAP_MODIFY_BATCH_REPLACE)) {
            $attribute = isset($batch['attrib']) ? $batch['attrib'] : '';
            throw new \LogicException(sprintf(
                'Unable to modify "%s". You can only use the "set" method to modify this attribute.', $attribute
            ));
        }
    }
}
