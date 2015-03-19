<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Object;

/**
 * Base object types which are then further defined in the schema.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapObjectType
{
    /**
     * A normal user account.
     */
    const USER = 'user';

    /**
     * Either a security or distribution group
     */
    const GROUP = 'group';

    /**
     * A contact object.
     */
    const CONTACT = 'contact';

    /**
     * A computer object.
     */
    const COMPUTER = 'computer';

    /**
     * An OU object.
     */
    const OU = 'ou';
}
