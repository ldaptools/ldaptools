<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Utilities;

/**
 * Some number utility functions for hex to int conversions.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
trait NumberUtilitiesTrait
{
    /**
     * Hex string of signed long 32bit big-endian to int form.
     *
     * @param string $hex
     * @return int
     */
    protected function hexSLong32Be2Int($hex)
    {
        return unpack('l1int', hex2bin($hex))['int'];
    }

    /**
     * Hex string of unsigned long 32bit little-endian to int form.
     *
     * @param string $hex
     * @return int
     */
    protected function hexULong32Le2int($hex)
    {
        return unpack('V1int', hex2bin($hex))['int'];
    }

    /**
     * Hex string of unsigned short 16bit little-endian to int form.
     *
     * @param string $hex
     * @return int
     */
    protected function hexUShort16Le2Int($hex)
    {
        return unpack('v1int', hex2bin($hex))['int'];
    }
}
