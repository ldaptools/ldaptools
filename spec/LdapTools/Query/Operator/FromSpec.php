<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Query\Operator;

use LdapTools\Exception\LdapQueryException;
use LdapTools\Query\Operator\bAnd;
use LdapTools\Query\Operator\Comparison;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class FromSpec extends ObjectBehavior
{
    function let()
    {
        $operator = new Comparison('objectClass', Comparison::EQ, 'user');
        $this->beConstructedWith($operator);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Query\Operator\From');
    }

    function it_should_implement_ContainsOperatorsInferface()
    {
        $this->shouldImplement('\LdapTools\Query\Operator\ContainsOperatorsInterface');
    }

    function it_should_return_one_child_when_calling_getChildren()
    {
        $this->getChildren()->shouldHaveCount(1);
    }

    function it_should_return_correct_child_count_after_adding_operators_to_it()
    {
        $this->add(new bAnd());
        $this->getChildren()->shouldHaveCount(2);
    }

    function it_should_return_no_operator_when_calling_getOperator()
    {
        $this->getOperatorSymbol()->shouldBeEqualTo('');
    }

    function it_should_return_no_attribute_when_calling_getAttribute()
    {
        $this->getAttribute()->shouldBeEqualTo('');
    }

    function it_should_return_no_attribute_when_calling_getTranslatedAttribute()
    {
        $this->getTranslatedAttribute()->shouldBeEqualTo('');
    }

    function it_should_return_the_correct_ldap_filter_with_one_operator()
    {
        $this->getLdapFilter()->shouldBeEqualTo('(objectClass=\75\73\65\72)');
    }

    function it_should_return_the_correct_ldap_filter_with_two_operators()
    {
        $this->add(new Comparison('objectClass', Comparison::EQ, 'group'));
        $this->getLdapFilter()->shouldBeEqualTo('(|(objectClass=\75\73\65\72)(objectClass=\67\72\6f\75\70))');
    }

    function it_should_throw_LdapQueryException_when_trying_to_set_the_operator_to_an_invalid_type()
    {
        $ex = new LdapQueryException('Invalid operator symbol "=". Valid operator symbols are: |, &');
        $this->shouldThrow($ex)->duringSetOperatorSymbol('=');
    }
}
