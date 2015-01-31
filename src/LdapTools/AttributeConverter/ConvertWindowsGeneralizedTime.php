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

/**
 * Converts Windows Generalized Time format to/from a \DateTime object.
 *
 * @see https://msdn.microsoft.com/en-us/library/aa772189%28v=vs.85%29.aspx
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertWindowsGeneralizedTime implements AttributeConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function toLdap($date)
    {
        if (!$date instanceof \DateTime) {
            throw new \InvalidArgumentException('The datetime going to LDAP should be a DateTime object.');
        }

        $tzOffset = str_replace(':', '', $date->format('P'));
        $tzOffset = ($tzOffset == '+0000') ? 'Z' : $tzOffset;
        $tzOffset = '.0'.$tzOffset;

        return $date->format('YmdHis').$tzOffset;
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($timestamp)
    {
        preg_match("/^(\d+).0(([+-]\d\d)(\d\d)|Z)$/i", $timestamp, $matches);

        if (!isset($matches[1]) || !isset($matches[2])) {
            throw new \RuntimeException(sprintf('Invalid timestamp encountered: %s', $timestamp));
        }

        $tz = (strtoupper($matches[2]) == 'Z') ? 'UTC' : $matches[3].':'.$matches[4];

        return new \DateTime($matches[1], new \DateTimeZone($tz));
    }
}
