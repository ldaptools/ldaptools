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
use LdapTools\BatchModify\Batch;
use LdapTools\Exception\AttributeConverterException;
use LdapTools\Query\Builder\FilterBuilder;
use LdapTools\Utilities\ConverterUtilitiesTrait;

/**
 * Aggregates a generic flag map to/from LDAP given the correct options.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertFlags implements AttributeConverterInterface
{
    use ConverterUtilitiesTrait, AttributeConverterTrait;

    /**
     * @var array
     */
    protected $options = [
        # The fully qualified flag class and enum name.
        # ie. 'LdapTools\Enums\AD\UserAccountControl::Disabled'
        'flag_enum' => '',
        # The default value to use for a flag enum class if no value is defined for the flags yet
        # ie. 'LdapTools\Enums\AD\UserAccountControl' => 'NormalAccount'
        'default_value' => [],
        # The LDAP attribute to use for LDAP queries against for specific flag enum classes.
        # ie. 'LdapTools\Enums\AD\UserAccountControl' => 'userAccountControl'
        'attribute' => [],
        # If the attribute value/meaning should be inverted. Provided as a convenience (ie. enabled)
        'invert' => false,
    ];

    /**
     * @var string
     */
    protected $flagClass = '';

    /**
     * @var string
     */
    protected $flagName = '';

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
        $this->init();
        if ($this->getOperationType() == AttributeConverterInterface::TYPE_SEARCH_TO) {
            return $this->getQueryOperator((bool) $value);
        }

        return (string) $this->modifyFlagValue((bool) $value);
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($value)
    {
        $this->init();
        $value = $this->getFlagEnum($value)->has($this->flagName);

        return $this->options['invert'] ? !$value : $value;
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
     * Given a bool value, do the needed bitwise comparison against the flag value to either remove or add the bit from
     * the overall value.
     *
     * @param bool $value
     * @return int
     */
    protected function modifyFlagValue($value)
    {
        $this->setDefaultLastValue($this->getEnumAttribute(), $this->getDefaultEnumValue());
        $flags = $this->getFlagFromLastValue($this->getLastValue());

        $value = $this->options['invert'] ? !$value : $value;
        if ($value) {
            $flags->add($this->flagName);
        } else {
            $flags->remove($this->flagName);
        }

        return $flags->getValue();
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
        $operator = $fb->bitwiseAnd($this->getEnumAttribute(), $this->getFlagEnum()->getValue());
        $value = $this->options['invert'] ? !$value : $value;

        return $value ? $operator : $fb->bNot($operator);
    }

    /**
     * @param mixed $lastValue
     * @return FlagEnumInterface
     */
    protected function getFlagFromLastValue($lastValue)
    {
        if (is_array($lastValue)) {
            $flags = $this->getFlagEnum(reset($lastValue));
        } else {
            $flags = $this->getFlagEnum($lastValue);
        }

        return $flags;
    }

    /**
     * @param mixed $value
     * @return FlagEnumInterface
     * @throws AttributeConverterException
     */
    protected function getFlagEnum($value = null)
    {
        $enumValue = $value ?: $this->flagName;

        if (!is_subclass_of($this->flagClass, FlagEnumInterface::class)) {
            throw new AttributeConverterException(sprintf(
                'The flag_enum "%s" for "%s" must be an instance of "%s"',
                $this->flagClass,
                $this->getAttribute(),
                FlagEnumInterface::class
            ));
        }

        return new $this->flagClass($enumValue);
    }

    /**
     * @throws AttributeConverterException
     */
    protected function init()
    {
        $parts = explode('::', $this->options['flag_enum']);

        if (count($parts) != 2) {
            throw new AttributeConverterException(sprintf(
                'The flag_enum value "%s" is not valid for "%s".',
                $this->options['flag_enum'],
                $this->getAttribute()
            ));
        }

        $this->flagClass = $parts[0];
        $this->flagName = $parts[1];
    }

    /**
     * @return mixed
     */
    protected function getDefaultEnumValue()
    {
        $default = null;

        if (isset($this->options['default_value'][$this->flagClass])) {
            $default = $this->options['default_value'][$this->flagClass];
        }

        return $default;
    }

    /**
     * @return string
     * @throws AttributeConverterException
     */
    protected function getEnumAttribute()
    {
        if (!isset($this->options['attribute'][$this->flagClass])) {
            throw new AttributeConverterException(sprintf(
                'No attribute option is defined for class "%s" and "%s".',
                $this->flagClass,
                $this->getAttribute()
            ));
        }

        return $this->options['attribute'][$this->flagClass];
    }
}
