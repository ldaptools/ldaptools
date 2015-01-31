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
 * Represents various ldap server control values.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapControls
{
    /**
     * Paged operation support that splits results into multiple result sets.
     */
    const PAGED_RESULTS = '1.2.840.113556.1.4.319';

    /**
     * Used to specify that tombstones and deleted objects should be visible to the operation.
     */
    const SHOW_DELETED = '1.2.840.113556.1.4.417';
}
