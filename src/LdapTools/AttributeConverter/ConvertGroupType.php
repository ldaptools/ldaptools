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

use Enums\FlagEnumInterface;
use LdapTools\Enums\AD\GroupType;
use LdapTools\Query\Builder\FilterBuilder;

/**
 * Converts the groupType bitmask value to a PHP bool, or a bool for a specific bit back to the value for LDAP.
 *
 * @see https://msdn.microsoft.com/en-us/library/cc223142.aspx
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertGroupType extends ConvertFlags
{
    const SCOPES = [
        'DomainLocalGroup',
        'GlobalGroup',
        'UniversalGroup',
    ];

    public function __construct()
    {
        # Default to a security enabled global group
        $this->options['default_value'][GroupType::class] = -2147483646;
    }

    /**
     * Given a bool value, do the needed bitwise comparison against the groupType value to either remove or
     * add the bit from the overall value.
     *
     * @param bool $value
     * @return int
     */
    protected function modifyFlagValue($value)
    {
        $this->setDefaultLastValue('groupType', $this->getDefaultEnumValue());
        $flags = $this->getFlagFromLastValue($this->getLastValue());

        if (!in_array($this->flagName, self::SCOPES)) {
            $this->modifyBitmaskValue(
                $flags,
                $this->options['invert'] ? !$value : $value,
                $this->flagName
            );
        } else {
            $this->modifyGroupScopeBit($flags, $value);
        }

        return $flags->getValue();
    }

    /**
     * Based on the current value, remove the bit for the scope that is already set before adding the new one (if the
     * value is set to true for the scope to be active anyway).
     *
     * @param FlagEnumInterface $flags
     * @param bool $value
     */
    protected function modifyGroupScopeBit(FlagEnumInterface $flags, $value)
    {
        if ($value) {
            foreach (self::SCOPES as $scope) {
                if ($this->flagName === $scope) {
                    continue;
                }
                if ($flags->has($scope)) {
                    $this->modifyBitmaskValue($flags, false, $scope);
                }
            }
        }

        $this->modifyBitmaskValue($flags, $value, $this->flagName);
    }

    /**
     * Modify the existing value based on the attributes bit.
     *
     * @param FlagEnumInterface $flags
     * @param bool $toggle
     * @param string $flagName
     */
    protected function modifyBitmaskValue($flags, $toggle, $flagName)
    {
        $bit = $this->getBitForAttribute($flagName);

        if ($toggle) {
            $flags->add($bit);
        } else {
            $flags->remove($bit);
        }
    }

    /**
     * @param string $flagName
     * @return int
     */
    protected function getBitForAttribute($flagName)
    {
        $bit = $this->getFlagEnum($flagName)->getValue();

        if (!in_array($flagName, self::SCOPES)) {
            $bit = -1 * abs($bit);
        }

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
        $bit = abs($this->getBitForAttribute($this->flagName));
        $operator = $fb->bitwiseAnd('groupType', (string) $bit);
        $value = $this->options['invert'] ? !$value : $value;

        return $value ? $operator : $fb->bNot($operator);
    }
}
