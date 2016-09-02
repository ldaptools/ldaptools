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
use LdapTools\Query\Operator\Comparison;
use PhpSpec\ObjectBehavior;

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

    function it_should_throw_LdapQueryException_on_an_unknown_comparison_type()
    {
        $ex = new LdapQueryException('Invalid operator symbol "FOOBAR". Valid operator symbols are: ~=, =, >=, <=');
        $this->shouldThrow($ex)->during('__construct',['foo','FOOBAR', 'bar']);
    }

    function it_should_not_throw_LdapQueryException_on_a_valid_comparison_type()
    {
        $this->shouldNotThrow('\LdapTools\Exception\LdapQueryException')->duringSetOperatorSymbol(Comparison::GTE);
        $this->shouldNotThrow('\LdapTools\Exception\LdapQueryException')->duringSetOperatorSymbol(Comparison::LTE);
        $this->shouldNotThrow('\LdapTools\Exception\LdapQueryException')->duringSetOperatorSymbol(Comparison::AEQ);
        $this->shouldNotThrow('\LdapTools\Exception\LdapQueryException')->duringSetOperatorSymbol(Comparison::EQ);
    }

    function it_should_return_the_correct_ldap_equals_filter()
    {
        $this->beConstructedWith('foo', Comparison::EQ, 'bar');
        $this->toLdapFilter()->shouldBeEqualTo('(foo=bar)');
    }

    function it_should_return_the_correct_ldap_greater_than_or_equals_filter()
    {
        $this->beConstructedWith('foo', Comparison::GTE, 'bar');
        $this->toLdapFilter()->shouldBeEqualTo('(foo>=bar)');
    }

    function it_should_return_the_correct_ldap_less_than_or_equals_filter()
    {
        $this->beConstructedWith('foo', Comparison::LTE, 'bar');
        $this->toLdapFilter()->shouldBeEqualTo('(foo<=bar)');
    }

    function it_should_return_the_correct_ldap_approximately_equals_filter()
    {
        $this->beConstructedWith('foo', Comparison::AEQ, 'bar');
        $this->toLdapFilter()->shouldBeEqualTo('(foo~=bar)');
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

    function it_should_be_able_to_set_and_get_whether_a_converter_should_be_used()
    {
        $this->beConstructedWith('foo', Comparison::AEQ, 'bar');
        $this->getUseConverter()->shouldBeEqualTo(true);
        $this->setUseConverter(false);
        $this->getUseConverter()->shouldBeEqualTo(false);
    }

    function it_should_be_able_to_set_and_get_whether_a_converter_was_used()
    {
        $this->beConstructedWith('foo', Comparison::AEQ, 'bar');
        $this->getWasConverterUsed()->shouldBeEqualTo(false);
        $this->setWasConverterUsed(true);
        $this->getWasConverterUsed()->shouldBeEqualTo(true);
    }

    function it_should_throw_a_LdapQueryException_when_using_an_invalid_attribute_name()
    {
        $this->beConstructedWith('foo=*bar', Comparison::AEQ, 'bar');
        $this->shouldThrow('\LdapTools\Exception\LdapQueryException')->duringToLdapFilter();
    }

    function it_should_escape_special_characters_when_going_to_ldap()
    {
        $this->beConstructedWith('foo', Comparison::AEQ, '*(foo=)(*');
        $this->toLdapFilter()->shouldBeEqualTo('(foo~=\2a\28foo=\29\28\2a)');
    }

    function it_should_set_the_alias_based_off_the_attribute()
    {
        $this->beConstructedWith('foo.bar', '=', 'foo');

        $this->getAlias()->shouldBeEqualTo('foo');
        $this->getAttribute()->shouldBeEqualTo('bar');

        $this->setAttribute('bar.foo');
        $this->getAlias()->shouldBeEqualTo('bar');
        $this->getAttribute()->shouldBeEqualTo('foo');
    }

    function it_should_set_the_converted_value_for_a_specific_alias()
    {
        $this->setValue('foo');
        $this->setConvertedValue('bar', 'foo');
        $this->getConvertedValue('foo')->shouldBeEqualTo('bar');
        // If a converted value was never set for an alias, it will return null.
        $this->getConvertedValue('bar')->shouldBeEqualTo(null);
        // No 'non-alias' converted value was set, so this would return null too.
        $this->getConvertedValue()->shouldBeEqualTo(null);
    }

    function it_should_set_the_translated_attribute_name_for_a_specific_alias()
    {
        $this->setAttribute('name');
        $this->setTranslatedAttribute('ou', 'ou');
        $this->setTranslatedAttribute('cn', 'container');
        $this->getTranslatedAttribute('ou')->shouldBeEqualTo('ou');
        $this->getTranslatedAttribute('container')->shouldBeEqualTo('cn');
        // If a translated attribute was never set for an alias, it will return an empty string.
        $this->getTranslatedAttribute('bar')->shouldBeEqualTo('');
        // No 'non-alias' translated attribute was set, so this would return an empty string too.
        $this->getTranslatedAttribute()->shouldBeEqualTo('');
    }

    function it_should_set_if_a_converter_was_used_for_a_specific_alias()
    {
        $this->setAttribute('foo');
        $this->getWasConverterUsed()->shouldBeEqualTo(false);
        $this->getWasConverterUsed('foo')->shouldBeEqualTo(false);

        $this->setWasConverterUsed(true);
        $this->getWasConverterUsed()->shouldBeEqualTo(true);
        $this->getWasConverterUsed('foo')->shouldBeEqualTo(false);

        $this->setWasConverterUsed(true, 'foo');
        $this->setWasConverterUsed(false);
        $this->getWasConverterUsed('foo')->shouldBeEqualTo(true);
        $this->getWasConverterUsed()->shouldBeEqualTo(false);
    }

    function it_should_return_the_LDAP_filter_correctly_based_on_the_alias_in_use()
    {
        $this->setAttribute('bar.foo');

        // When set to a specific alias (in this case 'bar'), other aliases will generate an empty string...
        $this->toLdapFilter('foo')->shouldBeEqualTo('');
        // The absence of an alias when one is explicitly set will also return an empty string...
        $this->toLdapFilter()->shouldBeEqualTo('');
        // When the alias is specifically called then the filter will be returned...
        $this->toLdapFilter('bar')->shouldBeEqualTo('(foo=bar)');

        $this->setAttribute('foo');
        // No alias defined according to the attribute, so no alias specified will return the filter...
        $this->toLdapFilter()->shouldBeEqualTo('(foo=bar)');
        // This will return the filter for the context of the 'foo' alias, as a specific alias wasn't defined.
        $this->toLdapFilter('foo')->shouldBeEqualTo('(foo=bar)');
    }

    function it_should_get_the_LDAP_filter_with_any_converted_values_or_translated_attributes_for_an_alias()
    {
        $this->setAttribute('u.foo');
        $this->toLdapFilter('u')->shouldBeEqualTo('(foo=bar)');
        $this->setTranslatedAttribute('foobar', 'u');
        $this->toLdapFilter('u')->shouldBeEqualTo('(foobar=bar)');
        $this->setConvertedValue('foo', 'u');
        $this->toLdapFilter('u')->shouldBeEqualTo('(foobar=foo)');
    }

    function it_should_return_the_filter_for_the_value_if_the_value_is_a_BaseOperator_instance()
    {
        $this->setAttribute('foo');
        $this->setValue(new Comparison('foobar', '=', 'stuff'));
        $this->toLdapFilter()->shouldEqual('(foobar=stuff)');
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
