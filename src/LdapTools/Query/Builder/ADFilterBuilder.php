<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Query\Builder;

use LdapTools\Query\GroupTypeFlags;
use LdapTools\Query\MatchingRuleOid;
use LdapTools\Query\Operator\MatchingRule;
use LdapTools\Query\UserAccountControlFlags;

/**
 * Active Directory specific filter builder helpers.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ADFilterBuilder extends FilterBuilder
{
    /**
     * Attribute mapping.
     */
    const ATTR = [
        'UAC' => 'userAccountControl',
        'LOCKOUT_TIME' => 'lockoutTime',
        'PASSWORD_LAST_SET' => 'pwdLastSet',
        'MEMBER_OF' => 'memberOf',
        'MEMBER' => 'member',
        'GROUP_TYPE' => 'groupType',
        'ACCOUNT_EXPIRES' => 'accountExpires',
        'PROXY_ADDRESSES' => 'proxyAddresses',
    ];

    /**
     * Checks for accounts that are set to expire at a certain date.
     *
     * @return \LdapTools\Query\Operator\bAnd
     */
    public function accountExpires()
    {
        return $this->bAnd(
            $this->gte(self::ATTR['ACCOUNT_EXPIRES'], '1'),
            $this->lte(self::ATTR['ACCOUNT_EXPIRES'], '9223372036854775806')
        );
    }

    /**
     * Checks for accounts that never expire.
     *
     * @return \LdapTools\Query\Operator\bOr
     */
    public function accountNeverExpires()
    {
        return $this->bOr(
            $this->eq(self::ATTR['ACCOUNT_EXPIRES'], '0'),
            $this->eq(self::ATTR['ACCOUNT_EXPIRES'], '9223372036854775807')
        );
    }

    /**
     * Checks for disabled accounts via a bitwise AND comparison on userAccountControl.
     *
     * @return \LdapTools\Query\Operator\MatchingRule
     */
    public function accountIsDisabled()
    {
        return $this->bitwiseAnd(self::ATTR['UAC'], UserAccountControlFlags::DISABLED);
    }

    /**
     * Checks for locked accounts via a comparison on the lockoutTime attribute.
     *
     * @return \LdapTools\Query\Operator\Comparison
     */
    public function accountIsLocked()
    {
        return $this->gte(self::ATTR['LOCKOUT_TIME'], 1);
    }

    /**
     * Check for a specific AD group type by its flag.
     *
     * @see \LdapTools\Query\GroupTypeFlags
     * @param int $flag A constant from GroupTypeFlags
     * @return \LdapTools\Query\Operator\MatchingRule
     */
    public function groupIsType($flag)
    {
        return $this->bitwiseAnd(self::ATTR['GROUP_TYPE'], $flag);
    }

    /**
     * Checks for groups that are security enabled.
     *
     * @return \LdapTools\Query\Operator\MatchingRule
     */
    public function groupIsSecurityEnabled()
    {
        return $this->groupIsType(GroupTypeFlags::SECURITY_ENABLED);
    }

    /**
     * Check for groups that are distribution lists.
     *
     * @return \LdapTools\Query\Operator\bNot
     */
    public function groupIsDistribution()
    {
        return $this->bNot($this->groupIsType(GroupTypeFlags::SECURITY_ENABLED));
    }

    /**
     * Checks for groups that are global in scope.
     *
     * @return \LdapTools\Query\Operator\MatchingRule
     */
    public function groupIsGlobal()
    {
        return $this->groupIsType(GroupTypeFlags::GLOBAL_GROUP);
    }

    /**
     * Checks for groups that are universal in scope.
     *
     * @return \LdapTools\Query\Operator\MatchingRule
     */
    public function groupIsUniversal()
    {
        return $this->groupIsType(GroupTypeFlags::UNIVERSAL_GROUP);
    }

    /**
     * Checks for groups that are domain local in scope.
     *
     * @return \LdapTools\Query\Operator\MatchingRule
     */
    public function groupIsDomainLocal()
    {
        return $this->groupIsType(GroupTypeFlags::DOMAIN_LOCAL_GROUP);
    }

    /**
     * Checks for the existence of an attribute that should only be set on mail-enabled objects.
     *
     * @return \LdapTools\Query\Operator\Wildcard
     */
    public function mailEnabled()
    {
        return $this->present(self::ATTR['PROXY_ADDRESSES']);
    }

    /**
     * Checks for accounts where the password never expires via a bitwise AND comparison on userAccountControl.
     *
     * @return \LdapTools\Query\Operator\MatchingRule
     */
    public function passwordNeverExpires()
    {
        return $this->bitwiseAnd(self::ATTR['UAC'], UserAccountControlFlags::PASSWORD_NEVER_EXPIRES);
    }

    /**
     * Check for accounts where they must change their password on the next logon.
     *
     * @return \LdapTools\Query\Operator\Comparison
     */
    public function passwordMustChange()
    {
        return $this->eq(self::ATTR['PASSWORD_LAST_SET'], 0);
    }

    /**
     * Performs a recursive search of group membership to determine if the account belongs to it. If you are not using a
     * schema and want to use this function you should pass false as the second argument.
     *
     * @param string $group The name, GUID, SID, LdapObject or DN of a group
     * @param bool $schema If false the 'memberOf' attribute will be used instead of the 'groups' attribute from the schema.
     * @return MatchingRule
     */
    public function isRecursivelyMemberOf($group, $schema = true)
    {
        $attribute = $schema ? 'groups' : self::ATTR['MEMBER_OF'];

        return new MatchingRule($attribute, MatchingRuleOid::IN_CHAIN, $group);
    }

    /**
     * Performs a recursive search of members in a group to see if the account is one of them.
     *
     * @param string $value A username, SID, GUID, DN or LdapObject.
     * @param string $attribute The attribute to query against. Defaults to 'members'.
     * @return MatchingRule
     */
    public function hasMemberRecursively($value, $attribute = 'members')
    {
        return new MatchingRule($attribute, MatchingRuleOid::IN_CHAIN, $value);
    }
}
