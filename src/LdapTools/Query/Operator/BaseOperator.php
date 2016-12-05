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

use LdapTools\Exception\LdapQueryException;
use LdapTools\Utilities\LdapUtilities;

/**
 * The base Operator implementation.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
abstract class BaseOperator
{
    /**
     * Separates the alias from the attribute.
     */
    const ALIAS_DELIMITER = '.';

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
     * @var array The attribute name for a specific alias after its schema has been applied.
     */
    protected $translatedAliasAttribute = [];

    /**
     * @var mixed The value for the attribute without any possible converters.
     */
    protected $value;

    /**
     * @var mixed The attribute name after any possible schema has been applied.
     */
    protected $convertedValue;

    /**
     * @var array The attribute value for specific aliases that were used.
     */
    protected $convertedAliasValue = [];

    /**
     * @var bool Whether or not an Attribute Converter was used.
     */
    protected $converterUsed = false;

    /**
     * @var array Whether or not an attribute converter was used for a specific alias.
     */
    protected $converterAliasUsed = [];

    /**
     * @var bool Whether or not a converter, if present, will be used against this operator.
     */
    protected $shouldUseConverter = true;

    /**
     * @var string The operator symbol (ie. &, |, =, <=, >=, etc)
     */
    protected $operatorSymbol = '';

    /**
     * @var array The valid operator symbols that can be set.
     */
    protected $validOperators = [];

    /**
     * @var null|string The alias this operator is associated with, if any.
     */
    protected $alias;

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
     * @param string|null $alias
     * @return mixed
     */
    public function getConvertedValue($alias = null)
    {
        if ($alias && isset($this->convertedAliasValue[$alias])) {
            return $this->convertedAliasValue[$alias];
        } else {
            return $this->convertedValue;
        }
    }

    /**
     * Set the converted value.
     *
     * @param string|null $alias
     * @param mixed $value
     */
    public function setConvertedValue($value, $alias = null)
    {
        if ($alias) {
            $this->convertedAliasValue[$alias] = $value;
        } else {
            $this->convertedValue = $value;
        }
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
        if (strpos($attribute, '.') !== false) {
            $pieces = explode('.', $attribute, 2);
            $this->setAlias($pieces[0]);
            $attribute = $pieces[1];
        // If an alias was already set then this must be set back to null.
        } else {
            $this->alias = null;
        }
        $this->attribute = $attribute;
    }

    /**
     * Get the translated attribute (the attribute after the schema conversion).
     *
     * @param string|null $alias
     * @return string|null
     */
    public function getTranslatedAttribute($alias = null)
    {
        if ($alias && isset($this->translatedAliasAttribute[$alias])) {
            return $this->translatedAliasAttribute[$alias];
        } else {
            return $this->translatedAttribute;
        }
    }

    /**
     * Set the translated attribute (the attribute after the schema conversion).
     *
     * @param string|null $alias
     * @param $attribute|null
     */
    public function setTranslatedAttribute($attribute, $alias = null)
    {
        if ($alias) {
            $this->translatedAliasAttribute[$alias] = $attribute;
        } else {
            $this->translatedAttribute = $attribute;
        }
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
     * @throws LdapQueryException
     */
    public function setOperatorSymbol($symbol)
    {
        if (!in_array($symbol, $this->validOperators)) {
            throw new LdapQueryException(sprintf(
                'Invalid operator symbol "%s". Valid operator symbols are: %s',
                $symbol,
                implode(', ', $this->validOperators)
            ));
        }

        $this->operatorSymbol = $symbol;
    }

    /**
     * Get the alias this operator is associated with. If none is assigned this will be null.
     *
     * @return string|null
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Set the alias this operator is associated with. To assign no alias set it to null.
     *
     * @param string|null
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
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
     * @param string|null $alias
     */
    public function setWasConverterUsed($value, $alias = null)
    {
        if ($alias) {
            $this->converterAliasUsed[$alias] = (bool) $value;
        } else {
            $this->converterUsed = (bool) $value;
        }
    }

    /**
     * Get whether a converter was used or not.
     *
     * @param string|null $alias
     * @return bool
     */
    public function getWasConverterUsed($alias = null)
    {
        if ($alias) {
            return isset($this->converterAliasUsed[$alias]) ? $this->converterAliasUsed[$alias] : false;
        } else {
            return $this->converterUsed;
        }
    }

    /**
     * @deprecated Use the 'toLdapFilter()' method instead.
     * @param null $alias
     * @return string
     */
    public function getLdapFilter($alias = null)
    {
        trigger_error('The '.__METHOD__.' method is deprecated and will be removed in a later version. Use toLdapFilter() instead.', E_USER_DEPRECATED);

        return $this->toLdapFilter($alias);
    }

    /**
     * Returns the operator translated to its LDAP filter string value.
     *
     * @param string|null $alias
     * @return string
     */
    public function toLdapFilter($alias = null)
    {
        if ($this->skipFilterForAlias($alias)) {
            return '';
        }
        if ($this->getValueForQuery($alias) instanceof BaseOperator) {
            return $this->getValueForQuery($alias)->toLdapFilter($alias);
        }

        return self::SEPARATOR_START
            .$this->getAttributeToQuery($alias)
            .$this->operatorSymbol
            .LdapUtilities::escapeValue($this->getValueForQuery($alias), null, LDAP_ESCAPE_FILTER)
            .self::SEPARATOR_END;
    }

    /**
     * This will get the translated attribute or just the attribute if no schema translation was done.
     *
     * @param null|string $alias
     * @return string
     * @throws LdapQueryException
     */
    protected function getAttributeToQuery($alias)
    {
        $attribute = $this->getTranslatedAttribute($alias) ?: $this->getAttribute();

        // This avoids possible LDAP injection from unverified input for an attribute name.
        if (!LdapUtilities::isValidAttributeFormat($attribute)) {
            throw new LdapQueryException(sprintf('Attribute "%s" is not a valid name or OID.', $attribute));
        }

        return $attribute;
    }

    /**
     * Depending on whether a converter was used, get the value that should be used for the query.
     *
     * @param null|string $alias
     * @return mixed
     */
    protected function getValueForQuery($alias)
    {
        $value = $this->getConvertedValue($alias);
        
        return is_null($value) ? $this->getValue() : $value;
    }

    /**
     * Determine whether the operator should actually produce a filter (only if alias is null or matches the current one)
     *
     * @param string|null $alias
     * @return bool
     */
    protected function skipFilterForAlias($alias)
    {
        return $this->getAlias() && $this->getAlias() != $alias;
    }
}
