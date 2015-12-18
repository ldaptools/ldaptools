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
 * Converts a Windows timestamp into a \DateTime object, and from a \DateTime object back to Windows time.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertWindowsTime implements AttributeConverterInterface
{
    use AttributeConverterTrait;

    /**
     * The seconds between 1/1/1601 and 1/1/1970.
     */
    const SECONDS_DIFF = 11644473600;

    /**
     *  Nanoseconds used for conversion.
     */
    const NANO_CONVERT = 10000000;

    /**
     * {@inheritdoc}
     */
    public function toLdap($date)
    {
        if (!$date instanceof \DateTime) {
            throw new AttributeConverterException('The datetime going to LDAP should be a DateTime object.');
        }

        // The number_format call is to make sure a float is properly converted to a string across all platforms.
        return number_format((($date->getTimestamp() + self::SECONDS_DIFF) * self::NANO_CONVERT), 0, '.', '');
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($date)
    {
        return new \DateTime('@'.round(($date / self::NANO_CONVERT) - self::SECONDS_DIFF));
    }
}
