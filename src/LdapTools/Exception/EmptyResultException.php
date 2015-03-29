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
 * Triggered when querying LDAP and a non-empty result set is expected for a return value.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class EmptyResultException extends Exception
{
}
