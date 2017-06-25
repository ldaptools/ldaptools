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

use Enums\FlagEnumInterface;
use Enums\FlagEnumTrait;

/**
 * The possible flags that can be assigned to the userAccountControl attribute. Taken mostly verbatim from available
 * online documentation.
 *
 * @link https://support.microsoft.com/kb/305144/
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class UserAccountControl implements FlagEnumInterface
{
    use FlagEnumTrait;

    /**
     * The logon script is run.
     */
    const RunLogonScript = 1;

    /**
     * The user account is disabled.
     */
    const Disabled = 2;

    /**
     * The home folder is required.
     */
    const HomeDirectoryRequired = 8;

    /**
     * The account is currently locked out.
     */
    const Locked = 16;

    /**
     * A password is not required.
     */
    const PasswordNotRequired = 32;

    /**
     * The user cannot change their password.
     */
    const PasswordCantChange = 64;

    /**
     * The user can send an encrypted password.
     */
    const EncryptedTextPwdAllowed = 128;

    /**
     * This is an account whose primary account is in another domain.
     */
    const TempDuplicateAccount = 256;

    /**
     * Defines a "normal" user account.
     */
    const NormalAccount = 512;

    /**
     * This is a permit to trust an account for a system domain that trusts other domains.
     */
    const InterdomainTrustAccount = 2048;

    /**
     * This is a computer account for a computer that is running Microsoft Windows NT 4.0 Workstation, Microsoft
     * Windows NT 4.0 Server, Microsoft Windows 2000 Professional, or Windows 2000 Server and is a member of this
     * domain.
     */
    const WorkstationTrustAccount = 4096;

    /**
     * This is a computer account for a domain controller that is a member of this domain.
     */
    const ServerTrustAccount = 8192;

    /**
     * The password on the account should not expire.
     */
    const PasswordNeverExpires = 65536;

    /**
     * This is a MNS logon account.
     */
    const MsnLoginAccount = 131072;

    /**
     * The logon requires a smartcard.
     */
    const SmartCardRequired = 262144;

    /**
     * The account is trusted for kerberos delegation.
     */
    const TrustedForDelegation = 524288;

    /**
     * The security context of the user is not delegated to a service even if the service account is set as trusted for
     * kerberos delegation.
     */
    const NotDelegated = 1048576;

    /**
     * Restrict this principal to use only DES encryption types for keys.
     */
    const UseDESKeyOnly = 2097152;

    /**
     * This account does not require Kerberos pre-authentication for logging on.
     */
    const PreAuthNotRequired = 4194304;

    /**
     * The password has expired.
     */
    const PasswordExpired = 8388608;

    /**
     * The account is enabled for delegation.
     */
    const TrustedToAuthForDelegation = 16777216;

    /**
     * The account is a Read-Only Domain Controller.
     */
    const PartialSecretsAccount = 67108864;
}
