<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Log;

/**
 * LDAP Logging interface.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
interface LdapLoggerInterface
{
    public function start(LogOperation $operation);

    public function end(LogOperation $operation);
}
