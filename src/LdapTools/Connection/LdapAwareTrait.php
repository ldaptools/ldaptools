<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Connection;

/**
 * LDAP connection aware property and functions.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
trait LdapAwareTrait
{
    /**
     * @var LdapConnectionInterface|null
     */
    protected $connection;

    /**
     * @param LdapConnectionInterface|null $connection
     */
    public function setLdapConnection(LdapConnectionInterface $connection = null)
    {
        $this->connection = $connection;
    }

    /**
     * @return LdapConnectionInterface|null
     */
    public function getLdapConnection()
    {
        return $this->connection;
    }
}
