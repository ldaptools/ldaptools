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
use LdapTools\Query\MatchingRuleOid;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class MatchingRuleSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('foo', MatchingRuleOid::BIT_AND, '2');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Query\Operator\MatchingRule');
    }

    function it_should_have_a_symbol_constant()
    {
        $this->shouldHaveConstant('SYMBOL');
    }

    function it_should_return_foo_when_calling_getAttribute()
    {
        $this->getAttribute()->shouldBeEqualTo('foo');
    }

    function it_should_return_2_when_calling_getValue()
    {
        $this->getValue()->shouldBeEqualTo('2');
    }

    function it_should_return_the_correct_ldap_bitwise_and_filter()
    {
        $this->getLdapFilter()->shouldBeEqualTo('(foo:1.2.840.113556.1.4.803:=2)');
    }

    function it_should_return_the_correct_ldap_bitwise_or_filter()
    {
        $this->beConstructedWith('foo', MatchingRuleOid::BIT_OR, 2);
        $this->getLdapFilter()->shouldBeEqualTo('(foo:1.2.840.113556.1.4.804:=2)');
    }

    function it_should_throw_LdapQueryException_when_trying_to_set_the_operator_to_an_invalid_type()
    {
        $ex = new LdapQueryException('Invalid operator symbol ">=". Valid operator symbols are: =');
        $this->shouldThrow($ex)->duringSetOperatorSymbol('>=');
    }

    function it_should_throw_a_LdapQueryException_on_an_invalid_oid()
    {
        $this->beConstructedWith('foo', 'foo=bar)(', 2);
        $this->shouldThrow('\LdapTools\Exception\LdapQueryException')->duringGetLdapFilter();
    }

    function it_should_accept_text_as_an_oid()
    {
        $this->beConstructedWith('foo', 'FooBarMatch', 2);
        $this->shouldNotThrow('\LdapTools\Exception\LdapQueryException')->duringGetLdapFilter();
    }

    function it_should_escape_special_characters_when_going_to_ldap()
    {
        $this->beConstructedWith('foo', MatchingRuleOid::BIT_OR, '\*)3');
        $this->getLdapFilter()->shouldBeEqualTo('(foo:1.2.840.113556.1.4.804:=\5c\2a\293)');
    }

    public function getMatchers()
    {
        return [
            'haveConstant' => function($subject, $constant) {
                return defined('\LdapTools\Query\Operator\MatchingRule::'.$constant);
            }
        ];
    }
}
