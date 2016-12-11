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
use LdapTools\Query\Operator\Wildcard;
use PhpSpec\ObjectBehavior;

class WildcardSpec extends ObjectBehavior
{
    protected function escape($value)
    {
        return ldap_escape($value);
    }

    function let()
    {
        $this->beConstructedWith('foo', Wildcard::CONTAINS, 'bar');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Query\Operator\Wildcard');
    }

    function it_should_extend_the_Comparison_operator()
    {
        $this->shouldHaveType('\LdapTools\Query\Operator\Comparison');
    }

    function it_should_have_a_STARTS_WITH_constant()
    {
        $this->shouldHaveConstant('STARTS_WITH');
    }

    function it_should_have_an_ENDS_WITH_constant()
    {
        $this->shouldHaveConstant('ENDS_WITH');
    }

    function it_should_have_a_CONTAINS_constant()
    {
        $this->shouldHaveConstant('CONTAINS');
    }

    function it_should_have_a_PRESENT_constant()
    {
        $this->shouldHaveConstant('PRESENT');
    }

    function it_should_have_an_EQ_constant()
    {
        $this->shouldHaveConstant('EQ');
    }

    public function it_should_return_a_string_with_the_wildcard_operators_on_each_side_when_using_contains()
    {
        $this->beConstructedWith('foo', Wildcard::CONTAINS, 'bar');
        $this->toLdapFilter()->shouldBeEqualTo('(foo=*bar*)');
    }

    public function it_should_return_a_string_with_the_wildcard_operators_on_the_right_side_when_using_starts_with()
    {
        $this->beConstructedWith('foo', Wildcard::STARTS_WITH, 'bar');
        $this->toLdapFilter()->shouldBeEqualTo('(foo=bar*)');
    }

    public function it_should_return_a_string_with_the_wildcard_operators_on_the_left_side_when_using_ends_with()
    {
        $this->beConstructedWith('foo', Wildcard::ENDS_WITH, 'bar');
        $this->toLdapFilter()->shouldBeEqualTo('(foo=*bar)');
    }

    public function it_should_return_a_string_with_unescaped_wildcards_when_using_like()
    {
        $this->beConstructedWith('foo', Wildcard::LIKE, '*b*a*r*');
        $this->toLdapFilter()->shouldBeEqualTo('(foo=*b*a*r*)');
    }

    public function it_should_not_use_a_converter_if_the_type_is_present()
    {
        $this->beConstructedWith('foo', Wildcard::PRESENT);
        $this->getUseConverter()->shouldBeEqualTo(false);
    }

    function it_should_throw_LdapQueryException_when_trying_to_set_the_operator_to_an_invalid_type()
    {
        $ex = new LdapQueryException('Invalid operator symbol ">=". Valid operator symbols are: =');
        $this->shouldThrow($ex)->duringSetOperatorSymbol('>=');
    }

    function it_should_throw_a_LdapQueryException_when_using_an_invalid_attribute_name()
    {
        $this->beConstructedWith('foob<ar*', Wildcard::LIKE, '*bar*');
        $this->shouldThrow('\LdapTools\Exception\LdapQueryException')->duringToLdapFilter();
    }

    function it_should_escape_special_characters_when_going_to_ldap_with_starts_with()
    {
        $this->beConstructedWith('foo', Wildcard::STARTS_WITH, '*test');
        $this->toLdapFilter()->shouldBeEqualTo('(foo=\2atest*)');
    }

    function it_should_escape_special_characters_when_going_to_ldap_with_ends_with()
    {
        $this->beConstructedWith('foo', Wildcard::ENDS_WITH, '*test=)');
        $this->toLdapFilter()->shouldBeEqualTo('(foo=*\2atest=\29)');
    }

    function it_should_escape_special_characters_when_going_to_ldap_with_contains()
    {
        $this->beConstructedWith('foo', Wildcard::ENDS_WITH, '*te*st<*');
        $this->toLdapFilter()->shouldBeEqualTo('(foo=*\2ate\2ast<\2a)');
    }

    function it_should_escape_special_characters_when_going_to_ldap_with_like()
    {
        $this->beConstructedWith('foo', Wildcard::LIKE, '*te*s)t*');
        $this->toLdapFilter()->shouldBeEqualTo('(foo=*te*s\29t*)');
    }


    function it_should_set_the_alias_based_off_the_attribute()
    {
        $this->beConstructedWith('foo.bar', Wildcard::CONTAINS, 'foo');

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
        $this->toLdapFilter('bar')->shouldBeEqualTo('(foo=*bar*)');

        $this->setAttribute('foo');
        // No alias defined according to the attribute, so no alias specified will return the filter...
        $this->toLdapFilter()->shouldBeEqualTo('(foo=*bar*)');
        // This will return the filter for the context of the 'foo' alias, as a specific alias wasn't defined.
        $this->toLdapFilter('foo')->shouldBeEqualTo('(foo=*bar*)');
    }

    function it_should_get_the_LDAP_filter_with_any_converted_values_or_translated_attributes_for_an_alias()
    {
        $this->setAttribute('u.foo');
        $this->toLdapFilter('u')->shouldBeEqualTo('(foo=*bar*)');
        $this->setTranslatedAttribute('foobar', 'u');
        $this->toLdapFilter('u')->shouldBeEqualTo('(foobar=*bar*)');
        $this->setConvertedValue('foo', 'u');
        $this->toLdapFilter('u')->shouldBeEqualTo('(foobar=*foo*)');
    }

    function it_should_return_the_filter_for_the_value_if_the_value_is_a_BaseOperator_instance()
    {
        $this->setAttribute('foo');
        $this->setValue(new Comparison('foobar', '=', 'stuff'));
        $this->toLdapFilter()->shouldEqual('(foobar=stuff)');
    }

    function it_should_get_the_wildcard_type()
    {
        $this->getWildcardType()->shouldBeEqualTo(Wildcard::CONTAINS);
    }

    public function getMatchers()
    {
        return [
            'haveConstant' => function($subject, $constant) {
                return defined('\LdapTools\Query\Operator\Wildcard::'.$constant);
            }
        ];
    }
}
