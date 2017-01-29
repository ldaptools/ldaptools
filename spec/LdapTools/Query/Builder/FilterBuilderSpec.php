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
use LdapTools\Query\Operator\Comparison;
use PhpSpec\ObjectBehavior;

class FilterBuilderSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Query\Builder\FilterBuilder');
    }

    function it_should_get_an_instance_through_the_factory_method(LdapConnectionInterface $connection)
    {
        $connection->getConfig()->willReturn((new DomainConfiguration('foo.bar'))->setLdapType('openldap'));
        $this::getInstance()->shouldReturnAnInstanceOf('LdapTools\Query\Builder\FilterBuilder');
        $this::getInstance($connection)->shouldReturnAnInstanceOf('LdapTools\Query\Builder\FilterBuilder');
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

    function it_should_return_bAnd_when_calling_lt()
    {
        $this->lt('foo', '5')->shouldReturnAnInstanceOf('LdapTools\Query\Operator\bAnd');
    }

    function it_should_return_bAnd_when_calling_gt()
    {
        $this->gt('foo', '5')->shouldReturnAnInstanceOf('LdapTools\Query\Operator\bAnd');
    }

    function it_should_correctly_format_a_less_than_filter()
    {
        $this->lt('foo', '5')->toLdapFilter()->shouldBeEqualTo('(&(!(foo>=5))(foo=*))');
    }

    function it_should_correctly_format_a_greater_than_filter()
    {
        $this->gt('foo', '5')->toLdapFilter()->shouldBeEqualTo('(&(!(foo<=5))(foo=*))');
    }

    function it_should_correctly_format_a_generic_matching_rule()
    {
        $this->match('foo', 'caseExact', 'bar')->toLdapFilter()->shouldBeEqualTo('(foo:caseExact:=bar)');
        $this->match(null, '2.4.8.10', 'America', true)->toLdapFilter()->shouldBeEqualTo('(:dn:2.4.8.10:=America)');
    }

    function it_should_correctly_format_a_match_dn_filter()
    {
        $this->matchDn('ou', 'Sales')->toLdapFilter()->shouldBeEqualTo('(ou:dn:=Sales)');
    }

    function it_should_correctly_format_an_in_filter()
    {
        $this->in('id', [1, 2, 3, 4, 5])->toLdapFilter()->shouldBeEqualTo('(|(id=1)(id=2)(id=3)(id=4)(id=5))');
    }
}
