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
 * Converts Generalized Time format to/from a \DateTime object.
 *
 * @link http://tools.ietf.org/html/rfc4517#section-3.3.13
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertGeneralizedTime implements AttributeConverterInterface
{
    use AttributeConverterTrait;

    /**
     * {@inheritdoc}
     */
    public function toLdap($date)
    {
        if (!$date instanceof \DateTime) {
            throw new AttributeConverterException('The datetime going to LDAP should be a DateTime object.');
        }

        return $date->format('YmdHis').$this->getTzOffsetForTimestamp($date->format('P'));
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($timestamp)
    {
        preg_match("/^(\d+).?0?(([+-]\d\d)(\d\d)|Z)$/i", $timestamp, $matches);

        if (!isset($matches[1]) || !isset($matches[2])) {
            throw new \RuntimeException(sprintf('Invalid timestamp encountered: %s', $timestamp));
        }

        $tz = (strtoupper($matches[2]) == 'Z') ? 'UTC' : $matches[3].':'.$matches[4];

        return new \DateTime($matches[1], new \DateTimeZone($tz));
    }

    /**
     * Get the timezone offset that will be appended to the timestamp.
     *
     * @param string $tzOffset As given from the \DateTime object.
     * @return string
     */
    protected function getTzOffsetForTimestamp($tzOffset)
    {
        $tzOffset = str_replace(':', '', $tzOffset);

        return ($tzOffset == '+0000') ? 'Z' : $tzOffset;
    }
}
