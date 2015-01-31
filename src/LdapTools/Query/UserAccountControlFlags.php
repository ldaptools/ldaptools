<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Query;

/**
 * The possible flags that can be assigned to the userAccountControl attribute. Taken mostly verbatim from available
 * online documentation.
 *
 * @link https://support.microsoft.com/kb/305144/
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class UserAccountControlFlags
{
    /**
     * The logon script is run.
     */
    const RUN_LOGON_SCRIPT = 1;

    /**
     * The user account is disabled.
     */
    const DISABLED = 2;

    /**
     * The home folder is required.
     */
    const HOME_DIRECTORY_REQUIRED = 8;

    /**
     * The account is currently locked out.
     */
    const LOCKED = 16;

    /**
     * A password is not required.
     */
    const PASSWORD_NOT_REQUIRED = 32;

    /**
     * The user cannot change their password.
     */
    const PASSWORD_CANT_CHANGE = 64;

    /**
     * The user can send an encrypted password.
     */
    const ENCRYPTED_TEXT_PWD_ALLOWED = 128;

    /**
     * This is an account whose primary account is in another domain.
     */
    const TEMP_DUPLICATE_ACCOUNT = 256;

    /**
     * Defines a "normal" user account.
     */
    const NORMAL_ACCOUNT = 512;

    /**
     * This is a permit to trust an account for a system domain that trusts other domains.
     */
    const INTERDOMAIN_TRUST_ACCOUNT = 2048;

    /**
     * This is a computer account for a computer that is running Microsoft Windows NT 4.0 Workstation, Microsoft
     * Windows NT 4.0 Server, Microsoft Windows 2000 Professional, or Windows 2000 Server and is a member of this
     * domain.
     */
    const WORKSTATION_TRUST_ACCOUNT = 4096;

    /**
     * This is a computer account for a domain controller that is a member of this domain.
     */
    const SERVER_TRUST_ACCOUNT = 8192;

    /**
     * The password on the account should not expire.
     */
    const PASSWORD_NEVER_EXPIRES = 65536;

    /**
     * This is a MNS logon account.
     */
    const MNS_LOGON_ACCOUNT = 131072;

    /**
     * The logon requires a smartcard.
     */
    const SMARTCARD_REQUIRED = 262144;

    /**
     * The account is trusted for kerberos delegation.
     */
    const TRUSTED_FOR_DELEGATION = 524288;

    /**
     * The security context of the user is not delegated to a service even if the service account is set as trusted for
     * kerberos delegation.
     */
    const NOT_DELEGATED = 1048576;

    /**
     * Restrict this principal to use only DES encryption types for keys.
     */
    const USE_DES_KEY_ONLY = 2097152;

    /**
     * This account does not require Kerberos pre-authentication for logging on.
     */
    const PREAUTH_NOT_REQUIRED = 4194304;

    /**
     * The password has expired.
     */
    const PASSWORD_EXPIRED = 8388608;

    /**
     * The account is enabled for delegation.
     */
    const TRUSTED_TO_AUTH_FOR_DELEGATION = 16777216;

    /**
     * The account is a Read-Only Domain Controller.
     */
    const PARTIAL_SECRETS_ACCOUNT = 67108864;
}
