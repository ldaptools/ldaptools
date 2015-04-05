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
 * If a specific attribute is requested but not found and the query depends on it.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class AttributeNotFoundException extends Exception
{
}
