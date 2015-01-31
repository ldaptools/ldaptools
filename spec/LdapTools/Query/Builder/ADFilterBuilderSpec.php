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
}
