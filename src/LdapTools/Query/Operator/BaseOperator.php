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

use LdapTools\Factory\AttributeConverterFactory;
use LdapTools\Schema\LdapObjectSchema;

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
     * Applies a schema to the operator to do attribute/value conversion if applicable.
     *
     * @param LdapObjectSchema $schema
     */
    public function applySchema(LdapObjectSchema $schema)
    {
        $this->translatedAttribute = $schema->getAttributeToLdap($this->attribute);

        if ($schema->hasConverter($this->attribute) && $this->shouldUseConverter) {
            $converter = $schema->getConverter($this->attribute);
            $this->convertedValue = AttributeConverterFactory::get($converter)->toLdap($this->value);
            $this->converterUsed = true;
        }
    }

    /**
     * Returns the operator translated to its LDAP filter string value.
     *
     * @return string
     */
    public function __toString()
    {
        return self::SEPARATOR_START
            .$this->getAttributeToQuery()
            .$this->operatorSymbol
            .$this->escapeValue($this->getValueForQuery())
            .self::SEPARATOR_END;
    }

    /**
     * Escape any special characters for LDAP.
     *
     * @param mixed $value The value to escape.
     * @param null|string $ignore The characters to ignore.
     * @return string The escaped value.
     */
    protected function escapeValue($value, $ignore = null)
    {
        // If this is a hexadecimal escaped string, then do not escape it.
        return preg_match("/^(\\\[0-9a-fA-F]{2})+$/", (string) $value) ? $value : ldap_escape($value, $ignore);
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

    protected function getValueForQuery()
    {
        return $this->converterUsed ? $this->convertedValue : $this->value;
    }
}
