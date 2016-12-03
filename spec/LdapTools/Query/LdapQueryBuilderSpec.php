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

use LdapTools\Cache\NoCache;
use LdapTools\Configuration;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Connection\LdapControl;
use LdapTools\Connection\LdapControlType;
use LdapTools\DomainConfiguration;
use LdapTools\Connection\LdapConnection;
use LdapTools\Exception\InvalidArgumentException;
use LdapTools\Factory\CacheFactory;
use LdapTools\Event\SymfonyEventDispatcher;
use LdapTools\Factory\LdapObjectSchemaFactory;
use LdapTools\Factory\SchemaParserFactory;
use LdapTools\Operation\QueryOperation;
use LdapTools\Query\Builder\ADFilterBuilder;
use LdapTools\Query\Builder\FilterBuilder;
use LdapTools\Query\Operator\bAnd;
use LdapTools\Schema\LdapObjectSchema;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LdapQueryBuilderSpec extends ObjectBehavior
{
    /**
     * @var SchemaParserFactory
     */
    protected $schema;

    /**
     * @var LdapObjectSchema
     */
    protected $objectSchema;

    /**
     * @var FilterBuilder
     */
    protected $fb;
    
    protected $singleGroupEntry = [
        'count' => 1,
        0 => [
            "distinguishedname" => [
                "count" => 1,
                0 => "CN=Foo,DC=bar,DC=foo",
            ],
            0 => "distinguishedName",
            'count' => 2,
            'dn' => "CN=Foo,DC=bar,DC=foo",
        ],
    ];
    
    function let(LdapConnectionInterface $connection)
    {
        $config = new Configuration();
        $domain = new DomainConfiguration('example.com');
        $domain->setServers(['example'])
            ->setBaseDn('dc=example,dc=com')
            ->setLazyBind(true)
            ->setPageSize(500);
        $connection->getConfig()->willReturn($domain);
        $config->setCache(new NoCache());
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), $config->getSchemaFolder());
        $cache = CacheFactory::get($config->getCacheType(), []);
        $dispatcher = new SymfonyEventDispatcher();
        $schemaFactory = new LdapObjectSchemaFactory($cache, $parser, $dispatcher);

        $this->fb = new FilterBuilder();
        $this->schema = $schemaFactory;
        $this->objectSchema = $schema = new LdapObjectSchema('ad', 'user');
        $this->objectSchema->setFilter($this->fb->bAnd($this->fb->eq('objectCategory', 'person'), $this->fb->eq('objectClass', 'user')));
        
        $this->beConstructedWith($connection, $schemaFactory);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Query\LdapQueryBuilder');
    }

    function it_should_return_the_selected_attributes_when_calling_getAttributes()
    {
        $attributes = ['firstName','lastName','description'];
        $this->select($attributes);
        $this->getAttributes()->shouldBeEqualTo($attributes);
    }

    function it_should_allow_a_string_as_a_selected_attribute()
    {
        $this->select('dn');
        $this->getAttributes()->shouldBeEqualTo(['dn']);
    }

    function it_should_error_when_neither_a_string_or_array_was_passed_to_select()
    {
        $this->shouldThrow(new InvalidArgumentException('The attributes to select should either be a string or an array'))
            ->duringSelect(false);
    }

    function it_should_return_self_when_calling_select()
    {
        $this->select(['cn'])->shouldReturnAnInstanceOf('\LdapTools\Query\LdapQueryBuilder');
    }

    function it_should_return_self_when_calling_from_with_a_valid_type()
    {
        $this->select(['cn']);
        $this->from('user')->shouldReturnAnInstanceOf('\LdapTools\Query\LdapQueryBuilder');
    }

    function it_should_return_self_when_calling_from_with_a_LdapObjectSchema()
    {
        $this->select(['cn']);
        $this->from($this->objectSchema)->shouldReturnAnInstanceOf('\LdapTools\Query\LdapQueryBuilder');
    }

    function it_should_return_self_when_calling_fromUsers()
    {
        $this->fromUsers()->shouldReturnAnInstanceOf('\LdapTools\Query\LdapQueryBuilder');
    }

    function it_should_return_self_when_calling_fromGroups()
    {
        $this->fromGroups()->shouldReturnAnInstanceOf('\LdapTools\Query\LdapQueryBuilder');
    }

    function it_should_return_self_when_calling_fromOUs()
    {
        $this->fromOUs()->shouldReturnAnInstanceOf('\LdapTools\Query\LdapQueryBuilder');
    }

    function it_should_return_self_when_calling_where()
    {
        $this->where(['foo' => 'bar'])->shouldReturnAnInstanceOf('\LdapTools\Query\LdapQueryBuilder');
    }

    function it_should_return_self_when_calling_andWhere()
    {
        $this->andWhere(['foo' => 'bar'])->shouldReturnAnInstanceOf('\LdapTools\Query\LdapQueryBuilder');
    }

    function it_should_return_self_when_calling_orWhere()
    {
        $this->andWhere(['foo' => 'bar'])->shouldReturnAnInstanceOf('\LdapTools\Query\LdapQueryBuilder');
    }

    function it_should_return_self_when_calling_add()
    {
        $this->add(new bAnd())->shouldReturnAnInstanceOf('\LdapTools\Query\LdapQueryBuilder');
    }

    function it_should_return_self_when_calling_setScopeBase()
    {
        $this->setScopeBase()->shouldReturnAnInstanceOf('\LdapTools\Query\LdapQueryBuilder');
    }

    function it_should_return_self_when_calling_setScopeOneLevel()
    {
        $this->setScopeOneLevel()->shouldReturnAnInstanceOf('\LdapTools\Query\LdapQueryBuilder');
    }

    function it_should_return_self_when_calling_setScopeSubTree()
    {
        $this->setScopeSubTree()->shouldReturnAnInstanceOf('\LdapTools\Query\LdapQueryBuilder');
    }

    function it_should_return_self_when_setting_the_paging_size_and_use()
    {
        $this->setUsePaging(true)->shouldReturnAnInstanceOf('\LdapTools\Query\LdapQueryBuilder');
        $this->setPageSize(1)->shouldReturnAnInstanceOf('\LdapTools\Query\LdapQueryBuilder');
    }

    function it_should_set_the_scope_types_correctly()
    {
        $this->setScopeBase()->getScope()->shouldBeEqualTo(QueryOperation::SCOPE['BASE']);
        $this->setScopeOneLevel()->getScope()->shouldBeEqualTo(QueryOperation::SCOPE['ONELEVEL']);
        $this->setScopeSubTree()->getScope()->shouldBeEqualTo(QueryOperation::SCOPE['SUBTREE']);
        $this->setScope(QueryOperation::SCOPE['BASE'])->getScope()->shouldBeEqualTo(QueryOperation::SCOPE['BASE']);
    }

    function it_should_set_subtree_as_the_default_scope()
    {
        $this->getScope()->shouldBeEqualTo(QueryOperation::SCOPE['SUBTREE']);
    }

    function it_should_return_ADFilterBuilder_when_calling_filter_and_the_ldap_type_is_ActiveDirectory()
    {
        $this->filter()->shouldBeLike(new ADFilterBuilder());
    }

    function it_should_return_FilterBuilder_when_calling_filter_and_the_ldap_type_is_not_ActiveDirectory()
    {
        $domain = new DomainConfiguration('example.com');
        $domain->setServers(['example'])
            ->setBaseDn('dc=example,dc=com')
            ->setLazyBind(true)
            ->setPageSize(500)
            ->setLdapType(LdapConnection::TYPE_OPENLDAP);
        $connection = new LdapConnection($domain);
        $this->beConstructedWith($connection);

        $this->filter()->shouldBeLike(new FilterBuilder());
    }

    function it_should_return_the_filter_when_calling_toLdapFilter()
    {
        $filter = '(objectClass=group)';
        $this->fromGroups();
        $this->toLdapFilter()->shouldBeEqualTo($filter);
    }

    function it_should_not_escape_hex_string_search_values_that_are_already_escaped()
    {
        $guidHex = '\d0\b4\0d\27\9d\24\a7\46\9c\c5\eb\69\5d\9a\f9\ac';

        $this->fromUsers();
        $this->where(['objectGuid' => $guidHex]);
        $this->toLdapFilter()->shouldBeEqualTo('(&(&(objectCategory=person)(objectClass=user))(&(objectGuid='.$guidHex.')))');
    }

    function it_should_throw_a_LogicException_when_calling_getLdapQuery_and_a_ldap_connection_is_not_set()
    {
        $this->beConstructedWith(null,null);
        $this->where(['foo' => 'bar']);
        $this->shouldThrow('LdapTools\Exception\LogicException')->during('getLdapQuery');
    }

    function it_should_throw_a_LogicException_when_calling_from_with_a_string_and_a_schema_factory_is_not_set()
    {
        $this->beConstructedWith(null,null);
        $this->shouldThrow('LdapTools\Exception\LogicException')->during('from', ['foo']);
    }

    function it_should_set_the_attributes_to_select()
    {
        $this->getAttributes()->shouldBeEqualTo([]);
        $this->select(['bar']);
        $this->getAttributes()->shouldBeEqualTo(['bar']);
    }

    function it_should_add_additional_statements_to_the_AND_section_of_the_filter_when_calling_andWhere()
    {
        $this->where(['foo' => 'bar']);
        $this->andWhere(['bar' => 'foo']);
        $this->toLdapFilter()->shouldBeEqualTo('(&(foo=bar)(bar=foo))');
    }

    function it_should_add_an_order_by_attribute_defaulting_to_asc()
    {
        $this->where(['foo' => 'bar'])->orderBy('foo')->getLdapQuery()->getOrderBy()->shouldBeEqualTo(['foo' => 'ASC']);
    }

    function it_should_add_an_order_by_attribute_defaulting_with_a_specific_direction()
    {
        $this->where(['foo' => 'bar'])->orderBy('foo','DESC')->getLdapQuery()->getOrderBy()->shouldBeEqualTo(['foo' => 'DESC']);
    }

    function it_should_stack_order_by_attributes_when_calling_addOrderBy()
    {
        $this->where(['foo' => 'bar'])->orderBy('foo','DESC')->addOrderBy('bar')->getLdapQuery()->getOrderBy()->shouldBeEqualTo(['foo' => 'DESC','bar' => 'ASC']);
    }

    function it_should_overwrite_order_by_attributes_when_calling_orderBy()
    {
        $this->where(['foo' => 'bar'])->orderBy('foo','DESC')->orderBy('bar')->getLdapQuery()->getOrderBy()->shouldBeEqualTo(['bar' => 'ASC']);
    }
    
    function it_should_add_LDAP_controls_to_the_query_operation()
    {
        $control1 = new LdapControl(LdapControlType::SHOW_DELETED, true);
        $control2 = new LdapControl(LdapControlType::PAGED_RESULTS, false);
        
        $this->addControl($control1, $control2);
        $this->getLdapQuery()->getQueryOperation()->getControls()->shouldBeEqualTo([$control1, $control2]);
    }

    function it_should_filter_by_OUs_when_calling_fromOUs()
    {
        $filter = '(objectClass=organizationalUnit)';
        $this->fromOUs();
        $this->toLdapFilter()->shouldBeEqualTo($filter);
    }

    function it_should_pass_operation_options_on_to_the_LdapQuery_class_correctly()
    {
        $this->objectSchema->setAttributesToSelect(['foo', 'bar']);

        $this->select();
        $this->from($this->objectSchema);
        $this->setScopeOneLevel();
        $this->setBaseDn('ou=stuff,dc=foo,dc=bar');
        $this->setPageSize('9001');

        $this->getLdapQuery()->getQueryOperation()->getAttributes()->shouldBeEqualTo([]);
        $this->getLdapQuery()->getQueryOperation()->getBaseDn()->shouldBeEqualTo('ou=stuff,dc=foo,dc=bar');
        $this->getLdapQuery()->getQueryOperation()->getScope()->shouldBeEqualTo(QueryOperation::SCOPE['ONELEVEL']);
        $this->getLdapQuery()->getQueryOperation()->getPageSize()->shouldBeEqualTo('9001');
        $this->getLdapQuery()->getQueryOperation()->getFilter()->toLdapFilter()->shouldBeEqualTo('(&(objectCategory=person)(objectClass=user))');

        $this->select('foo');
        $this->getLdapQuery()->getQueryOperation()->getAttributes()->shouldBeEqualTo(['foo']);
    }

    function it_should_allow_use_paging_to_be_set_per_query()
    {
        $this->where(['foo' => 'bar'])->getLdapQuery()->getQueryOperation()->getUsePaging()->shouldBeEqualTo(null);
        $this->where(['foo' => 'bar'])->setUsePaging(true)->getLdapQuery()->getQueryOperation()->getUsePaging()->shouldBeEqualTo(true);
    }

    function it_should_allow_the_ldap_server_to_be_set_per_query()
    {
        $this->where(['foo' => 'bar'])->getLdapQuery()->getQueryOperation()->getServer()->shouldBeEqualTo(null);
        $this->where(['foo' => 'bar'])->setServer('foo')->getLdapQuery()->getQueryOperation()->getServer()->shouldBeEqualTo('foo');
    }

    function it_should_set_the_ldap_server()
    {
        $this->getServer()->shouldBeEqualTo(null);
        $this->setServer('foo')->shouldReturnAnInstanceOf('\LdapTools\Query\LdapQueryBuilder');
        $this->getServer()->shouldBeEqualTo('foo');
    }
    
    function it_should_hydrate_properly_getting_the_ldap_filter($connection)
    {
        $connection->execute(Argument::that(function($operation) {
            return $operation->getFilter() == '(&(&(objectClass=group))(sAMAccountName=bar))';
        }))->willReturn($this->singleGroupEntry);
        
        $this->from('user');
        $this->where(['username' => 'foo', 'groups' => 'bar']);
        $this->toLdapFilter()->shouldBeEqualTo('(&(&(objectCategory=person)(objectClass=user))(&(sAMAccountName=foo)(memberOf=CN=Foo,DC=bar,DC=foo)))');
    }
    
    function it_should_get_a_filter_without_a_schema_or_connection()
    {
        $this->beConstructedWith();
        
        $this->where(['foo' => 'bar']);
        $this->toLdapFilter()->shouldBeEqualTo('(&(foo=bar))');
    }
    
    function it_should_throw_an_error_if_the_schema_has_no_filter_defined()
    {
        $schema = new LdapObjectSchema('foo','bar');
        $this->shouldThrow(new InvalidArgumentException('The schema type "bar" needs a filter defined to query LDAP with it.'))->duringFrom($schema);
    }

    function it_should_generate_a_filter_from_multiple_types()
    {
        $this->fromUsers();
        $this->fromGroups();
        
        $this->toLdapFilter()->shouldBeEqualTo('(|(&(objectCategory=person)(objectClass=user))(objectClass=group))');
    }
    
    function it_should_generate_a_filter_from_multiple_types_when_using_an_alias()
    {
        $this->fromUsers('u');
        $this->fromGroups('g');
        $this->where(['u.department' => 'IT', 'g.description' => 'Test']);
        $this->andWhere($this->filter()->startsWith('name', 'Admin'));
        
        $this->toLdapFilter()->shouldBeEqualTo('(|(&(&(objectCategory=person)(objectClass=user))(&(department=IT)(cn=Admin*)))(&(objectClass=group)(&(description=Test)(cn=Admin*))))');
    }
    
    function it_should_be_able_to_call_from_with_a_dynamic_schema_type_name()
    {
        $this->fromOU()->shouldReturnAnInstanceOf('LdapTools\Query\LdapQueryBuilder');
        $this->fromContainer('c');

        $this->getLdapQuery()->getQueryOperation()->getFilter()->getAliases()->shouldHaveKey('ou');
        $this->getLdapQuery()->getQueryOperation()->getFilter()->getAliases()->shouldHaveKey('c');

        $this->shouldThrow('LdapTools\Exception\SchemaParserException')->duringFromFoo();
    }
}
