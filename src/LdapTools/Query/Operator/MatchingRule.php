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
 * Represents a matching rule/extensible match.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class MatchingRule extends BaseOperator
{
    const SYMBOL = ':=';

    /**
     * Used in an extensible match to indicate the rule should operate against the DN RDNs.
     */
    const DN_FLAG = ':dn';

    /**
     * @var string
     */
    protected $rule;

    /**
     * @var bool
     */
    protected $useDnFlag = false;

    /**
     * @param string|null $attribute
     * @param string|null $rule
     * @param mixed $value
     * @param bool $useDnFlag
     */
    public function __construct($attribute, $rule, $value, $useDnFlag = false)
    {
        $this->validOperators = [ self::SYMBOL ];
        $this->operatorSymbol = self::SYMBOL;
        $this->setAttribute($attribute);
        $this->value = $value;
        $this->rule = $rule;
        $this->useDnFlag = $useDnFlag;
    }

    /**
     * Get the matching rule.
     *
     * @return null|string
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * Set the matching rule.
     *
     * @param null|string $rule
     * @return $this
     */
    public function setRule($rule)
    {
        $this->rule = $rule;

        return $this;
    }

    /**
     * Set whether or not the DN flag should be used.
     *
     * @param bool $useDnFlag
     * @return $this
     */
    public function setUseDnFlag($useDnFlag)
    {
        $this->useDnFlag = (bool) $useDnFlag;

        return $this;
    }

    /**
     * Whether or not the DN flag is being used.
     *
     * @return bool
     */
    public function getUseDnFlag()
    {
        return $this->useDnFlag;
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
     * {@inheritdoc}
     */
    public function toLdapFilter($alias = null)
    {
        if ($this->skipFilterForAlias($alias)) {
            return '';
        }
        if ($this->rule && !LdapUtilities::isValidAttributeFormat($this->rule)) {
            throw new LdapQueryException(sprintf('Matching rule "%s" is not a valid format.', $this->rule));
        }
        if (!$this->rule && empty($this->getAttribute())) {
            throw new LdapQueryException('If you do not specify a matching rule, you must specify an attribute.');
        }
        if ($this->getValueForQuery($alias) instanceof BaseOperator) {
            return $this->getValueForQuery($alias)->toLdapFilter($alias);
        }

        return self::SEPARATOR_START
            .($this->getAttribute() ? $this->getAttributeToQuery($alias) : '')
            .($this->useDnFlag ? self::DN_FLAG : '')
            .($this->rule ? ':'.$this->rule : '')
            .$this->operatorSymbol
            .LdapUtilities::escapeValue($this->getValueForQuery($alias), null, LDAP_ESCAPE_FILTER)
            .self::SEPARATOR_END;
    }
}
