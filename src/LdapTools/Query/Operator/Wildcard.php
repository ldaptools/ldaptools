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

        $this->attribute = $attribute;
        $this->value = $value;
        $this->operatorSymbol = self::EQ;
        $this->wildcardType = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        if ($this->wildcardType == self::CONTAINS) {
            $value = '*'.LdapUtilities::escapeValue($this->getValueForQuery()).'*';
        } elseif ($this->wildcardType == self::STARTS_WITH) {
            $value = LdapUtilities::escapeValue($this->getValueForQuery()).'*';
        } elseif ($this->wildcardType == self::ENDS_WITH) {
            $value = '*'.LdapUtilities::escapeValue($this->getValueForQuery());
        } elseif ($this->wildcardType == self::LIKE) {
            $value = LdapUtilities::escapeValue($this->getValueForQuery(), '*');
        } else {
            $value = '*';
        }

        return self::SEPARATOR_START
            .$this->getAttributeToQuery()
            .$this->operatorSymbol
            .$value
            .self::SEPARATOR_END;
    }
}
