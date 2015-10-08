<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Query\Operator;

use LdapTools\Utilities\LdapUtilities;

/**
 * The base Operator implementation.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
abstract class BaseOperator
{
    /**
     * The start parenthesis of a LDAP grouping.
     */
    const SEPARATOR_START = '(';

    /**
     * The end parenthesis of a LDAP grouping.
     */
    const SEPARATOR_END = ')';

    /**
     * @var string The attribute name.
     */
    protected $attribute = '';

    /**
     * @var string The attribute name after any possible schema has been applied.
     */
    protected $translatedAttribute = '';

    /**
     * @var mixed The value for the attribute without any possible converters.
     */
    protected $value;

    /**
     * @var mixed The attribute name after any possible schema has been applied.
     */
    protected $convertedValue;

    /**
     * @var bool Whether or not an Attribute Converter was used.
     */
    protected $converterUsed = false;

    /**
     * @var bool Whether or not a converter, if present, will be used against this operator.
     */
    protected $shouldUseConverter = true;

    /**
     * @var string The operator symbol (ie. &, |, =, <=, >=, etc)
     */
    protected $operatorSymbol = '';

    /**
     * Get the attribute value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the attribute value.
     *
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Get the converted value.
     *
     * @return mixed
     */
    public function getConvertedValue()
    {
        return $this->convertedValue;
    }

    /**
     * Set the converted value.
     *
     * @param mixed $value
     */
    public function setConvertedValue($value)
    {
        $this->convertedValue = $value;
    }

    /**
     * Get the attribute.
     *
     * @return string|null
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * Set the attribute.
     *
     * @param string $attribute
     */
    public function setAttribute($attribute)
    {
        $this->attribute = $attribute;
    }

    /**
     * Get the translated attribute (the attribute after the schema conversion).
     *
     * @return string|null
     */
    public function getTranslatedAttribute()
    {
        return $this->translatedAttribute;
    }

    /**
     * Set the translated attribute (the attribute after the schema conversion).
     *
     * @param $attribute|null
     */
    public function setTranslatedAttribute($attribute)
    {
        $this->translatedAttribute = $attribute;
    }

    /**
     * Get the operator symbol in use.
     *
     * @return string
     */
    public function getOperatorSymbol()
    {
        return $this->operatorSymbol;
    }

    /**
     * Set the operator symbol in use.
     *
     * @param $symbol
     */
    public function setOperatorSymbol($symbol)
    {
        $this->operatorSymbol = $symbol;
    }

    /**
     * Set whether a converter should be used or not.
     *
     * @param bool $value
     */
    public function setUseConverter($value)
    {
        $this->shouldUseConverter = (bool) $value;
    }

    /**
     * Get whether a converter should be used or not.
     *
     * @return bool
     */
    public function getUseConverter()
    {
        return $this->shouldUseConverter;
    }

    /**
     * Set whether a converter was used or not.
     *
     * @param bool $value
     */
    public function setWasConverterUsed($value)
    {
        $this->converterUsed = (bool) $value;
    }

    /**
     * Get whether a converter was used or not.
     *
     * @return bool
     */
    public function getWasConverterUsed()
    {
        return $this->converterUsed;
    }

    /**
     * Returns the operator translated to its LDAP filter string value.
     *
     * @return string
     */
    public function __toString()
    {
        if ( is_array($this->getValueForQuery() ) ) {

            $values = Array();

            foreach( $this->getValueForQuery() as $value ) {
                    $values[] = self::SEPARATOR_START
                            .$this->getAttributeToQuery()
                            .$this->operatorSymbol
                    .$value
                            .self::SEPARATOR_END;
            }
            return self::SEPARATOR_START
                ."&"
                .implode($values)
                .self::SEPARATOR_END;
        }

            return self::SEPARATOR_START
                .$this->getAttributeToQuery()
                .$this->operatorSymbol
                .LdapUtilities::escapeValue($this->getValueForQuery())
                .self::SEPARATOR_END;
    }

    /**
     * This will get the translated attribute or just the attribute if no schema translation was done.
     *
     * @return string
     */
    protected function getAttributeToQuery()
    {
        return $this->translatedAttribute ? $this->translatedAttribute : $this->attribute;
    }

    /**
     * Depending on whether a converter was used, get the value that should be used for the query.
     *
     * @return mixed
     */
    protected function getValueForQuery()
    {
        return $this->converterUsed ? $this->convertedValue : $this->value;
    }
}
