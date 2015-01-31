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

    function it_should_return_the_correct_ldap_bitwise_and_filter_on_toString()
    {
        $this->__tostring()->shouldBeEqualTo('(foo:1.2.840.113556.1.4.803:=\32)');
    }

    function it_should_return_the_correct_ldap_bitwise_or_filter_on_toString()
    {
        $this->beConstructedWith('foo', MatchingRuleOid::BIT_OR, 2);
        $this->__tostring()->shouldBeEqualTo('(foo:1.2.840.113556.1.4.804:=\32)');
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
