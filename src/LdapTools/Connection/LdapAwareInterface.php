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
 * The LDAP connection aware interface.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
interface LdapAwareInterface
{
    /**
     * @param LdapConnectionInterface $connection
     */
    public function setLdapConnection(LdapConnectionInterface $connection = null);

    /**
     * @return LdapConnectionInterface
     */
    public function getLdapConnection();
}
