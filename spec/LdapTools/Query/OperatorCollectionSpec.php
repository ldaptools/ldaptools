<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Query;

use LdapTools\Query\MatchingRuleOid;
use LdapTools\Query\Operator\bAnd;
use LdapTools\Query\Operator\bNot;
use LdapTools\Query\Operator\bOr;
use LdapTools\Query\Operator\Comparison;
use LdapTools\Query\Operator\MatchingRule;
use LdapTools\Query\Operator\Wildcard;
use LdapTools\Query\Operator\From;
use LdapTools\Schema\LdapObjectSchema;
use PhpSpec\Exception\Example\SkippingException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class OperatorCollectionSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Query\OperatorCollection');
    }

    function it_should_add_a_from_correctly()
    {
        $this->add(new From(new Comparison('objectclass', Comparison::EQ, 'foobar')));
        $this->getFromOperators()->shouldHaveCount(1);
    }

    function it_should_add_a_comparison_correctly()
    {
        $this->add(new Comparison('foo', Comparison::EQ, 'bar'));
        $this->getComparisonOperators()->shouldHaveCount(1);
    }

    function it_should_add_a_bAnd_correctly()
    {
        $this->add(new bAnd());
        $this->getAndOperators()->shouldHaveCount(1);
    }

    function it_should_add_a_bOr_correctly()
    {
        $this->add(new bOr());
        $this->getOrOperators()->shouldHaveCount(1);
    }

    function it_should_add_a_matchingrule_correctly()
    {
        $this->add(new MatchingRule('foo', MatchingRuleOid::BIT_OR, 1));
        $this->getMatchingRuleOperators()->shouldHaveCount(1);
    }

    function it_should_add_a_not_correctly()
    {
        $this->add(new bNot(new Comparison('foo', Comparison::EQ, 'bar')));
        $this->getNotOperators()->shouldHaveCount(1);
    }

    function it_should_add_a_wildcard_correctly()
    {
        $this->add(new Wildcard('foo', Wildcard::CONTAINS, 'bar'));
        $this->getWildcardOperators()->shouldHaveCount(1);
    }

    function it_should_return_an_array_from_toArray()
    {
        $this->toArray()->shouldBeArray();
    }

    function it_should_be_iterable()
    {
        $this->shouldImplement('\IteratorAggregate');
    }

    function it_should_add_a_ldapobjectschema_when_calling_addLdapObjectSchema()
    {
        $this->addLdapObjectSchema(new LdapObjectSchema('foo','bar'));
        $this->getLdapObjectSchemas()->shouldHaveCount(1);
    }

    function it_should_return_an_array_when_calling_getLdapObjectSchemas()
    {
        $this->getLdapObjectSchemas()->shouldBeArray();
    }

    function it_should_sort_the_operators()
    {
        $this->add(new From(new Comparison('objectclass', Comparison::EQ, 'foobar')));
        $this->add(new bAnd());
        $this->add(new MatchingRule('foo', MatchingRuleOid::BIT_OR, 1));

        $this->toArray()->shouldHaveFirstItemAs('\LdapTools\Query\Operator\From');
        $this->toArray()->shouldHaveLastItemAs('\LdapTools\Query\Operator\MatchingRule');
    }

    public function it_should_throw_an_exception_when_adding_non_operators()
    {
        if (version_compare(PHP_VERSION, '7.0', '>=')) {
            throw new SkippingException("This spec currently doesn't work on PHP >= 7. Prophecy related issue?");
        };
        $this->shouldThrow('\Exception')->duringAdd('foobar');
    }

    public function it_should_throw_an_LdapQueryException_when_adding_more_than_one_From_operator()
    {
        $this->add(new From(new Comparison('objectclass', Comparison::EQ, 'foobar')));
        $this->shouldThrow('\LdapTools\Exception\LdapQueryException')->duringAdd(
            new From(new Comparison('objectclass', Comparison::EQ, 'foobar'))
        );
    }

    function it_should_get_the_ldap_filter_for_the_operators()
    {
        $this->add(new Comparison('foo', Comparison::EQ, 'bar'));
        $this->toLdapFilter()->shouldBeEqualTo('(foo=bar)');

        $this->add(new bNot(new Comparison('bar', Comparison::EQ, 'foo')));
        $this->toLdapFilter()->shouldBeEqualTo('(&(!(bar=foo))(foo=bar))');
    }

    public function getMatchers()
    {
        return [
            'haveFirstItemAs' => function($subject, $class) {
                return is_a(reset($subject), $class);
            },
            'haveLastItemAs' => function($subject, $class) {
                return is_a(end($subject), $class);
            },
        ];
    }
}
