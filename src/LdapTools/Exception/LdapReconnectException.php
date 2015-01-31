<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Exception;

/**
 * A specific exception that can occur after attempting to authenticate a user a re-binding to LDAP.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapReconnectException extends Exception
{
}
