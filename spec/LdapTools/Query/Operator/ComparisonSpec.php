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

use LdapTools\Query\Operator\Comparison;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ComparisonSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('foo', Comparison::EQ, 'bar');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Query\Operator\Comparison');
    }

    function it_should_have_an_equals_constant()
    {
        $this->shouldHaveConstant('EQ');
    }

    function it_should_have_an_aproximately_equals_constant()
    {
        $this->shouldHaveConstant('AEQ');
    }

    function it_should_have_a_less_than_or_equal_to_constant()
    {
        $this->shouldHaveConstant('LTE');
    }

    function it_should_have_a_greater_than_or_equal_to_constant()
    {
        $this->shouldHaveConstant('GTE');
    }

    function it_should_have_a_greater_than_or_equal_to_symbol_when_the_constant_is_used()
    {
        $this->beConstructedWith('foo', Comparison::GTE, 'bar');
        $this->getOperatorSymbol()->shouldBeEqualTo('>=');
    }

    function it_should_have_a_less_than_or_equal_to_symbol_when_the_constant_is_used()
    {
        $this->beConstructedWith('foo', Comparison::LTE, 'bar');
        $this->getOperatorSymbol()->shouldBeEqualTo('<=');
    }

    function it_should_have_an_equals_when_the_constant_is_used()
    {
        $this->beConstructedWith('foo', Comparison::EQ, 'bar');
        $this->getOperatorSymbol()->shouldBeEqualTo('=');
    }

    function it_should_have_an_approximately_equal_to_symbol_when_the_constant_is_used()
    {
        $this->beConstructedWith('foo', Comparison::AEQ, 'bar');
        $this->getOperatorSymbol()->shouldBeEqualTo('~=');
    }

    function it_should_return_foo_when_calling_getAttribute()
    {
        $this->getAttribute()->shouldBeEqualTo('foo');
    }

    function it_should_return_bar_when_calling_getValue()
    {
        $this->getValue()->shouldBeEqualTo('bar');
    }

    function it_should_throw_InvalidArgumentException_on_an_unknown_comparison_type()
    {
        $this->shouldThrow('\InvalidArgumentException')->during('__construct',['foo','FOOBAR', 'bar']);
    }

    function it_should_return_the_correct_ldap_equals_filter_on_toString()
    {
        $this->beConstructedWith('foo', Comparison::EQ, 'bar');
        $this->__tostring()->shouldBeEqualTo('(foo=\62\61\72)');
    }

    function it_should_return_the_correct_ldap_greater_than_or_equals_filter_on_toString()
    {
        $this->beConstructedWith('foo', Comparison::GTE, 'bar');
        $this->__tostring()->shouldBeEqualTo('(foo>=\62\61\72)');
    }

    function it_should_return_the_correct_ldap_less_than_or_equals_filter_on_toString()
    {
        $this->beConstructedWith('foo', Comparison::LTE, 'bar');
        $this->__tostring()->shouldBeEqualTo('(foo<=\62\61\72)');
    }

    function it_should_return_the_correct_ldap_approximately_equals_filter_on_toString()
    {
        $this->beConstructedWith('foo', Comparison::AEQ, 'bar');
        $this->__tostring()->shouldBeEqualTo('(foo~=\62\61\72)');
    }

    function it_should_be_able_to_set_and_get_the_value()
    {
        $this->beConstructedWith('foo', Comparison::AEQ, 'bar');
        $this->getValue()->shouldBeEqualTo('bar');
        $this->setValue('foo');
        $this->getValue()->shouldBeEqualTo('foo');
    }

    function it_should_be_able_to_set_and_get_the_convereted_value()
    {
        $this->beConstructedWith('foo', Comparison::AEQ, 'bar');
        $this->getConvertedValue()->shouldBeNull();
        $this->setConvertedValue('foo');
        $this->getConvertedValue()->shouldBeEqualTo('foo');
    }

    public function getMatchers()
    {
        return [
            'haveConstant' => function($subject, $constant) {
                return defined('\LdapTools\Query\Operator\Comparison::'.$constant);
            }
        ];
    }
}
