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

use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\DomainConfiguration;
use LdapTools\Query\GroupTypeFlags;
use PhpSpec\ObjectBehavior;

class ADFilterBuilderSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Query\Builder\ADFilterBuilder');
    }

    function it_should_get_an_instance_through_the_factory_method(LdapConnectionInterface $connection)
    {
        $connection->getConfig()->willReturn((new DomainConfiguration('foo.bar'))->setLdapType('ad'));
        $this::getInstance($connection)->shouldReturnAnInstanceOf('LdapTools\Query\Builder\ADFilterBuilder');
    }

    function it_should_be_an_instance_of_FilterBuilder()
    {
        $this->shouldReturnAnInstanceOf('\LdapTools\Query\Builder\FilterBuilder');
    }

    function it_should_return_MatchingRule_when_calling_isRecursivelyMemberOf()
    {
        $this->isRecursivelyMemberOf('cn=foo,dc=bar,dc=foo')->shouldReturnAnInstanceOf('LdapTools\Query\Operator\MatchingRule');
    }

    function it_should_return_MatchingRule_when_calling_hasMemberRecursively()
    {
        $this->hasMemberRecursively('cn=foo,dc=bar,dc=foo')->shouldReturnAnInstanceOf('LdapTools\Query\Operator\MatchingRule');
    }

    function it_should_use_the_members_attribute_by_default_for_hasMemberRecursively()
    {
        $this->hasMemberRecursively('foo')->getAttribute()->shouldBeEqualTo('members');
    }

    function it_should_use_the_specified_attribute_when_requested_for_hasMemberRecursively()
    {
        $this->hasMemberRecursively('foo','users')->getAttribute()->shouldBeEqualTo('users');
    }

    function it_should_return_Wildcard_when_calling_mailEnabled()
    {
        $this->mailEnabled()->shouldReturnAnInstanceOf('LdapTools\Query\Operator\Wildcard');
    }

    function it_should_use_the_groups_attribute_by_default_for_isRecursivelyMemberOf()
    {
        $this->isRecursivelyMemberOf('foo')->getAttribute()->shouldBeEqualTo('groups');
    }

    function it_should_use_the_memberOf_attribute_if_specified_for_isRecursivelyMemberOf()
    {
        $this->isRecursivelyMemberOf('foo', 'memberOf')->getAttribute()->shouldBeEqualTo('memberOf');
    }
}
