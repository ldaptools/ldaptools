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
 * Represents a LDAP wildcard search using a '*'. This is used to help escape the proper values for the filter.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class Wildcard extends Comparison
{
    /**
     * The wildcard will be at the end of the value.
     */
    const STARTS_WITH = 'STARTS_WITH';

    /**
     * The wildcard will be a the start of the value.
     */
    const ENDS_WITH = 'ENDS_WITH';

    /**
     * The wildcard will be on both ends of the value.
     */
    const CONTAINS = 'CONTAINS';

    /**
     * Any '*' characters placed within the value will be ignored when the value is escaped.
     */
    const LIKE = 'LIKE';

    /**
     * The value is ignored and is replaced with a single '*'. This checks for the existence of an attribute.
     */
    const PRESENT = 'PRESENT';

    /**
     * @var string The wildcard type selected.
     */
    protected $wildcardType;

    /**
     * Construct a filter that contains a wildcard.
     *
     * @param string $attribute
     * @param string $type
     * @param null|string $value
     * @throws LdapQueryException
     */
    public function __construct($attribute, $type, $value = null)
    {
        if (!defined('self::'.strtoupper($type))) {
            throw new LdapQueryException(sprintf('Invalid wildcard operator type "%s".', $type));
        } elseif ($type == self::PRESENT) {
            $this->setUseConverter(false);
        }

        $this->setAttribute($attribute);
        $this->value = $value;
        $this->validOperators = [ self::EQ ];
        $this->operatorSymbol = self::EQ;
        $this->wildcardType = $type;
    }

    /**
     * Get the wildcard type used by this operator.
     *
     * @return string
     */
    public function getWildcardType()
    {
        return $this->wildcardType;
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
        if ($this->getValueForQuery($alias) instanceof BaseOperator) {
            return $this->getValueForQuery($alias)->toLdapFilter($alias);
        }

        if ($this->wildcardType == self::CONTAINS) {
            $value = '*'.LdapUtilities::escapeValue($this->getValueForQuery($alias), null, LDAP_ESCAPE_FILTER).'*';
        } elseif ($this->wildcardType == self::STARTS_WITH) {
            $value = LdapUtilities::escapeValue($this->getValueForQuery($alias), null, LDAP_ESCAPE_FILTER).'*';
        } elseif ($this->wildcardType == self::ENDS_WITH) {
            $value = '*'.LdapUtilities::escapeValue($this->getValueForQuery($alias), null, LDAP_ESCAPE_FILTER);
        } elseif ($this->wildcardType == self::LIKE) {
            $value = LdapUtilities::escapeValue($this->getValueForQuery($alias), '*', LDAP_ESCAPE_FILTER);
        } else {
            $value = '*';
        }

        return self::SEPARATOR_START
            .$this->getAttributeToQuery($alias)
            .$this->operatorSymbol
            .$value
            .self::SEPARATOR_END;
    }
}
