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
     * A helpful map to provide more useful error messages for specific response codes.
     */
    protected static $messages = [
        self::AccountInvalid => 'Account does not exist.',
        self::AccountCredentialsInvalid => 'Account password is invalid.',
        self::AccountRestrictions => 'Account Restrictions prevent this user from signing in.',
        self::AccountRestrictionsTime => 'Time Restriction - The account cannot login at this time.',
        self::AccountRestrictionsDevice => 'Device Restriction - The account is not allowed to log on to this computer.',
        self::AccountPasswordExpired => 'The password for this account has expired.',
        self::AccountDisabled => 'The account is currently disabled.',
        self::AccountContextIDS => 'The account is a member of too many groups and cannot be logged on.',
        self::AccountExpired => 'The account has expired.',
        self::AccountPasswordMustChange => "The account's password must change before it can login.",
        self::AccountLocked => 'The account is currently locked out.',
        self::CurrentPasswordIncorrect => 'Unable to update the password. The value for the current password is incorrect.',
        self::PasswordMalformed => 'Unable to update the password. The value contains characters not allowed in passwords.',
        self::PasswordRestrictions => 'Unable to update the password. It does not meet the length, complexity, or history requirements for the domain.',
        self::MemberNotInGroup => 'The specified user account is not a member of the specified group account.',
    ];

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

    /**
     * Check if an error (enum name or value) has a helpful message defined.
     *
     * @param mixed $value
     * @return bool
     */
    public static function hasMessageForError($value)
    {
        if (self::isValidName($value)) {
            $value = self::getNameValue($value);
        }

        return isset(self::$messages[$value]);
    }

    /**
     * Get the helpful error message for a specific error if it exists.
     *
     * @param mixed $value
     * @return string|null
     */
    public static function getMessageForError($value)
    {
        if (self::isValidName($value)) {
            $value = self::getNameValue($value);
        }

        return isset(self::$messages[$value]) ? self::$messages[$value] : null;
    }

    /**
     * Get the helpful error message for this response code, if any is defined.
     *
     * @return null|string
     */
    public function getMessage()
    {
        return self::getMessageForError($this->value);
    }
}
