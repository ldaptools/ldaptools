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
use LdapTools\Exception\AttributeConverterException;
use LdapTools\Query\Builder\FilterBuilder;
use LdapTools\Security\Flags;
use LdapTools\Utilities\ConverterUtilitiesTrait;
use LdapTools\Utilities\MBString;

/**
 * Aggregates a generic flag map to/from LDAP given the correct options.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertFlags implements AttributeConverterInterface
{
    use ConverterUtilitiesTrait, AttributeConverterTrait;

    /**
     * @var null|array
     */
    protected $currentOptions;

    public function __construct()
    {
        $this->setOptions([
            'flagMap' => [],
            'defaultValue' => '',
            'invert' => [],
            'attribute' => '',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
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
        $value = (new Flags($value))->has($this->getCurrentAttributeFlag());

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
     * Given a bool value, do the needed bitwise comparison against the flag value to either remove or add the bit from
     * the overall value.
     *
     * @param bool $value
     * @return int
     */
    protected function modifyFlagValue($value)
    {
        $this->setDefaultLastValue($this->getAttributeOptions()['attribute'], $this->getAttributeOptions()['defaultValue']);
        if (is_array($this->getLastValue())) {
            $flags = $this->getLastValue();
            $flags = new Flags(reset($flags));
        } else {
            $flags = new Flags($this->getLastValue());
        }

        // If the bit we are expecting is already set how we want it, then do not attempt to modify it.
        if ($this->fromLdap($flags->getValue()) === $value) {
            return $flags->getValue();
        }
        $value = $this->shouldInvertValue() ? !$value : $value;

        $mappedValue = $this->getCurrentAttributeFlag();
        if ($value) {
            $flags->add((int) $mappedValue);
        } else {
            $flags->remove((int) $mappedValue)->getValue();
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
        $operator = $fb->bitwiseAnd($this->getAttributeOptions()['attribute'], $this->getCurrentAttributeFlag());
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
        $options = $this->getAttributeOptions();

        return isset($options['invert']) && in_array(MBString::strtolower($this->getAttribute()), $options['invert']);
    }

    /**
     * Get the flag value for the current attribute we are checking.
     *
     * @return int
     */
    protected function getCurrentAttributeFlag()
    {
        return $this->getArrayValue($this->getAttributeOptions()['flagMap'], $this->getAttribute());
    }

    /**
     * @return array
     * @throws AttributeConverterException
     */
    protected function getAttributeOptions()
    {
        $attribute = MBString::strtolower($this->getAttribute());

        if (isset($this->currentOptions[$attribute])) {
            return $this->currentOptions[$attribute];
        }

        foreach ($this->getOptions() as $options) {
            if (isset($options['flagMap']) && array_key_exists($attribute, MBString::array_change_key_case($options['flagMap']))) {
                $this->currentOptions[$attribute] = $options;

                return $this->currentOptions[$attribute];
            }
        }

        throw new AttributeConverterException(sprintf(
            'The attribute "%s" does not appear to be mapped to any flag values.',
            $this->getAttribute()
        ));
    }
}
