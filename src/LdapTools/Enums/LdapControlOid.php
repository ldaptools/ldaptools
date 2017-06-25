<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Enums;

use Enums\SimpleEnumInterface;
use Enums\SimpleEnumTrait;

/**
 * Possible LDAP Controls and their respective OIDs.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapControlOid implements SimpleEnumInterface
{
    use SimpleEnumTrait;

    /**
     * Paged operation support that splits results into multiple result sets.
     */
    const PagedResults = '1.2.840.113556.1.4.319';

    /**
     * Used to specify that tombstones and deleted objects should be visible to the operation.
     */
    const ShowDeleted = '1.2.840.113556.1.4.417';

    /**
     * Used to specify that all children of a LDAP object should be removed during a delete operation.
     */
    const SubTreeDelete = '1.2.840.113556.1.4.805';

    /**
     * Used to control what part of a Windows Security descriptor is selected/used on searches/modifications/adds.
     *
     * @see https://msdn.microsoft.com/en-us/library/cc223323.aspx
     */
    const SDFlagsControl = '1.2.840.113556.1.4.801';

    /**
     * Used to enforce password policy requirements when resetting a password in AD (2012 or higher).
     *
     * @see https://msdn.microsoft.com/en-us/library/hh128228.aspx
     */
    const PasswordPolicyHints = '1.2.840.113556.1.4.2239';

    /**
     * Used to enforce password policy requirements when resetting a password in AD (2008R2 or lower)
     *
     * @see https://msdn.microsoft.com/en-us/library/jj216527.aspx
     */
    const PasswordPolicyHintsDeprecated = '1.2.840.113556.1.4.2066';
}
