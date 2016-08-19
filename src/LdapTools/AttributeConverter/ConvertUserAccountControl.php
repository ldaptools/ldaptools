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
use LdapTools\Query\Builder\FilterBuilder;
use LdapTools\Query\UserAccountControlFlags;
use LdapTools\Utilities\ConverterUtilitiesTrait;
use LdapTools\Utilities\MBString;

/**
 * Uses a User Account Control Mapping from the schema and the current attribute/last value context to properly convert
 * the boolean value to what LDAP or PHP expects it to be.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertUserAccountControl implements AttributeConverterInterface
{
    use ConverterUtilitiesTrait, AttributeConverterTrait;

    public function __construct()
    {
        $this->setOptions([
            'uacMap' => [],
            'defaultValue' => UserAccountControlFlags::NORMAL_ACCOUNT,
            'invert' => [],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
        $this->validateCurrentAttribute($this->getOptions()['uacMap']);

        if ($this->getOperationType() == AttributeConverterInterface::TYPE_SEARCH_TO) {
            return $this->getQueryOperator((bool) $value);
        } else {
            return $this->modifyUacValue((bool) $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($value)
    {
        $this->validateCurrentAttribute($this->getOptions()['uacMap']);
        $value = (bool) ((int) $value & (int) $this->getArrayValue($this->getOptions()['uacMap'], $this->getAttribute()));
        
        return $this->shouldInvertValue() ? !$value : $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getShouldAggregateValues()
    {
        return ($this->getOperationType() == self::TYPE_MODIFY || $this->getOperationType() == self::TYPE_CREATE);
    }

    /**
     * {@inheritdoc}
     */
    public function isBatchSupported(Batch $batch)
    {
        return $batch->isTypeReplace();
    }

    /**
     * Given a bool value, do the needed bitwise comparison against the User Account Control value to either remove or
     * add the bit from the overall value.
     *
     * @param bool $value
     * @return int
     */
    protected function modifyUacValue($value)
    {
        $this->setDefaultLastValue('userAccountControl', $this->getOptions()['defaultValue']);
        if (is_array($this->getLastValue())) {
            $lastValue = $this->getLastValue();
            $lastValue = reset($lastValue);
        } else {
            $lastValue = $this->getLastValue();
        }

        // If the bit we are expecting is already set how we want it, then do not attempt to modify it.
        if ($this->fromLdap($lastValue) === $value) {
            return $lastValue;
        }
        $value = $this->shouldInvertValue() ? !$value : $value;

        $mappedValue = $this->getArrayValue($this->getOptions()['uacMap'], $this->getAttribute());
        if ($value) {
            $uac = (int) $lastValue | (int) $mappedValue;
        } else {
            $uac = (int) $lastValue ^ (int) $mappedValue;
        }

        return (string) $uac;
    }

    /**
     * Transform a bool value into the bitwise operator needed for the LDAP filter.
     * 
     * @param bool $value
     * @return \LdapTools\Query\Operator\BaseOperator
     */
    protected function getQueryOperator($value)
    {
        $fb = new FilterBuilder();
        $mappedValue = $this->getArrayValue($this->getOptions()['uacMap'], $this->getAttribute());
        $operator = $fb->bitwiseAnd('userAccountControl', $mappedValue);
        $value = $this->shouldInvertValue() ? !$value : $value;

        return $value ? $operator : $fb->bNot($operator);
    }

    /**
     * Check if the attribute value/meaning should be inverted. Provided as a convenience (ie. enabled) 
     *
     * @return bool
     */
    protected function shouldInvertValue()
    {
        return in_array(MBString::strtolower($this->getAttribute()), MBString::array_change_value_case($this->getOptions()['invert']));
    }
}
