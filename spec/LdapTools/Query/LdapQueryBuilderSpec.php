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

use LdapTools\Configuration;
use LdapTools\DomainConfiguration;
use LdapTools\Connection\LdapConnection;
use LdapTools\Factory\CacheFactory;
use LdapTools\Factory\EventDispatcherFactory;
use LdapTools\Factory\LdapObjectSchemaFactory;
use LdapTools\Factory\SchemaParserFactory;
use LdapTools\Query\Builder\ADFilterBuilder;
use LdapTools\Query\Builder\FilterBuilder;
use LdapTools\Query\LdapQuery;
use LdapTools\Query\Operator\bAnd;
use LdapTools\Schema\LdapObjectSchema;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LdapQueryBuilderSpec extends ObjectBehavior
{
    function let()
    {
        $config = new Configuration();
        $domain = new DomainConfiguration('example.com');
        $domain->setServers(['example'])
            ->setBaseDn('dc=example,dc=com')
            ->setLazyBind(true)
            ->setPageSize(500)
            ->setLdapType(LdapConnection::TYPE_AD);
        $connection = new LdapConnection($domain);
        $config->setCacheType('none');

        $parser = SchemaParserFactory::get($config->getSchemaFormat(), $config->getSchemaFolder());
        $cache = CacheFactory::get($config->getCacheType(), []);
        $dispatcher = EventDispatcherFactory::get();
        $schemaFactory = new LdapObjectSchemaFactory($cache, $parser, $dispatcher);

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
        $this->shouldThrow(new \InvalidArgumentException('The attributes to select should either be a string or an array'))
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
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setObjectClass('user');
        $schema->setObjectCategory('person');
        $this->select(['cn']);
        $this->from($schema)->shouldReturnAnInstanceOf('\LdapTools\Query\LdapQueryBuilder');
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

    function it_should_set_the_scope_types_correctly()
    {
        $this->setScopeBase()->getScope()->shouldBeEqualTo(LdapQuery::SCOPE_BASE);
        $this->setScopeOneLevel()->getScope()->shouldBeEqualTo(LdapQuery::SCOPE_ONELEVEL);
        $this->setScopeSubTree()->getScope()->shouldBeEqualTo(LdapQuery::SCOPE_SUBTREE);
    }

    function it_should_set_subtree_as_the_default_scope()
    {
        $this->getScope()->shouldBeEqualTo(LdapQuery::SCOPE_SUBTREE);
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

    function it_should_return_the_filter_when_calling_getLdapFilter_or_toString()
    {
        $filter = '(objectClass=\67\72\6f\75\70)';
        $this->fromGroups();
        $this->getLdapFilter()->shouldBeEqualTo($filter);
        $this->getLdapFilter()->shouldBeEqualTo($filter);
    }

    function it_should_not_escape_hex_string_search_values_that_are_already_escaped()
    {
        $guidHex = '\d0\b4\0d\27\9d\24\a7\46\9c\c5\eb\69\5d\9a\f9\ac';

        $this->fromUsers();
        $this->where(['objectGuid' => $guidHex]);
        $this->getLdapFilter()->shouldBeEqualTo('(&(&(objectCategory=\70\65\72\73\6f\6e)(objectClass=\75\73\65\72))(&(objectGuid='.$guidHex.')))');
    }

    function it_should_throw_a_RuntimeException_when_calling_getLdapQuery_and_a_ldap_connection_is_not_set()
    {
        $this->beConstructedWith(null,null);
        $this->where(['foo' => 'bar']);
        $this->shouldThrow('\RuntimeException')->during('getLdapQuery');
    }

    function it_should_throw_a_RuntimeException_when_calling_from_with_a_string_and_a_schema_factory_is_not_set()
    {
        $this->beConstructedWith(null,null);
        $this->shouldThrow('\RuntimeException')->during('from', ['foo']);
    }

    function it_should_honor_default_attributes_to_select_when_present_in_the_LdapObjectSchema()
    {
        $attributes = ['foo', 'bar'];
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setObjectClass('user');
        $schema->setObjectCategory('person');
        $schema->setAttributesToSelect($attributes);

        $this->select();
        $this->from($schema);
        $this->getAttributes()->shouldBeEqualTo($attributes);
    }

    function it_should_override_default_attributes_to_select_when_explicitly_setting_attributes_in_select()
    {
        $attributes = ['foo'];
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setObjectClass('user');
        $schema->setObjectCategory('person');
        $schema->setAttributesToSelect($attributes);

        $this->select(['bar']);
        $this->from($schema);
        $this->getAttributes()->shouldBeEqualTo(['bar']);
    }

    function it_should_add_additional_statements_to_the_AND_section_of_the_filter_when_calling_andWhere()
    {
        $this->where(['foo' => 'bar']);
        $this->andWhere(['bar' => 'foo']);
        $this->getLdapFilter()->shouldBeEqualTo('(&(foo=\62\61\72)(bar=\66\6f\6f))');
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

    function it_should_filter_by_OUs_when_calling_fromOUs()
    {
        $filter = '(objectClass=\6f\72\67\61\6e\69\7a\61\74\69\6f\6e\61\6c\55\6e\69\74)';
        $this->fromOUs();
        $this->getLdapFilter()->shouldBeEqualTo($filter);
    }

    function it_should_allow_a_schema_object_type_that_has_multiple_classes_defined_in_objectClass()
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setObjectClass(['foo', 'bar']);

        $this->from($schema);
        $this->getLdapFilter()->shouldBeEqualTo('(&(objectClass=\66\6f\6f)(objectClass=\62\61\72))');
    }

    function it_should_allow_a_schema_object_type_that_has_only_a_category_defined()
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setObjectCategory('foo');

        $this->from($schema);
        $this->getLdapFilter()->shouldBeEqualTo('(objectCategory=\66\6f\6f)');
    }

    function it_should_allow_a_schema_object_type_that_has_only_a_single_class_defined()
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setObjectClass('foo');

        $this->from($schema);
        $this->getLdapFilter()->shouldBeEqualTo('(objectClass=\66\6f\6f)');
    }

    function it_should_allow_a_schema_object_type_that_has_a_single_category_and_class_defined()
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setObjectCategory('foo');
        $schema->setObjectClass('bar');

        $this->from($schema);
        $this->getLdapFilter()->shouldBeEqualTo('(&(objectCategory=\66\6f\6f)(objectClass=\62\61\72))');
    }

    function it_should_allow_a_schema_object_type_that_has_a_single_category_and_multiple_classes_defined()
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setObjectCategory('foo');
        $schema->setObjectClass(['foo', 'bar']);

        $this->from($schema);
        $this->getLdapFilter()->shouldBeEqualTo('(&(objectCategory=\66\6f\6f)(&(objectClass=\66\6f\6f)(objectClass=\62\61\72)))');
    }
}
