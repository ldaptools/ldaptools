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
 * Maps AD extended response codes to their meanings.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ADResponseCodes
{
    /**
     * The account does not exist in the directory.
     */
    const ACCOUNT_INVALID = 1317;

    /**
     * The account is not in the group.
     */
    const MEMBER_NOT_IN_GROUP = 1321;

    /**
     * Unable to update password because the current password supplied is incorrect.
     */
    const CURRENT_PASSWORD_INCORRECT = 1323;

    /**
     * Unable to update the password because it contains characters not allowed in passwords.
     */
    const PASSWORD_MALFORMED = 1324;

    /**
     * Unable to update the password because it does not meet length, complexity, or history requirements on the domain.
     */
    const PASSWORD_RESTRICTIONS = 1325;

    /**
     * The accounts credentials are invalid.
     */
    const ACCOUNT_CREDENTIALS_INVALID = 1326;

    /**
     * Account restrictions are preventing a login.
     */
    const ACCOUNT_RESTRICTIONS = 1327;

    /**
     * Time restrictions caused by specified log-on hours.
     */
    const ACCOUNT_RESTRICTIONS_TIME = 1328;

    /**
     * Device restrictions caused by "log-on to workstations...".
     */
    const ACCOUNT_RESTRICTIONS_DEVICE = 1329;

    /**
     * The account password has expired. Not sure of the technical difference between this and 1907.
     */
    const ACCOUNT_PASSWORD_EXPIRED = 1330;

    /**
     * The account is disabled.
     */
    const ACCOUNT_DISABLED = 1331;

    /**
     * Occurs when a user is a member of too many groups.
     * @see http://support.microsoft.com/en-us/kb/328889
     */
    const ACCOUNT_CONTEXT_IDS = 1384;

    /**
     * The account has expired (accountExpires attribute).
     */
    const ACCOUNT_EXPIRED = 1793;

    /**
     * The accounts password must change before it can login.
     */
    const ACCOUNT_PASSWORD_MUST_CHANGE = 1907;

    /**
     * The account is locked out.
     */
    const ACCOUNT_LOCKED = 1909;

    /**
     * A helpful map to provide more useful error messages.
     */
    const RESPONSE_MESSAGE = [
        self::ACCOUNT_INVALID => 'Account does not exist.',
        self::ACCOUNT_CREDENTIALS_INVALID => 'Account password is invalid.',
        self::ACCOUNT_RESTRICTIONS => 'Account Restrictions prevent this user from signing in.',
        self::ACCOUNT_RESTRICTIONS_TIME => 'Time Restriction - The account cannot login at this time.',
        self::ACCOUNT_RESTRICTIONS_DEVICE => 'Device Restriction - The account is not allowed to log on to this computer.',
        self::ACCOUNT_PASSWORD_EXPIRED => 'The password for this account has expired.',
        self::ACCOUNT_DISABLED => 'The account is currently disabled.',
        self::ACCOUNT_CONTEXT_IDS => 'The account is a member of too many groups and cannot be logged on.',
        self::ACCOUNT_EXPIRED => 'The account has expired.',
        self::ACCOUNT_PASSWORD_MUST_CHANGE => "The account's password must change before it can login.",
        self::ACCOUNT_LOCKED => 'The account is currently locked out.',
        self::CURRENT_PASSWORD_INCORRECT => 'Unable to update the password. The value for the current password is incorrect.',
        self::PASSWORD_MALFORMED => 'Unable to update the password. The value contains characters not allowed in passwords.',
        self::PASSWORD_RESTRICTIONS => 'Unable to update the password. It does not meet the length, complexity, or history requirements for the domain.',
        self::MEMBER_NOT_IN_GROUP => 'The specified user account is not a member of the specified group account.',
    ];
}
