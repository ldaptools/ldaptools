<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Security;

use LdapTools\Utilities\LdapUtilities;

/**
 * Represents a SID structure.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class SID
{
    /**
     * Well-known SIDs whose values are always the same.
     *
     * @see https://support.microsoft.com/en-us/kb/243330
     */
    const WELL_KNOWN = [
        'NULL' => 'S-1-0-0',
        'WORLD_AUTHORITY' => 'S-1-1',
        'EVERYONE' => 'S-1-1-0',
        'LOCAL_AUTHORITY' => 'S-1-2',
        'LOCAL' => 'S-1-2-0',
        'CONSOLE_LOGON' => 'S-1-2-1',
        'CREATOR_AUTHORITY' => 'S-1-3',
        'CREATOR_OWNER' => 'S-1-3-0',
        'CREATOR_GROUP' => 'S-1-3-1',
        'CREATOR_OWNER_SERVER' => 'S-1-3-2',
        'CREATOR_GROUP_SERVER' => 'S-1-3-3',
        'CREATOR_OWNER_RIGHTS' => 'S-1-3-4',
        'ALL_SERVICES' => 'S-1-5-80-0',
        'USER_MODE_DRIVERS' => 'S-1-5-84-0-0-0-0-0',
        'NON_UNIQUE_AUTHORITY' => 'S-1-4',
        'NT_AUTHORITY' => 'S-1-5',
        'DIALUP' => 'S-1-5-1',
        'NETWORK' => 'S-1-5-2',
        'BATCH' => 'S-1-5-3',
        'INTERACTIVE' => 'S-1-5-4',
        'SERVICE' => 'S-1-5-6',
        'ANONYMOUS' => 'S-1-5-7',
        'PROXY' => 'S-1-5-8',
        'ENTERPRISE_DOMAIN_CONTROLLERS' => 'S-1-5-9',
        'PRINCIPAL_SELF' => 'S-1-5-10',
        'AUTHENTICATED_USERS' => 'S-1-5-11',
        'RESTRICTED_CODE' => 'S-1-5-12',
        'WRITE_RESTRICTED_CODE' => 'S-1-5-33',
        'TERMINAL_SERVER_USERS' => 'S-1-5-13',
        'INTERACTIVE_LOGON' => 'S-1-5-14',
        'ORGANIZATION' => 'S-1-5-15',
        'ORGANIZATION_IIS' => 'S-1-5-17',
        'LOCAL_SYSTEM' => 'S-1-5-18',
        'NT_AUTHORITY_LOCAL' => 'S-1-5-19',
        'NT_AUTHORITY_NETWORK' => 'S-1-5-20',
        'ADMINISTRATORS' => 'S-1-5-32-544',
        'USERS' => 'S-1-5-32-545',
        'GUESTS' => 'S-1-5-32-546',
        'POWER_USERS' => 'S-1-5-32-547',
        'ACCOUNT_OPERATORS' => 'S-1-5-32-548',
        'SERVER_OPERATORS' => 'S-1-5-32-549',
        'PRINT_OPERATORS' => 'S-1-5-32-550',
        'BACKUP_OPERATORS' => 'S-1-5-32-551',
        'REPLICATORS' => 'S-1-5-32-552',
        'NTLM_AUTHENTICATION' => 'S-1-5-64-10',
        'SCHANNEL_AUTHENTICATION' => 'S-1-5-64-14',
        'DIGEST_AUTHENTICATION' => 'S-1-5-64-21',
        'NT_SERVICE' => 'S-1-5-80',
        'NT_SERVICE_ALL' => 'S-1-5-80-0',
        'NT_VM' => 'S-1-5-83-0',
        'ALL_APP_PACKAGES' => 'S-1-15-2-1',
        'UNTRUSTED_MANDATORY_LEVEL' => 'S-1-16-0',
        'LOW_MANDATORY_LEVEL' => 'S-1-16-4096',
        'MEDIUM_MANDATORY_LEVEL' => 'S-1-16-8192',
        'MEDIUM_PLUS_MANDATORY_LEVEL' => 'S-1-16-8448',
        'HIGH_MANDATORY_LEVEL' => 'S-1-16-12288',
        'SYSTEM_MANDATORY_LEVEL' => 'S-1-16-16384',
        'PROTECTED_PROCESS_MANDATORY_LEVEL' => 'S-1-16-20480',
        'SECURE_PROCESS_MANDATORY_LEVEL' => 'S-1-16-28672',
        'BI_PRE2K_COMPATIBLE' => 'S-1-5-32-554',
        'BI_RDS_USERS' => 'S-1-5-32-555',
        'BI_NET_CFG_OPERATORS' => 'S-1-5-32-556',
        'BI_INC_FT_BUILDERS' => 'S-1-5-32-557',
        'BI_PERF_MON_USERS' => 'S-1-5-32-558',
        'BI_PERF_LOG_USERS' => 'S-1-5-32-559',
        'BI_WIN_AUTH_AG' => 'S-1-5-32-560',
        'BI_TS_LIC_SERVERS' => 'S-1-5-32-561',
        'BI_DIST_COM_USERS' => ' S-1-5-32-562',
        'BI_CERT_DCOM_USERS' => 'S-1-5-32-574',
        'BI_IIS_USERS' => 'S-1-5-32-568',
        'BI_CRYPTO_OPERATORS' => 'S-1-5-32-569',
        'BI_EVENT_LOG_READERS' => 'S-1-5-32-573',
        'BI_RDS_REMOTE_ACCESS_SERVERS' => ' S-1-5-32-575',
        'BI_RDS_ENPOINT_SERVERS' => 'S-1-5-32-576',
        'BI_RDS_MGMT_SERVERS' => 'S-1-5-32-577',
        'BI_HYPERV_ADMINS' => 'S-1-5-32-578',
        'BI_ACCESS_CONTROL_ASSISTANCE_OPS' => 'S-1-5-32-579',
        'BI_REMOTE_MANAGEMENT_USERS' => 'S-1-5-32-580',
    ];

    /**
     * Well known SID short names that do not require the domain SID to complete.
     */
    const SHORT_NAME = [
        'AA' => SID::WELL_KNOWN['BI_ACCESS_CONTROL_ASSISTANCE_OPS'],
        'AN' => SID::WELL_KNOWN['ANONYMOUS'],
        'AO' => SID::WELL_KNOWN['ACCOUNT_OPERATORS'],
        'AC' => SID::WELL_KNOWN['ALL_APP_PACKAGES'],
        'AU' => SID::WELL_KNOWN['AUTHENTICATED_USERS'],
        'BA' => SID::WELL_KNOWN['ADMINISTRATORS'],
        'BG' => SID::WELL_KNOWN['GUESTS'],
        'BO' => SID::WELL_KNOWN['BACKUP_OPERATORS'],
        'BU' => SID::WELL_KNOWN['USERS'],
        'CD' => SID::WELL_KNOWN['BI_CERT_DCOM_USERS'],
        'CG' => SID::WELL_KNOWN['CREATOR_GROUP'],
        'CO' => SID::WELL_KNOWN['CREATOR_OWNER'],
        'CY' => SID::WELL_KNOWN['BI_CRYPTO_OPERATORS'],
        'ED' => SID::WELL_KNOWN['ENTERPRISE_DOMAIN_CONTROLLERS'],
        'ER' => SID::WELL_KNOWN['BI_EVENT_LOG_READERS'],
        'ES' => SID::WELL_KNOWN['BI_RDS_ENPOINT_SERVERS'],
        'HA' => SID::WELL_KNOWN['BI_HYPERV_ADMINS'],
        'HI' => SID::WELL_KNOWN['HIGH_MANDATORY_LEVEL'],
        'IU' => SID::WELL_KNOWN['INTERACTIVE_LOGON'],
        'IS' => SID::WELL_KNOWN['BI_IIS_USERS'],
        'LS' => SID::WELL_KNOWN['NT_AUTHORITY_LOCAL'],
        'LU' => SID::WELL_KNOWN['BI_PERF_LOG_USERS'],
        'LW' => SID::WELL_KNOWN['LOW_MANDATORY_LEVEL'],
        'ME' => SID::WELL_KNOWN['MEDIUM_MANDATORY_LEVEL'],
        'MP' => SID::WELL_KNOWN['MEDIUM_PLUS_MANDATORY_LEVEL'],
        'MS' => SID::WELL_KNOWN['BI_RDS_MGMT_SERVERS'],
        'MU' => SID::WELL_KNOWN['BI_PERF_MON_USERS'],
        'NO' => SID::WELL_KNOWN['BI_NET_CFG_OPERATORS'],
        'NS' => SID::WELL_KNOWN['NT_AUTHORITY_NETWORK'],
        'NU' => SID::WELL_KNOWN['NETWORK'],
        'OW' => SID::WELL_KNOWN['CREATOR_OWNER_RIGHTS'],
        'PO' => SID::WELL_KNOWN['PRINT_OPERATORS'],
        'PS' => SID::WELL_KNOWN['PRINCIPAL_SELF'],
        'PU' => SID::WELL_KNOWN['POWER_USERS'],
        'RA' => SID::WELL_KNOWN['BI_RDS_REMOTE_ACCESS_SERVERS'],
        'RC' => SID::WELL_KNOWN['RESTRICTED_CODE'],
        'RD' => SID::WELL_KNOWN['BI_RDS_USERS'],
        'RE' => SID::WELL_KNOWN['REPLICATORS'],
        'RM' => SID::WELL_KNOWN['BI_REMOTE_MANAGEMENT_USERS'],
        'RU' => SID::WELL_KNOWN['BI_PRE2K_COMPATIBLE'],
        'SI' => SID::WELL_KNOWN['SYSTEM_MANDATORY_LEVEL'],
        'SO' => SID::WELL_KNOWN['SERVER_OPERATORS'],
        'SU' => SID::WELL_KNOWN['SERVICE'],
        'SY' => SID::WELL_KNOWN['LOCAL_SYSTEM'],
        'UD' => SID::WELL_KNOWN['USER_MODE_DRIVERS'],
        'WD' => SID::WELL_KNOWN['EVERYONE'],
        'WR' => SID::WELL_KNOWN['WRITE_RESTRICTED_CODE'],
    ];

    /**
     * Well known SID short names that require the domain SID to complete.
     */
    const SHORT_NAME_DOMAIN = [
        'CA' => 'S-1-5-21-{domainsid}-517',
        'CN' => 'S-1-5-21-{domainsid}-522',
        'DA' => 'S-1-5-21-{domainsid}-512',
        'DC' => 'S-1-5-21-{domainsid}-515',
        'DD' => 'S-1-5-21-{domainsid}-516',
        'DG' => 'S-1-5-21-{domainsid}-514',
        'DU' => 'S-1-5-21-{domainsid}-513',
        'LA' => 'S-1-5-21-{domainsid}-500',
        'LG' => 'S-1-5-21-{domainsid}-501',
        'PA' => 'S-1-5-21-{domainsid}-520',
        'RS' => 'S-1-5-21-{domainsid}-553',
    ];

    /**
     * Well known SID SDDL short names that require the root domain SID to complete.
     *
     * Several MS docs seem wrong on the Enterprise Read-Only DC group (RO). It marks it as using the domain SID, but
     * the group only exists in the root domain. Leaving this here unless shown otherwise.
     */
    const SHORT_NAME_ROOT_DOMAIN = [
        'EA' => 'S-1-5-21-{domainsid}-519',
        'RO' => 'S-1-5-21-{domainsid}-498',
        'SA' => 'S-1-5-21-{domainsid}-518',
    ];

    /**
     * @var int The revision level of the SID.
     */
    protected $revisionLevel;

    /**
     * @var int The value that indicates the authority under which the SID was created.
     */
    protected $identifierAuthority;

    /**
     * @var int[] Sub-authority values that uniquely identify a principal relative to the identifier authority.
     */
    protected $subAuthorities = [];

    /**
     * @param string $sid The SID in string, short name, or binary form.
     */
    public function __construct($sid)
    {
        if (LdapUtilities::isValidSid($sid)) {
            $this->decodeFromString($sid);
        } elseif (array_key_exists(strtoupper($sid), self::SHORT_NAME)) {
            $this->decodeFromString(self::SHORT_NAME[strtoupper($sid)]);
        } else {
            $this->decodeFromBinary($sid);
        }
    }

    /**
     * Get the SID in binary string form.
     *
     * @return string
     */
    public function toBinary()
    {
        return pack(
            'C2xxNV*',
            $this->revisionLevel,
            count($this->subAuthorities),
            $this->identifierAuthority,
            ...$this->subAuthorities
        );
    }

    /**
     * Get the SID in its friendly string form.
     *
     * @return string
     */
    public function toString()
    {
        return 'S-'.$this->revisionLevel.'-'.$this->identifierAuthority.implode(
            preg_filter('/^/', '-', $this->subAuthorities)
        );
    }

    /**
     * Get the revision level of the SID.
     *
     * @return int
     */
    public function getRevisionLevel()
    {
        return $this->revisionLevel;
    }

    /**
     * Get the value that indicates the authority under which the SID was created.
     *
     * @return int
     */
    public function getIdentifierAuthority()
    {
        return $this->identifierAuthority;
    }

    /**
     * Get the array of sub-authority values that uniquely identify a principal relative to the identifier authority.
     *
     * @return int[]
     */
    public function getSubAuthorities()
    {
        return $this->subAuthorities;
    }

    /**
     * The number of elements in the sub-authority array.
     *
     * @return int
     */
    public function getSubAuthorityCount()
    {
        return count($this->subAuthorities);
    }

    /**
     * Get the SDDL short name for the SID, if it has one. If it does not this will return null.
     *
     * @return string|null
     */
    public function getShortName()
    {
        $sid = $this->toString();
        $rid = preg_match('/^S-1-5-21(-\d+){2,}/', $sid) ? (string) array_slice($this->subAuthorities, -1)[0] : null;
        $domainSid = "S-1-5-21-{domainsid}-$rid";

        $shortName = null;
        if (in_array($sid, self::SHORT_NAME)) {
            $shortName = array_search($sid, self::SHORT_NAME);
        } elseif ($rid !== null && in_array($domainSid, self::SHORT_NAME_DOMAIN)) {
            $shortName = array_search($domainSid, self::SHORT_NAME_DOMAIN);
        } elseif ($rid !== null && in_array($domainSid, self::SHORT_NAME_ROOT_DOMAIN)) {
            $shortName = array_search($domainSid, self::SHORT_NAME_ROOT_DOMAIN);
        }

        return $shortName;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Parse the binary form of a SID into its respective parts that make it up.
     *
     * @param string $value
     */
    protected function decodeFromBinary($value)
    {
        $sid = @unpack('C1rev/C1count/x2/N1id', $value);
        if (!isset($sid['id']) || !isset($sid['rev'])) {
            throw new \UnexpectedValueException(
                'The revision level or identifier authority was not found when decoding the SID.'
            );
        }

        $this->revisionLevel = $sid['rev'];
        $this->identifierAuthority = $sid['id'];
        $subs = isset($sid['count']) ? $sid['count'] : 0;

        // The sub-authorities depend on the count, so only get as many as the count, regardless of data beyond it
        for ($i = 0; $i < $subs; $i++) {
            $this->subAuthorities[] = unpack('V1sub', hex2bin(substr(bin2hex($value), 16 + ($i * 8), 8)))['sub'];
        }
    }

    /**
     * @param string $value
     */
    protected function decodeFromString($value)
    {
        $sid = explode('-', ltrim($value, 'S-'));

        $this->revisionLevel = (int) array_shift($sid);
        $this->identifierAuthority = (int) array_shift($sid);
        $this->subAuthorities = array_map('intval', $sid);
    }
}
