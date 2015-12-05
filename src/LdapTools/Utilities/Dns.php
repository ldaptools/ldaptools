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
 * A very thin wrapper around some PHP DNS functions. Mostly for the purpose of testing.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class Dns
{
    /**
     * Call this just like you would dns_get_record.
     *
     * @param mixed ...$arguments
     * @return array
     */
    public function getRecord(...$arguments)
    {
        return dns_get_record(...$arguments);
    }
}
