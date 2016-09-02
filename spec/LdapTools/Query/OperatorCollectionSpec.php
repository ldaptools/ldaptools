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
use LdapTools\Query\OperatorCollection;
use LdapTools\Schema\LdapObjectSchema;
use PhpSpec\ObjectBehavior;

class OperatorCollectionSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Query\OperatorCollection');
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
        $schema = new LdapObjectSchema('foo','bar');
        $this->addLdapObjectSchema($schema);
        $this->getAliases()->shouldContain($schema);
    }
    
    function it_should_add_a_ldapobjectschema_with_a_specific_alias()
    {
        $schema = new LdapObjectSchema('foo', 'bar');
        $alias = 'foobar';

        $this->addLdapObjectSchema($schema, $alias);
        $this->getAliases()->shouldHaveKeyWithValue($alias, $schema);
    }
    
    function it_should_validate_an_alias_name()
    {
        $schema = new LdapObjectSchema('foo', 'bar');
        $this->shouldThrow('LdapTools\Exception\InvalidArgumentException')->duringAddLdapObjectSchema($schema, false);
        $this->shouldThrow('LdapTools\Exception\InvalidArgumentException')->duringAddLdapObjectSchema($schema, 'a.b');
        $this->shouldThrow('LdapTools\Exception\InvalidArgumentException')->duringAddLdapObjectSchema($schema, 'a*b');
        $this->shouldThrow('LdapTools\Exception\InvalidArgumentException')->duringAddLdapObjectSchema($schema, 'a b');
        
        $this->shouldNotThrow('LdapTools\Exception\InvalidArgumentException')->duringAddLdapObjectSchema($schema, 'a');
        $this->shouldNotThrow('LdapTools\Exception\InvalidArgumentException')->duringAddLdapObjectSchema($schema, '_a');
        $this->shouldNotThrow('LdapTools\Exception\InvalidArgumentException')->duringAddLdapObjectSchema($schema, '0_Ab');
    }
    
    function it_should_add_a_ldapobjectschema_with_an_alias_of_the_object_type_by_default()
    {
        $this->addLdapObjectSchema(new LdapObjectSchema('foo', 'bar'));
        $this->getAliases()->shouldHaveKey('bar');
    }

    function it_should_sort_the_operators()
    {
        $this->add(new MatchingRule('foo', MatchingRuleOid::BIT_OR, 1));
        $this->add(new bAnd());

        $this->toArray()->shouldHaveFirstItemAs('\LdapTools\Query\Operator\bAnd');
        $this->toArray()->shouldHaveLastItemAs('\LdapTools\Query\Operator\MatchingRule');
    }

    public function it_should_support_multiple_LdapObjectSchemas()
    {
        $foo = new LdapObjectSchema('foo', 'foo');
        $foo->setFilter(new Comparison('foo', '=', 'foo'));
        $bar = new LdapObjectSchema('foo', 'bar');
        $bar->setFilter(new Comparison('foo', '=', 'bar'));

        $this->addLdapObjectSchema($foo);
        $this->addLdapObjectSchema($bar);
    }

    function it_should_get_the_ldap_filter_for_the_operators()
    {
        $this->add(new Comparison('foo', Comparison::EQ, 'bar'));
        $this->toLdapFilter()->shouldBeEqualTo('(foo=bar)');

        $this->add(new bNot(new Comparison('bar', Comparison::EQ, 'foo')));
        $this->toLdapFilter()->shouldBeEqualTo('(&(!(bar=foo))(foo=bar))');
    }

    function it_should_get_the_ldap_filter_for_all_aliases_and_wrap_them_in_an_or_statement()
    {
        $foo = new LdapObjectSchema('foo', 'foo');
        $foo->setFilter(new Comparison('foo', Comparison::EQ, 'bar'));
        $bar = new LdapObjectSchema('foo', 'bar');
        $bar->setFilter(new Comparison('bar', Comparison::EQ, 'foo'));
        
        $this->addLdapObjectSchema($bar);
        $this->addLdapObjectSchema($foo);

        $this->toLdapFilter()->shouldBeEqualTo('(|(bar=foo)(foo=bar))');
    }
    
    function it_should_get_the_ldap_filter_for_a_specific_alias()
    {
        $foo = new LdapObjectSchema('foo', 'foo');
        $foo->setFilter(new Comparison('foo', Comparison::EQ, 'bar'));
        $bar = new LdapObjectSchema('foo', 'bar');
        $bar->setFilter(new Comparison('bar', Comparison::EQ, 'foo'));

        $this->addLdapObjectSchema($bar);
        $this->addLdapObjectSchema($foo);

        $this->toLdapFilter('foo')->shouldBeEqualTo('(foo=bar)');
        $this->toLdapFilter('bar')->shouldBeEqualTo('(bar=foo)');
    }
    
    function it_should_throw_an_exception_when_trying_to_get_a_filter_for_an_alias_that_doesnt_exist()
    {
        $this->shouldThrow('LdapTools\Exception\InvalidArgumentException')->duringToLdapFilter('foo');
    }

    function it_should_clone_the_operator_objects_when_cloning_the_collection()
    {
        $operator = new Comparison('foo', Comparison::EQ, 'bar');
        $operators = new OperatorCollection();
        $operators->add($operator);

        $new = clone $operators;
        $operator->setAttribute('foobar');

        $this->add(...$new->getComparisonOperators());
        $this->getComparisonOperators()->shouldNotBeLike([$operator]);
    }

    function it_should_chain_add_calls()
    {
        $this->add(new bAnd())->shouldReturnAnInstanceOf('LdapTools\Query\OperatorCollection');
    }

    function it_should_only_wrap_the_filter_in_an_and_when_the_collection_has_more_than_one_object()
    {
        $this->add(new Comparison('foo','=','bar'));

        $this->toLdapFilter()->shouldBeEqualTo('(foo=bar)');
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
