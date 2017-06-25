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

use LdapTools\Enums\MatchingRuleOid;
use LdapTools\Query\Operator\MatchingRule;

/**
 * Active Directory specific filter builder helpers.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ADFilterBuilder extends FilterBuilder
{
    /**
     * Checks for the existence of an attribute that should only be set on mail-enabled objects.
     *
     * @return \LdapTools\Query\Operator\Wildcard
     */
    public function mailEnabled()
    {
        return $this->present('proxyAddresses');
    }

    /**
     * Performs a recursive search of group membership to determine if the account belongs to it. If you are not using a
     * schema and want to use this function you should pass 'memberOf' as the second argument.
     *
     * @param string $group The name, GUID, SID, LdapObject or DN of a group
     * @param string $attribute The attribute to query against. Defaults to 'groups'.
     * @return MatchingRule
     */
    public function isRecursivelyMemberOf($group, $attribute = 'groups')
    {
        return new MatchingRule($attribute, MatchingRuleOid::InChain, $group);
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
        return new MatchingRule($attribute, MatchingRuleOid::InChain, $value);
    }
}
