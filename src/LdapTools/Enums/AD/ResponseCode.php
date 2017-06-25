<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Enums\AD;

use Enums\SimpleEnumInterface;
use Enums\SimpleEnumTrait;

/**
 * Possible Active Directory response codes.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ResponseCode implements SimpleEnumInterface
{
    use SimpleEnumTrait;

    /**
     * The account does not exist in the directory.
     */
    const AccountInvalid = 1317;

    /**
     * The account is not in the group.
     */
    const MemberNotInGroup = 1321;

    /**
     * Unable to update password because the current password supplied is incorrect.
     */
    const CurrentPasswordIncorrect = 1323;

    /**
     * Unable to update the password because it contains characters not allowed in passwords.
     */
    const PasswordMalformed = 1324;

    /**
     * Unable to update the password because it does not meet length, complexity, or history requirements on the domain.
     */
    const PasswordRestrictions = 1325;

    /**
     * The accounts credentials are invalid.
     */
    const AccountCredentialsInvalid = 1326;

    /**
     * Account restrictions are preventing a login.
     */
    const AccountRestrictions = 1327;

    /**
     * Time restrictions caused by specified log-on hours.
     */
    const AccountRestrictionsTime = 1328;

    /**
     * Device restrictions caused by "log-on to workstations...".
     */
    const AccountRestrictionsDevice = 1329;

    /**
     * The account password has expired. Not sure of the technical difference between this and 1907.
     */
    const AccountPasswordExpired = 1330;

    /**
     * The account is disabled.
     */
    const AccountDisabled = 1331;

    /**
     * Occurs when a user is a member of too many groups.
     *
     * @see http://support.microsoft.com/en-us/kb/328889
     */
    const AccountContextIDS = 1384;

    /**
     * The account has expired (accountExpires attribute).
     */
    const AccountExpired = 1793;

    /**
     * The accounts password must change before it can login.
     */
    const AccountPasswordMustChange = 1907;

    /**
     * The account is locked out.
     */
    const AccountLocked = 1909;
}
