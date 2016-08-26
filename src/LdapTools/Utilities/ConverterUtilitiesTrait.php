<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Utilities;

use LdapTools\AttributeConverter\AttributeConverterInterface;
use LdapTools\BatchModify\Batch;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Exception\AttributeConverterException;
use LdapTools\Exception\EmptyResultException;
use LdapTools\Query\LdapQueryBuilder;

/**
 * Intended to be used with attribute converters that utilize options and current attributes to do some of their work.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
trait ConverterUtilitiesTrait
{
    /**
     * @return string
     */
    abstract public function getAttribute();

    /**
     * @return Batch|null
     */
    abstract public function getBatch();

    /**
     * @return string|null
     */
    abstract public function getDn();

    /**
     * @return mixed
     */
    abstract public function getLastValue();

    /**
     * @param mixed
     */
    abstract public function setLastValue($value);

    /**
     * @return int
     */
    abstract public function getOperationType();

    /**
     * @return LdapConnectionInterface|null
     */
    abstract public function getLdapConnection();

    /**
     * If the current attribute does not exist in the array, then throw an error.
     *
     * @param array $options
     * @throws AttributeConverterException
     */
    protected function validateCurrentAttribute(array $options)
    {
        if (!array_key_exists(MBString::strtolower($this->getAttribute()), MBString::array_change_key_case($options))) {
            throw new AttributeConverterException(
                sprintf('You must first define "%s" in the options for this converter.', $this->getAttribute())
            );
        }
    }

    /**
     * Get the value of an array key in a case-insensitive way.
     *
     * @param array $options
     * @param string $key
     */
    protected function getArrayValue(array $options, $key)
    {
        return MBString::array_change_key_case($options)[MBString::strtolower($key)];
    }

    /**
     * This can be called to retrieve the current value of an attribute from LDAP.
     *
     * @param string $attribute The attribute name to query for a value from the converter context
     * @return array|string|null
     * @throws AttributeConverterException
     */
    protected function getCurrentLdapAttributeValue($attribute)
    {
        if (!$this->getDn() || !$this->getLdapConnection()) {
            throw new AttributeConverterException(sprintf('Unable to query for the current "%s" attribute.', $attribute));
        }

        $query = new LdapQueryBuilder($this->getLdapConnection());
        try {
            return $query->select($attribute)
                ->where($query->filter()->present('objectClass'))
                ->setBaseDn($this->getDn())
                ->setScopeBase()
                ->getLdapQuery()
                ->getSingleScalarOrNullResult();
        } catch (EmptyResultException $e) {
            throw new AttributeConverterException(sprintf('Unable to find LDAP object: %s', $this->getDn()));
        }
    }

    /**
     * Specify an attribute to query to set as the last value. If that is not found, have it set the value specified by
     * whatever you pass to $default.
     *
     * @param string $attribute
     * @param mixed $default
     */
    protected function setDefaultLastValue($attribute, $default)
    {
        if (empty($this->getLastValue()) && $this->getOperationType() == AttributeConverterInterface::TYPE_MODIFY) {
            $original = $this->getCurrentLdapAttributeValue($attribute);
            $this->setLastValue(is_null($original) ? $default : $original);
        } elseif (empty($this->getLastValue()) && $this->getOperationType() == AttributeConverterInterface::TYPE_CREATE) {
            $this->setLastValue($default);
        }
    }

    /**
     * Modifies a multivalued attribute array based off the original values, the new values, and the modification type.
     *
     * @param array $values
     * @param array $newValues
     * @return array
     */
    protected function modifyMultivaluedAttribute(array $values, array $newValues)
    {
        if ($this->getOperationType() == AttributeConverterInterface::TYPE_CREATE || ($this->getBatch() && $this->getBatch()->isTypeAdd())) {
            $values = array_merge($values, $newValues);
        } elseif ($this->getBatch() && $this->getBatch()->isTypeReplace()) {
            $values = $newValues;
        } elseif ($this->getBatch() && $this->getBatch()->isTypeRemove()) {
            $values = array_diff($values, $newValues);
        }

        return $values;
    }
}
