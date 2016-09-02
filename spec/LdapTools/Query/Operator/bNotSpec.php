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

class bNotSpec extends ObjectBehavior
{
    public function let()
    {
        $operator = new Comparison('foo', Comparison::EQ, 'bar');
        $this->beConstructedWith($operator);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Query\Operator\bNot');
    }

    function it_should_implement_ContainsOperatorsInferface()
    {
        $this->shouldImplement('\LdapTools\Query\Operator\ContainsOperatorsInterface');
    }

    function it_should_have_an_exclamation_symbol()
    {
        $this->getOperatorSymbol()->shouldBeEqualTo('!');
    }

    function it_should_throw_a_LdapQueryException_when_adding_items_implementing_ContainsOperatorsInterface()
    {
        $this->shouldThrow('\LdapTools\Exception\LdapQueryException')->during('__construct', [
            new bAnd()
        ]);
    }

    function it_should_throw_a_RuntimeException_when_adding_more_than_one_operator()
    {
        $this->shouldThrow('\RuntimeException')->duringAdd(new Comparison('foo', Comparison::EQ, 'bar'));
    }

    function it_should_throw_LdapQueryException_when_trying_to_set_the_operator_to_an_invalid_type()
    {
        $ex = new LdapQueryException('Invalid operator symbol "=". Valid operator symbols are: !');
        $this->shouldThrow($ex)->duringSetOperatorSymbol('=');
    }
}
