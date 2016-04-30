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

/**
 * Represents the various LDAP comparison operator types available.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class Comparison extends BaseOperator
{
    /**
     * Equals To operation.
     */
    const EQ = '=';

    /**
     * Approximately Equal To operation.
     */
    const AEQ = '~=';

    /**
     * Less Than or Equal To operation.
     */
    const LTE = '<=';

    /**
     * Greater Than or Equal to operation.
     */
    const GTE = '>=';

    /**
     * Construct a common attribute comparison check.
     *
     * @param string $attribute The attribute to check.
     * @param string $comparison The comparison type.
     * @param mixed $value The value to check for.
     */
    public function __construct($attribute, $comparison, $value)
    {
        $this->validOperators = [
            self::AEQ,
            self::EQ,
            self::GTE,
            self::LTE,
        ];
        $this->setOperatorSymbol($comparison);
        $this->setAttribute($attribute);
        $this->value = $value;
    }
}
