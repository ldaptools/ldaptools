<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Operation;

use LdapTools\Connection\LdapControl;
use LdapTools\Operation\AddOperation;
use LdapTools\Operation\DeleteOperation;
use LdapTools\Operation\QueryOperation;
use LdapTools\Operation\RenameOperation;
use LdapTools\Query\Operator\Comparison;
use LdapTools\Query\OperatorCollection;
use PhpSpec\ObjectBehavior;

class QueryOperationSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('(foo=bar)');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Operation\QueryOperation');
    }

    function it_should_implement_LdapOperationInterface()
    {
        $this->shouldImplement('\LdapTools\Operation\LdapOperationInterface');
    }

    function it_should_implement_CacheableOperationInterface()
    {
        $this->shouldImplement('\LdapTools\Operation\CacheableOperationInterface');
    }

    function it_should_set_the_base_dn_for_the_query_operation()
    {
        $dn = 'dc=example,dc=local';
        $this->setBaseDn($dn);
        $this->getBaseDn()->shouldBeEqualTo($dn);
    }

    function it_should_set_the_filter_for_the_query_operation()
    {
        $filter = 'foo';
        $this->setFilter($filter);
        $this->getFilter()->shouldBeEqualTo($filter);
    }

    function it_should_set_whether_paging_is_used_for_the_query_operation()
    {
        $this->getUsePaging()->shouldBeEqualTo(null);
        $this->setUsePaging(true);
        $this->getUsePaging()->shouldBeEqualTo(true);
    }

    function it_should_set_the_page_size_for_the_query_operation()
    {
        $pageSize = 1000;
        $this->setPageSize($pageSize);
        $this->getPageSize()->shouldBeEqualTo($pageSize);
    }

    function it_should_set_the_scope_for_the_query_operation()
    {
        $scope = QueryOperation::SCOPE['BASE'];
        $this->setScope($scope);
        $this->getScope()->shouldBeEqualTo($scope);
    }

    function it_should_set_the_attributes_to_return_for_the_query_operation()
    {
        $attributes = ['foo'];
        $this->setAttributes($attributes);
        $this->getAttributes()->shouldBeEqualTo($attributes);
    }

    function it_should_chain_the_setters()
    {
        $this->setBaseDn('foo')->shouldReturnAnInstanceOf('\LdapTools\Operation\QueryOperation');
        $this->setFilter('foo')->shouldReturnAnInstanceOf('\LdapTools\Operation\QueryOperation');
        $this->setPageSize('9001')->shouldReturnAnInstanceOf('\LdapTools\Operation\QueryOperation');
        $this->setScope(QueryOperation::SCOPE['SUBTREE'])->shouldReturnAnInstanceOf('\LdapTools\Operation\QueryOperation');
        $this->setAttributes(['foo'])->shouldReturnAnInstanceOf('\LdapTools\Operation\QueryOperation');
        $this->setUsePaging(true)->shouldReturnAnInstanceOf('\LdapTools\Operation\QueryOperation');
    }

    function it_should_get_the_name_of_the_operation()
    {
        $this->getName()->shouldBeEqualTo('Query');
    }

    function it_should_get_the_correct_ldap_function_for_the_given_scope()
    {
        $this->setScope(QueryOperation::SCOPE['SUBTREE'])->getLdapFunction()->shouldBeEqualTo('ldap_search');
        $this->setScope(QueryOperation::SCOPE['ONELEVEL'])->getLdapFunction()->shouldBeEqualTo('ldap_list');
        $this->setScope(QueryOperation::SCOPE['BASE'])->getLdapFunction()->shouldBeEqualTo('ldap_read');
    }

    function it_should_throw_a_query_exception_when_an_invalid_scope_is_used()
    {
        $this->shouldThrow('\LdapTools\Exception\LdapQueryException')->duringSetScope('foo');
    }

    function it_should_return_the_arguments_for_the_ldap_function_in_the_correct_order()
    {
        $args = [
            'dc=foo,dc=bar',
            '(foo=bar)',
            ['foo'],
            0,
        ];
        $this->setBaseDn($args[0]);
        $this->setFilter($args[1]);
        $this->setAttributes($args[2]);
        $this->getArguments()->shouldBeEqualTo($args);
    }

    function it_should_get_a_log_formatted_array()
    {
        $this->getLogArray()->shouldBeArray();
        $this->getLogArray()->shouldHaveKey('Base DN');
        $this->getLogArray()->shouldHaveKey('Scope');
        $this->getLogArray()->shouldHaveKey('Page Size');
        $this->getLogArray()->shouldHaveKey('Filter');
        $this->getLogArray()->shouldHaveKey('Attributes');
        $this->getLogArray()->shouldHaveKey('Use Paging');
        $this->getLogArray()->shouldHaveKey('Server');
        $this->getLogArray()->shouldHaveKey('Controls');
        $this->getLogArray()->shouldHaveKey('Size Limit');
        $this->getLogArray()->shouldHaveKey('Use Cache');
        $this->getLogArray()->shouldHaveKey('Execute on Cache Miss');
        $this->getLogArray()->shouldHaveKey('Invalidate Cache');
    }

    function it_should_support_being_constructed_with_a_filter_and_attributes()
    {
        $this->beConstructedWith('foo', ['bar']);

        $this->getFilter()->shouldBeEqualTo('foo');
        $this->getAttributes()->shouldBeEqualTo(['bar']);
    }
    
    function it_should_support_an_OperatorCollection_as_the_filter_value()
    {
        $collection = new OperatorCollection();
        $collection->add(new Comparison('foo', '=', 'bar'));
        $this->setFilter($collection);
        
        $this->getFilter()->shouldBeEqualTo($collection);
        $this->getArguments()->shouldBeEqualTo([null, '(foo=bar)', [], 0]);
        $this->getLogArray()->shouldContain('(foo=bar)');
    }

    function it_should_clone_the_operator_collection()
    {
        $operator = new Comparison('foo', Comparison::EQ, 'bar');
        $operators = new OperatorCollection();
        $operators->add($operator);
        $operation = new QueryOperation($operators);
        $new = clone $operation;
        $operator->setAttribute('foobar');

        $this->setFilter($new->getFilter());
        $this->getFilter()->toLdapFilter()->shouldNotBeEqualTo('(foobar=bar)');
    }

    function it_should_add_pre_operations()
    {
        $operation1 = new AddOperation('cn=foo,dc=bar,dc=foo');
        $operation2 = new DeleteOperation('cn=foo,dc=bar,dc=foo');
        $operation3 = new RenameOperation('cn=foo,dc=bar,dc=foo');

        $this->addPreOperation($operation1);
        $this->addPreOperation($operation2, $operation3);
        $this->getPreOperations()->shouldBeEqualTo([$operation1, $operation2, $operation3]);
    }

    function it_should_add_post_operations()
    {
        $operation1 = new AddOperation('cn=foo,dc=bar,dc=foo');
        $operation2 = new DeleteOperation('cn=foo,dc=bar,dc=foo');
        $operation3 = new RenameOperation('cn=foo,dc=bar,dc=foo');

        $this->addPostOperation($operation1);
        $this->addPostOperation($operation2, $operation3);
        $this->getPostOperations()->shouldBeEqualTo([$operation1, $operation2, $operation3]);
    }

    function it_should_add_ldap_controls()
    {
        $control1 = new LdapControl('foo', true);
        $control2 = new LdapControl('bar');

        $this->addControl($control1, $control2);
        $this->getControls()->shouldBeEqualTo([$control1, $control2]);
    }
    
    function it_should_set_a_size_limit_for_the_query()
    {
        $this->getSizeLimit()->shouldBeEqualTo(0);
        $this->setSizeLimit(5)->getSizeLimit()->shouldBeEqualTo(5);
    }

    function it_should_throw_an_exception_if_the_filter_is_empty_when_getting_the_arguments()
    {
        $this->setFilter('');
        $this->shouldThrow('LdapTools\Exception\LdapQueryException')->duringGetArguments();
        $this->setFilter(new OperatorCollection());
        $this->shouldThrow('LdapTools\Exception\LdapQueryException')->duringGetArguments();
    }

    function it_should_set_whether_or_not_to_use_the_cache()
    {
        $this->getUseCache()->shouldBeEqualTo(false);
        $this->setUseCache(true)->getUseCache()->shouldBeEqualTo(true);
    }

    function it_should_set_whether_or_not_to_execute_on_a_cache_miss()
    {
        $this->getExecuteOnCacheMiss()->shouldBeEqualTo(true);
        $this->setExecuteOnCacheMiss(false)->getExecuteOnCacheMiss()->shouldBeEqualTo(false);
    }

    function it_should_set_whether_or_not_to_invalidate_the_cache()
    {
        $this->getInvalidateCache()->shouldBeEqualTo(false);
        $this->setInvalidateCache(true)->getInvalidateCache()->shouldBeEqualTo(true);
    }

    function it_should_set_a_cache_expiration()
    {
        $date = new \DateTime();
        $this->getExpireCacheAt()->shouldBeNull();
        $this->setExpireCacheAt($date)->getExpireCacheAt()->shouldBeEqualTo($date);
    }
}
