<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Query\Builder;

use LdapTools\Query\GroupTypeFlags;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ADFilterBuilderSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Query\Builder\ADFilterBuilder');
    }

    function it_should_be_an_instance_of_FilterBuilder()
    {
        $this->shouldReturnAnInstanceOf('\LdapTools\Query\Builder\FilterBuilder');
    }

    function it_should_return_MatchingRule_when_calling_isDisabled()
    {
        $this->accountIsDisabled()->shouldReturnAnInstanceOf('LdapTools\Query\Operator\MatchingRule');
    }

    function it_should_return_Comparison_when_calling_isLocked()
    {
        $this->accountIsLocked()->shouldReturnAnInstanceOf('LdapTools\Query\Operator\Comparison');
    }

    function it_should_MatchingRule_when_calling_passwordNeverExpires()
    {
        $this->passwordNeverExpires()->shouldReturnAnInstanceOf('LdapTools\Query\Operator\MatchingRule');
    }

    function it_should_return_Comparison_when_calling_passwordMustChange()
    {
        $this->passwordMustChange()->shouldReturnAnInstanceOf('LdapTools\Query\Operator\Comparison');
    }

    function it_should_return_MatchingRule_when_calling_isRecursivelyMemberOf()
    {
        $this->isRecursivelyMemberOf('cn=foo,dc=bar,dc=foo')->shouldReturnAnInstanceOf('LdapTools\Query\Operator\MatchingRule');
    }

    function it_should_return_MatchingRule_when_calling_hasMemberRecursively()
    {
        $this->hasMemberRecursively('cn=foo,dc=bar,dc=foo')->shouldReturnAnInstanceOf('LdapTools\Query\Operator\MatchingRule');
    }

    function it_should_return_MatchingRule_when_calling_groupIsType()
    {
        $this->groupIsType(GroupTypeFlags::GLOBAL_GROUP)->shouldReturnAnInstanceOf('LdapTools\Query\Operator\MatchingRule');
        $this->groupIsType(GroupTypeFlags::GLOBAL_GROUP)->getAttribute()->shouldBeEqualTo('groupType');
        $this->groupIsType(GroupTypeFlags::GLOBAL_GROUP)->getValue()->shouldBeEqualTo(GroupTypeFlags::GLOBAL_GROUP);
    }

    function it_should_return_MatchingRule_when_calling_groupIsSecurityEnabled()
    {
        $this->groupIsSecurityEnabled()->shouldReturnAnInstanceOf('LdapTools\Query\Operator\MatchingRule');
        $this->groupIsSecurityEnabled()->getAttribute()->shouldBeEqualTo('groupType');
        $this->groupIsSecurityEnabled()->getValue()->shouldBeEqualTo(GroupTypeFlags::SECURITY_ENABLED);
    }

    function it_should_return_bNot_when_calling_groupIsDistribution()
    {
        $this->groupIsDistribution()->shouldReturnAnInstanceOf('LdapTools\Query\Operator\bNot');
    }

    function it_should_return_MatchingRule_when_calling_groupIsDomainLocal()
    {
        $this->groupIsDomainLocal()->shouldReturnAnInstanceOf('LdapTools\Query\Operator\MatchingRule');
        $this->groupIsDomainLocal()->getAttribute()->shouldBeEqualTo('groupType');
        $this->groupIsDomainLocal()->getValue()->shouldBeEqualTo(GroupTypeFlags::DOMAIN_LOCAL_GROUP);
    }

    function it_should_return_MatchingRule_when_calling_groupIsGlobal()
    {
        $this->groupIsGlobal()->shouldReturnAnInstanceOf('LdapTools\Query\Operator\MatchingRule');
        $this->groupIsGlobal()->getAttribute()->shouldBeEqualTo('groupType');
        $this->groupIsGlobal()->getValue()->shouldBeEqualTo(GroupTypeFlags::GLOBAL_GROUP);
    }

    function it_should_return_MatchingRule_when_calling_groupIsUniversal()
    {
        $this->groupIsUniversal()->shouldReturnAnInstanceOf('LdapTools\Query\Operator\MatchingRule');
        $this->groupIsUniversal()->getAttribute()->shouldBeEqualTo('groupType');
        $this->groupIsUniversal()->getValue()->shouldBeEqualTo(GroupTypeFlags::UNIVERSAL_GROUP);
    }

    function it_should_return_bAnd_when_calling_accountExpires()
    {
        $this->accountExpires()->shouldReturnAnInstanceOf('LdapTools\Query\Operator\bAnd');
    }

    function it_should_return_bOr_when_calling_accountNeverExpires()
    {
        $this->accountNeverExpires()->shouldReturnAnInstanceOf('LdapTools\Query\Operator\bOr');
    }
}
