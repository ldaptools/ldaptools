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
use LdapTools\Query\Operator\Wildcard;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

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
        $this->getLdapFilter()->shouldBeEqualTo('(foo=*bar*)');
    }

    public function it_should_return_a_string_with_the_wildcard_operators_on_the_right_side_when_using_starts_with()
    {
        $this->beConstructedWith('foo', Wildcard::STARTS_WITH, 'bar');
        $this->getLdapFilter()->shouldBeEqualTo('(foo=bar*)');
    }

    public function it_should_return_a_string_with_the_wildcard_operators_on_the_left_side_when_using_ends_with()
    {
        $this->beConstructedWith('foo', Wildcard::ENDS_WITH, 'bar');
        $this->getLdapFilter()->shouldBeEqualTo('(foo=*bar)');
    }

    public function it_should_return_a_string_with_unescaped_wildcards_when_using_like()
    {
        $this->beConstructedWith('foo', Wildcard::LIKE, '*b*a*r*');
        $this->getLdapFilter()->shouldBeEqualTo('(foo=*b*a*r*)');
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
        $this->shouldThrow('\LdapTools\Exception\LdapQueryException')->duringGetLdapFilter();
    }

    function it_should_escape_special_characters_when_going_to_ldap_with_starts_with()
    {
        $this->beConstructedWith('foo', Wildcard::STARTS_WITH, '*test');
        $this->getLdapFilter()->shouldBeEqualTo('(foo=\2atest*)');
    }

    function it_should_escape_special_characters_when_going_to_ldap_with_ends_with()
    {
        $this->beConstructedWith('foo', Wildcard::ENDS_WITH, '*test=)');
        $this->getLdapFilter()->shouldBeEqualTo('(foo=*\2atest=\29)');
    }

    function it_should_escape_special_characters_when_going_to_ldap_with_contains()
    {
        $this->beConstructedWith('foo', Wildcard::ENDS_WITH, '*te*st<*');
        $this->getLdapFilter()->shouldBeEqualTo('(foo=*\2ate\2ast<\2a)');
    }

    function it_should_escape_special_characters_when_going_to_ldap_with_like()
    {
        $this->beConstructedWith('foo', Wildcard::LIKE, '*te*s)t*');
        $this->getLdapFilter()->shouldBeEqualTo('(foo=*te*s\29t*)');
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
