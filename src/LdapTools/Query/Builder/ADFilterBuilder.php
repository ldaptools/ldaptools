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
     * Performs a recursive search of group membership to determine if the account belongs to it.
     *
     * @param string $groupDn The full distinguished name of the group.
     * @return MatchingRule
     */
    public function isRecursivelyMemberOf($groupDn)
    {
        return new MatchingRule(self::ATTR['MEMBER_OF'], MatchingRuleOid::IN_CHAIN, $groupDn);
    }

    /**
     * Performs a recursive search of members in a group to see if the account is one of them.
     *
     * @param string $objectDn The full distinguished name of the account.
     * @return MatchingRule
     */
    public function hasMemberRecursively($objectDn)
    {
        return new MatchingRule(self::ATTR['MEMBER'], MatchingRuleOid::IN_CHAIN, $objectDn);
    }
}
