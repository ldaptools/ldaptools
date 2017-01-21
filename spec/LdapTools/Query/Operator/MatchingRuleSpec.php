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
use LdapTools\Query\Operator\Comparison;
use PhpSpec\ObjectBehavior;

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
        $this->toLdapFilter()->shouldBeEqualTo('(foo:1.2.840.113556.1.4.803:=2)');
    }

    function it_should_return_the_correct_ldap_bitwise_or_filter()
    {
        $this->beConstructedWith('foo', MatchingRuleOid::BIT_OR, 2);
        $this->toLdapFilter()->shouldBeEqualTo('(foo:1.2.840.113556.1.4.804:=2)');
    }

    function it_should_throw_LdapQueryException_when_trying_to_set_the_operator_to_an_invalid_type()
    {
        $ex = new LdapQueryException('Invalid operator symbol ">=". Valid operator symbols are: :=');
        $this->shouldThrow($ex)->duringSetOperatorSymbol('>=');
    }

    function it_should_throw_a_LdapQueryException_on_an_invalid_oid()
    {
        $this->beConstructedWith('foo', 'foo=bar)(', 2);
        $this->shouldThrow('\LdapTools\Exception\LdapQueryException')->duringToLdapFilter();
    }

    function it_should_accept_text_as_an_oid()
    {
        $this->beConstructedWith('foo', 'FooBarMatch', 2);
        $this->shouldNotThrow('\LdapTools\Exception\LdapQueryException')->duringToLdapFilter();
    }

    function it_should_escape_special_characters_when_going_to_ldap()
    {
        $this->beConstructedWith('foo', MatchingRuleOid::BIT_OR, '\*)3');
        $this->toLdapFilter()->shouldBeEqualTo('(foo:1.2.840.113556.1.4.804:=\5c\2a\293)');
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
        $this->toLdapFilter('bar')->shouldBeEqualTo('(foo:1.2.840.113556.1.4.803:=2)');

        $this->setAttribute('foo');
        // No alias defined according to the attribute, so no alias specified will return the filter...
        $this->toLdapFilter()->shouldBeEqualTo('(foo:1.2.840.113556.1.4.803:=2)');
        // This will return the filter for the context of the 'foo' alias, as a specific alias wasn't defined.
        $this->toLdapFilter('foo')->shouldBeEqualTo('(foo:1.2.840.113556.1.4.803:=2)');
    }

    function it_should_get_the_LDAP_filter_with_any_converted_values_or_translated_attributes_for_an_alias()
    {
        $this->setAttribute('u.foo');
        $this->toLdapFilter('u')->shouldBeEqualTo('(foo:1.2.840.113556.1.4.803:=2)');
        $this->setTranslatedAttribute('foobar', 'u');
        $this->toLdapFilter('u')->shouldBeEqualTo('(foobar:1.2.840.113556.1.4.803:=2)');
        $this->setConvertedValue('foo', 'u');
        $this->toLdapFilter('u')->shouldBeEqualTo('(foobar:1.2.840.113556.1.4.803:=foo)');
    }
    
    function it_should_return_the_filter_for_the_value_if_the_value_is_a_BaseOperator_instance()
    {
        $this->setAttribute('foo');
        $this->setValue(new Comparison('foobar', '=', 'stuff'));
        $this->toLdapFilter()->shouldEqual('(foobar=stuff)');
    }

    function it_should_set_the_matching_rule()
    {
        $this->setRule('caseExactMatch')->getRule()->shouldBeEqualTo('caseExactMatch');
    }

    function it_should_set_the_dn_flag_when_specified()
    {
        $this->getUseDnFlag()->shouldBeEqualTo(false);
        $this->setUseDnFlag(true)->getUseDnFlag()->shouldBeEqualTo(true);
    }

    function it_should_allow_for_only_a_rule_with_no_explicit_attribute()
    {
        $this->beConstructedWith(null, 'caseExactMatch', 'foo');
        $this->toLdapFilter()->shouldBeEqualTo('(:caseExactMatch:=foo)');
    }

    function it_should_allow_for_only_an_attribute_and_value_specified()
    {
        $this->beConstructedWith('foo', null, 'bar');
        $this->toLdapFilter()->shouldBeEqualTo('(foo:=bar)');
    }

    function it_should_throw_an_exception_if_neither_an_attribute_or_rule_was_specified()
    {
        $this->beConstructedWith(null, null, 'foo');

        $this->shouldThrow('LdapTools\Exception\LdapQueryException')->duringToLdapFilter();
    }

    function it_should_allow_for_a_dn_flag_attribute_and_value()
    {
        $this->beConstructedWith('ou', null, 'Sales', true);

        $this->toLdapFilter()->shouldBeEqualTo('(ou:dn:=Sales)');
    }

    function it_should_allow_for_a_dn_flag_matching_rule_and_value()
    {
        $this->beConstructedWith(null, '2.4.8.10', 'America', true);

        $this->toLdapFilter()->shouldBeEqualTo('(:dn:2.4.8.10:=America)');
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
