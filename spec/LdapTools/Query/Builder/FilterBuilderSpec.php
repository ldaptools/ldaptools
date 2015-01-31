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

use LdapTools\Query\Operator\Comparison;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class FilterBuilderSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Query\Builder\FilterBuilder');
    }

    function it_should_return_bAnd_when_calling_bAnd()
    {
        $this->bAnd()->shouldReturnAnInstanceOf('LdapTools\Query\Operator\bAnd');
    }

    function it_should_return_bOr_when_calling_bOr()
    {
        $this->bOr()->shouldReturnAnInstanceOf('LdapTools\Query\Operator\bOr');
    }

    function it_should_return_bOr_when_calling_bNot()
    {
        $this->bNot(new Comparison('foo', Comparison::EQ, 'bar'))->shouldReturnAnInstanceOf('LdapTools\Query\Operator\bNot');
    }

    function it_should_return_bNot_when_calling_neq()
    {
        $this->neq('foo', 'bar')->shouldReturnAnInstanceOf('LdapTools\Query\Operator\bNot');
    }

    function it_should_return_a_comparison_when_calling_eq()
    {
        $this->eq('foo', 'bar')->shouldReturnAnInstanceOf('LdapTools\Query\Operator\Comparison');
    }

    function it_should_return_a_comparison_when_calling_gte()
    {
        $this->gte('foo', 'bar')->shouldReturnAnInstanceOf('LdapTools\Query\Operator\Comparison');
    }

    function it_should_return_a_comparison_when_calling_lte()
    {
        $this->lte('foo', 'bar')->shouldReturnAnInstanceOf('LdapTools\Query\Operator\Comparison');
    }

    function it_should_return_a_comparison_when_calling_aeq()
    {
        $this->aeq('foo', 'bar')->shouldReturnAnInstanceOf('LdapTools\Query\Operator\Comparison');
    }

    function it_should_return_a_wildcard_when_calling_startsWith()
    {
        $this->startsWith('foo', 'bar')->shouldReturnAnInstanceOf('LdapTools\Query\Operator\Wildcard');
    }

    function it_should_return_a_wildcard_when_calling_endsWith()
    {
        $this->endsWith('foo', 'bar')->shouldReturnAnInstanceOf('LdapTools\Query\Operator\Wildcard');
    }

    function it_should_return_a_wildcard_when_calling_contains()
    {
        $this->contains('foo', 'bar')->shouldReturnAnInstanceOf('LdapTools\Query\Operator\Wildcard');
    }

    function it_should_return_a_wildcard_when_calling_like()
    {
        $this->like('foo', 'b*a*r')->shouldReturnAnInstanceOf('LdapTools\Query\Operator\Wildcard');
    }

    function it_should_return_a_wildcard_when_calling_present()
    {
        $this->present('foo')->shouldReturnAnInstanceOf('LdapTools\Query\Operator\Wildcard');
    }

    function it_should_return_bNot_when_calling_notPresent()
    {
        $this->notPresent('bar')->shouldReturnAnInstanceOf('LdapTools\Query\Operator\bNot');
    }

    function it_should_return_MatchingRule_when_calling_bitwiseAnd()
    {
        $this->bitwiseAnd('bar', 2)->shouldReturnAnInstanceOf('LdapTools\Query\Operator\MatchingRule');
    }

    function it_should_return_MatchingRule_when_calling_bitwiseOr()
    {
        $this->bitwiseOr('bar', 2)->shouldReturnAnInstanceOf('LdapTools\Query\Operator\MatchingRule');
    }
}
