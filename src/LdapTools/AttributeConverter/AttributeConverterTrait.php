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
     * Sets the current LdapConnection for access by the converter.
     *
     * @param LdapConnectionInterface $connection
     */
    public function setLdapConnection(LdapConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getLdapConnection()
    {
        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function setAttributeName($attribute)
    {
        return $this->attribute = $attribute;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeName()
    {
        return $this->attribute;
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function setOperationType($type)
    {
        $this->type = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getOperationType()
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function setDn($dn)
    {
        $this->dn = $dn;
    }

    /**
     * {@inheritdoc}
     */
    public function getDn()
    {
        return $this->dn;
    }

    /**
     * {@inheritdoc}
     */
    public function setAttribute($attribute)
    {
        $this->attribute = $attribute;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastValue()
    {
        return $this->lastValue;
    }

    /**
     * {@inheritdoc}
     */
    public function setLastValue($value)
    {
        $this->lastValue = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function setShouldAggregateValues($aggregateValues)
    {
        $this->aggregateValues = (bool) $aggregateValues;
    }

    /**
     * {@inheritdoc}
     */
    public function getShouldAggregateValues()
    {
        return $this->aggregateValues;
    }
}
