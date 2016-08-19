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
use LdapTools\Query\GroupTypeFlags;
use LdapTools\Utilities\ConverterUtilitiesTrait;
use LdapTools\Utilities\MBString;

/**
 * Converts the groupType bitmask value to a PHP bool, or a bool for a specific bit back to the value for LDAP.
 *
 * @see https://msdn.microsoft.com/en-us/library/cc223142.aspx
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertGroupType implements AttributeConverterInterface
{
    use ConverterUtilitiesTrait, AttributeConverterTrait;

    public function __construct()
    {
        $this->setOptions([
            'typeMap' => [],
            'types' => [],
            'defaultValue' => GroupTypeFlags::GLOBAL_GROUP + GroupTypeFlags::SECURITY_ENABLED,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
        $this->validateCurrentAttribute($this->getOptions()['typeMap']);

        if ($this->getOperationType() == AttributeConverterInterface::TYPE_SEARCH_TO) {
            $valueToLdap = $this->getQueryOperator($value);
        } else {
            $valueToLdap = $this->modifyGroupTypeValue((bool)$value);
        }

        return $valueToLdap;
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($value)
    {
        $this->validateCurrentAttribute($this->getOptions()['typeMap']);
        $value = (bool) ((int) $value & (int) $this->getArrayValue($this->getOptions()['typeMap'], $this->getAttribute()));

        // Invert the value for the distribution group, as it is the absence of the security bit.
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
     * Given a bool value, do the needed bitwise comparison against the groupType value to either remove or
     * add the bit from the overall value.
     *
     * @param bool $value
     * @return int
     */
    protected function modifyGroupTypeValue($value)
    {
        $this->setDefaultLastValue('groupType', $this->getOptions()['defaultValue']);
        $lastValue = is_array($this->getLastValue()) ? reset($this->getLastValue()) : $this->getLastValue();

        // If the bit we are expecting is already set how we want it, then do not attempt to modify it.
        if ($this->fromLdap($lastValue) == $value) {
            return $lastValue;
        }

        if (in_array($this->getAttribute(), $this->getOptions()['types']['type'])) {
            $value = $this->shouldInvertValue() ? !$value : $value;
            $this->setLastValue($this->modifyBitmaskValue($lastValue, $value, $this->getAttribute()));
        } else {
            $this->modifyGroupScopeBit($lastValue, $value);
        }

        return (string) $this->getLastValue();
    }

    /**
     * Based on the current value, remove the bit for the scope that is already set before adding the new one (if the
     * value is set to true for the scope to be active anyway).
     *
     * @param int $lastValue
     * @param bool $value
     */
    protected function modifyGroupScopeBit($lastValue, $value)
    {
        if ($value) {
            foreach ($this->getOptions()['types']['scope'] as $attribute) {
                if (MBString::strtolower($attribute) == MBString::strtolower($this->getAttribute())) {
                    continue;
                }
                if (((int)$lastValue & (int)$this->getOptions()['typeMap'][$attribute])) {
                    $lastValue = $this->modifyBitmaskValue($lastValue, false, $attribute);
                }
            }
        }
        $lastValue = $this->modifyBitmaskValue($lastValue, $value, $this->getAttribute());

        $this->setLastValue($lastValue);
    }

    /**
     * Modify the existing value based on the attributes bit.
     *
     * @param int $value
     * @param bool $toggle
     * @param string $attribute
     * @return int
     */
    protected function modifyBitmaskValue($value, $toggle, $attribute)
    {
        $bit = $this->getBitForAttribute($attribute);

        if ($toggle) {
            $value = (int) $value + (int) $bit;
        } else {
            $value = (int) $value - (int) $bit;
        }

        return $value;
    }

    /**
     * Check if this is a distribution type for the attribute. That type is just inverse of a security enabled type.
     *
     * @return bool
     */
    protected function shouldInvertValue()
    {
        return MBString::strtolower($this->getAttribute()) == MBString::strtolower($this->getOptions()['distribution']);
    }

    /**
     * @param string $attribute
     * @return int
     */
    protected function getBitForAttribute($attribute)
    {
        $bit = MBString::array_change_key_case($this->getOptions()['typeMap'])[MBString::strtolower($attribute)];
        $bit = in_array($this->getAttribute(), $this->getOptions()['types']['type']) ? -1 * abs($bit) : $bit;

        return $bit;
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
        $bit = abs($this->getBitForAttribute($this->getAttribute()));
        $operator = $fb->bitwiseAnd('groupType', (string) $bit);
        $value = $this->shouldInvertValue() ? !$value : $value;

        return $value ? $operator : $fb->bNot($operator);
    }
}
