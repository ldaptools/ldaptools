<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\AttributeConverter;

use LdapTools\BatchModify\Batch;
use LdapTools\Connection\LdapConnectionInterface;

/**
 * Common Attribute Converter methods and properties.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
trait AttributeConverterTrait
{
    /**
     * @var LdapConnectionInterface
     */
    protected $connection;

    /**
     * @var string|null
     */
    protected $dn;

    /**
     * @var array Any options that may be recognized by the converter.
     */
    protected $options = [];

    /**
     * @var int The operation type for this conversion process.
     */
    protected $type = AttributeConverterInterface::TYPE_SEARCH_FROM;

    /**
     * @var string|null The attribute name for the current conversion.
     */
    protected $attribute;

    /**
     * @var mixed When the converter aggregates multiple attributes into a single one, this is the last value set.
     */
    protected $lastValue;

    /**
     * @var bool Whether to aggregate multiple attributes assigned to this converter that all map to one attribute.
     */
    protected $aggregateValues;

    /**
     * @var Batch|null The batch object for the conversion.
     */
    protected $batch;

    /**
     * @var bool Whether the values should be passed one by one to the converter or as an array of the values.
     */
    protected $isMultiValuedConverter = false;

    /**
     * Sets the current LdapConnection for access by the converter.
     *
     * @param LdapConnectionInterface|null $connection
     * @return $this
     */
    public function setLdapConnection(LdapConnectionInterface $connection = null)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @return LdapConnectionInterface|null
     */
    public function getLdapConnection()
    {
        return $this->connection;
    }

    /**
     * @param string $attribute
     * @return $this
     */
    public function setAttributeName($attribute)
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param int $type
     * @return $this
     */
    public function setOperationType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return int
     */
    public function getOperationType()
    {
        return $this->type;
    }

    /**
     * @param string $dn
     * @return $this
     */
    public function setDn($dn)
    {
        $this->dn = $dn;

        return $this;
    }

    /**
     * @return string
     */
    public function getDn()
    {
        return $this->dn;
    }

    /**
     * @param string
     * @return $this
     */
    public function setAttribute($attribute)
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * @return string
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * @return mixed
     */
    public function getLastValue()
    {
        return $this->lastValue;
    }

    /**
     * @param mixed
     * @return $this
     */
    public function setLastValue($value)
    {
        $this->lastValue = $value;

        return $this;
    }

    /**
     * @param bool
     */
    public function setShouldAggregateValues($aggregateValues)
    {
        $this->aggregateValues = (bool) $aggregateValues;
    }

    /**
     * @return bool
     */
    public function getShouldAggregateValues()
    {
        return $this->aggregateValues;
    }

    /**
     * @return Batch
     */
    public function getBatch()
    {
        return $this->batch;
    }

    /**
     * @param Batch $batch
     * @return $this
     */
    public function setBatch(Batch $batch)
    {
        $this->batch = $batch;

        return $this;
    }

    /**
     * @param Batch $batch
     * @return bool
     */
    public function isBatchSupported(Batch $batch)
    {
        return (bool) $batch;
    }

    /**
     * @param bool
     */
    public function setIsMultiValuedConverter($isMultiValuedConverter)
    {
        $this->isMultiValuedConverter = (bool) $isMultiValuedConverter;
    }

    /**
     * @return bool
     */
    public function getIsMultiValuedConverter()
    {
        return $this->isMultiValuedConverter;
    }
}
