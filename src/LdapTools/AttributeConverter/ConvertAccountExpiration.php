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

use LdapTools\Exception\AttributeConverterException;

/**
 * Used to convert an accountExpires value to a DateTime object, or detect if the value indicates it never expires and
 * either set it as false. To set the account to never expire always pass a bool false as the value. Otherwise to set a
 * date and time for the account to expire then set a \DateTime object.
 *
 * @see https://msdn.microsoft.com/en-us/library/ms675098%28v=vs.85%29.aspx
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertAccountExpiration implements AttributeConverterInterface
{
    use AttributeConverterTrait;

    /**
     * This value (and 0) indicates that the account never expires.
     */
    const NEVER_EXPIRES = '9223372036854775807';

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
        if (!($value === false || ($value instanceof \DateTime))) {
            throw new AttributeConverterException('Expecting a bool or DateTime when converting to LDAP.');
        }

        return ($value === false) ? '0' : (new ConvertWindowsTime())->toLdap($value);
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($value)
    {
        return ($value == 0  || $value == self::NEVER_EXPIRES) ? false : (new ConvertWindowsTime())->fromLdap($value);
    }
}
