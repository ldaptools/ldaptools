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
    /**
     * The start method is called against a LDAP operation prior to the operation being executed.
     *
     * @param LogOperation $operation
     */
    public function start(LogOperation $operation);

    /**
     * The end method is called against a LDAP operation after the operation has finished executing.
     *
     * @param LogOperation $operation
     */
    public function end(LogOperation $operation);
}
