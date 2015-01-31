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
     * The User Account Control attribute.
     */
    const ATTR_UAC = 'userAccountControl';

    /**
     * The attribute that specifies the time at which the account was locked.
     */
    const ATTR_LOCKOUT_TIME = 'lockoutTime';

    /**
     * The attribute that specifies the time at which the password was list set.
     */
    const ATTR_PASSWORD_LAST_SET = 'pwdLastSet';

    /**
     * The attribute for group membership of an object.
     */
    const ATTR_MEMBER_OF = 'memberOf';

    /**
     * The attribute for members belonging to an object.
     */
    const ATTR_MEMBER = 'member';

    /**
     * Checks for disabled accounts via a bitwise AND comparison on userAccountControl.
     *
     * @return \LdapTools\Query\Operator\MatchingRule
     */
    public function accountIsDisabled()
    {
        return $this->bitwiseAnd(self::ATTR_UAC, UserAccountControlFlags::DISABLED);
    }

    /**
     * Checks for locked accounts via a comparison on the lockoutTime attribute.
     *
     * @return \LdapTools\Query\Operator\Comparison
     */
    public function accountIsLocked()
    {
        return $this->gte(self::ATTR_LOCKOUT_TIME, 1);
    }

    /**
     * Checks for accounts where the password never expires via a bitwise AND comparison on userAccountControl.
     *
     * @return \LdapTools\Query\Operator\MatchingRule
     */
    public function passwordNeverExpires()
    {
        return $this->bitwiseAnd(self::ATTR_UAC, UserAccountControlFlags::PASSWORD_NEVER_EXPIRES);
    }

    /**
     * Check for accounts where they must change their password on the next logon.
     *
     * @return \LdapTools\Query\Operator\Comparison
     */
    public function passwordMustChange()
    {
        return $this->eq(self::ATTR_PASSWORD_LAST_SET, 0);
    }

    /**
     * Performs a recursive search of group membership to determine if the account belongs to it.
     *
     * @param string $groupDn The full distinguished name of the group.
     * @return MatchingRule
     */
    public function isRecursivelyMemberOf($groupDn)
    {
        return new MatchingRule(self::ATTR_MEMBER_OF, MatchingRuleOid::IN_CHAIN, $groupDn);
    }

    /**
     * Performs a recursive search of members in a group to see if the account is one of them.
     *
     * @param string $objectDn The full distinguished name of the account.
     * @return MatchingRule
     */
    public function hasMemberRecursively($objectDn)
    {
        return new MatchingRule(self::ATTR_MEMBER, MatchingRuleOid::IN_CHAIN, $objectDn);
    }
}
