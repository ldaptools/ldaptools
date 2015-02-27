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
 * Use LDAP matching rule OIDs.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class MatchingRule extends BaseOperator
{
    const SYMBOL = '=';

    /**
     * @var string
     */
    protected $oid = '';

    /**
     * @param string $attribute
     * @param string $oid
     * @param mixed $value
     */
    public function __construct($attribute, $oid, $value)
    {
        $this->operatorSymbol = self::SYMBOL;
        $this->attribute = $attribute;
        $this->value = $value;
        $this->oid = $oid;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return self::SEPARATOR_START
            .$this->getAttributeToQuery()
            .':'.$this->oid.':'
            .$this->operatorSymbol
            .LdapUtilities::escapeValue($this->getValueForQuery())
            .self::SEPARATOR_END;
    }
}
