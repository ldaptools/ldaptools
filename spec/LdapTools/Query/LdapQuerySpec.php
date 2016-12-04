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
use LdapTools\DomainConfiguration;
use LdapTools\Event\SymfonyEventDispatcher;
use LdapTools\Exception\LdapQueryException;
use LdapTools\Factory\CacheFactory;
use LdapTools\Factory\HydratorFactory;
use LdapTools\Factory\LdapObjectSchemaFactory;
use LdapTools\Factory\SchemaParserFactory;
use LdapTools\Object\LdapObject;
use LdapTools\Operation\QueryOperation;
use LdapTools\Query\Builder\FilterBuilder;
use LdapTools\Query\LdapQuery;
use LdapTools\Query\Operator\Comparison;
use LdapTools\Query\OperatorCollection;
use LdapTools\Schema\LdapObjectSchema;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LdapQuerySpec extends ObjectBehavior
{
    protected $ous = [
        "count" => 2,
        0 => [
            "ou" => [
                "count" => 1,
                0 => "West",
            ],
            0 => "ou",
            "description" => [
                "count" => 1,
                0 => "West - Servers",
            ],
            1 => "description",
            "count" => 1,
            "dn" => "ou=West,dc=example,dc=local",
        ],
        1 => [
            "ou" => [
                "count" => 1,
                0 => "Employees",
            ],
            0 => "ou",
            "description" => [
                "count" => 1,
                0 => "All Employees",
            ],
            1 => "description",
            "count" => 1,
            "dn" => "ou=Employees,dc=example,dc=local",
        ],
    ];

    protected $containers = [
        "count" => 2,
        0 => [
            "cn" => [
                "count" => 1,
                0 => "Computers",
            ],
            0 => "cn",
            "description" => [
                "count" => 1,
                0 => "Default computers container",
            ],
            1 => "description",
            "count" => 1,
            "dn" => "cn=Computers,dc=example,dc=local",
        ],
        1 => [
            "cn" => [
                "count" => 1,
                0 => "Users",
            ],
            0 => "cn",
            "description" => [
                "count" => 1,
                0 => "Default users container",
            ],
            1 => "description",
            "count" => 1,
            "dn" => "cn=Users,dc=example,dc=local",
        ],
    ];
    
    protected $ldapEntries = [
        "count" => 2,
        0 => [
            "givenname" => [
                "count" => 1,
                0 => "Jon",
            ],
            0 => "givenname",
            "cn" => [
                "count" => 1,
                0 => "Jon Bourke",
            ],
            1 => "cn",
            "sn" => [
                "count" => 1,
                0 => "Bourke",
            ],
            2 => "sn",
            "otherhomephone" => [
                "count" => 2,
                0 => "555-5555",
                1 => "444-4444",
            ],
            3 => "otherhomephone",
            "count" => 3,
            "dn" => "uid=jbourke,ou=People,dc=example,dc=local",
        ],
        1 => [
            "givenname" => [
                "count" => 1,
                0 => "Jon",
            ],
            0 => "givenname",
            "cn" => [
                "count" => 1,
                0 => "Jon Goldstein",
            ],
            1 => "cn",
            "sn" => [
                "count" => 1,
                0 => "Goldstein",
            ],
            2 => "sn",
            "otherhomephone" => [
                "count" => 1,
                0 => "555-5555",
            ],
            3 => "otherhomephone",
            "count" => 4,
            "dn" => "uid=jgoldste,ou=People,dc=example,dc=local",
        ],
    ];

    protected $sortEntries = [
        'count' => 2,
        0 => [
            'cn' => [
                'count' => 1,
                0 => "Smith, Archie",
            ],
            0 => "cn",
            'sn' => [
                'count' => 1,
                0 => "Smith",
            ],
            1 => "sn",
            'givenname' => [
                'count' => 1,
                0 => "Archie",
            ],
            2 => "givenname",
            'whencreated' => [
                'count' => 1,
                0 => "19960622123421Z",
            ],
            3 => "whencreated",
            'count' => 3,
            'dn' => "CN=Smith\, Archie,OU=DE,OU=Employees,DC=example,DC=local",
        ],
        1 => [
            'cn' => [
                'count' => 1,
                0 => "Smith, John",
            ],
            0 => "cn",
            'sn' => [
                'count' => 1,
                0 => "Smith",
            ],
            1 => "sn",
            'givenname' => [
                'count' => 1,
                0 => "John",
            ],
            2 => "givenname",
            'whenCreated' => [
                'count' => 1,
                0 => "19920622123421Z",
            ],
            3 => "whenCreated",
            'count' => 3,
            'dn' => "CN=Smith\, John,OU=DE,OU=Employees,DC=example,DC=local",

        ]
    ];

    /**
     * @var QueryOperation
     */
    protected $operation;

    /**
     * @var OperatorCollection
     */
    protected $filter;

    function let(LdapConnectionInterface $connection)
    {
        $attribbutes = [
            'defaultNamingContext' => 'dc=example,dc=local',
            'configurationNamingContext' => 'cn=Configuration,dc=example,dc=local',
        ];
        $rootDse = new LdapObject($attribbutes);

        $this->filter = new OperatorCollection();
        $this->filter->add(new Comparison('foo','=','bar'));
        $this->operation = new QueryOperation($this->filter);
        $this->operation->setFilter($this->filter);

        $this->operation->setAttributes(["cn", "givenName", "foo"]);
        $op = clone $this->operation;
        $op->setFilter($this->filter->toLdapFilter());
        $connection->execute($op)
            ->willReturn($this->ldapEntries);
        $connection->getRootDse()->willReturn($rootDse);
        $connection->getConfig()->willReturn(new DomainConfiguration('example.local'));

        $this->beConstructedWith($connection);
        $this->setQueryOperation($this->operation);

    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Query\LdapQuery');
    }

    function it_should_return_the_query_operation()
    {
        $this->getQueryOperation()->shouldBeEqualTo($this->operation);
    }

    function it_should_allow_setting_the_query_operation()
    {
        $operation = clone $this->operation;
        $operation->setBaseDn('dc=foo,dc=bar');
        $this->setQueryOperation($operation);
        $this->getQueryOperation()->getBaseDn()->shouldBeEqualTo('dc=foo,dc=bar');
    }

    function it_should_return_a_LdapObjectCollection_by_default()
    {
        $this->operation->setAttributes(["cn", "givenName", "foo"]);
        $this->execute()->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObjectCollection');
    }

    function it_should_return_a_result_when_calling_getResult()
    {
        $this->operation->setAttributes(["cn", "givenName", "foo"]);
        $this->getResult()->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObjectCollection');
    }

    function it_should_return_an_array_result_when_calling_getArrayResult()
    {
        $this->operation->setAttributes(["cn", "givenName", "foo"]);
        $this->getArrayResult()->shouldBeArray();
    }

    function it_should_return_a_single_result_when_calling_getSingleResult($connection)
    {
        $result = $this->ldapEntries;
        $result['count'] = 1;
        unset($result[1]);
        $this->operation->setAttributes(['objectGuid']);

        $connection->execute(Argument::that(function($op) {
            return $op->getAttributes() == ['objectGuid'];
        }))->willReturn($result);

        $this->getSingleResult()->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObject');
        $this->getSingleResult(HydratorFactory::TO_ARRAY)->shouldBeArray();
    }

    function it_should_return_a_single_result_when_calling_getOneOrNullResult($connection)
    {
        $result = $this->ldapEntries;
        $result['count'] = 1;
        unset($result[1]);
        $this->operation->setAttributes(['objectGuid']);

        $connection->execute(Argument::that(function($op) {
            return $op->getAttributes() == ['objectGuid'];
        }))->willReturn($result);

        $this->getOneOrNullResult()->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObject');
        $this->getOneOrNullResult(HydratorFactory::TO_ARRAY)->shouldBeArray();
    }

    function it_should_throw_MultiResultException_when_many_results_are_returned_when_only_one_is_expected()
    {
        $this->operation->setAttributes(["cn", "givenName", "foo"]);
        $this->shouldThrow('\LdapTools\Exception\MultiResultException')->duringGetSingleResult();
        $this->shouldThrow('\LdapTools\Exception\MultiResultException')->duringGetSingleResult(HydratorFactory::TO_ARRAY);
        $this->shouldThrow('\LdapTools\Exception\MultiResultException')->duringGetOneOrNullResult();
        $this->shouldThrow('\LdapTools\Exception\MultiResultException')->duringGetOneOrNullResult(HydratorFactory::TO_ARRAY);
    }

    function it_should_throw_EmptyResultException_when_no_results_are_returned_but_one_is_expected($connection)
    {
        $this->operation->setAttributes(['objectGuid']);
        $connection->execute(Argument::that(function($op) {
            return $op->getAttributes() == ['objectGuid'];
        }))->willReturn([]);

        $this->shouldThrow('\LdapTools\Exception\EmptyResultException')->duringGetSingleResult();
        $this->shouldThrow('\LdapTools\Exception\EmptyResultException')->duringGetSingleResult(HydratorFactory::TO_ARRAY);
    }

    function it_should_return_null_when_calling_getOneOrNullResult_and_no_results_are_found($connection)
    {
        $this->operation->setAttributes(['objectGuid']);
        $connection->execute(Argument::that(function($op) {
            return $op->getAttributes() == ['objectGuid'];
        }))->willReturn([]);

        $this->getOneOrNullResult()->shouldBeNull();
        $this->getOneOrNullResult(HydratorFactory::TO_ARRAY)->shouldBeNull();
    }

    function it_should_return_a_single_attribute_value_when_calling_getSingleScalarResult($connection)
    {
        $result = $this->ldapEntries;
        $result['count'] = 1;
        unset($result[1]);
        $this->operation->setAttributes(['sn']);

        $connection->execute(Argument::that(function($op) {
            return $op->getAttributes() == ['sn'];
        }))->willReturn($result);

        $this->getSingleScalarResult()->shouldBeEqualTo('Bourke');
    }

    function it_should_throw_an_error_when_calling_getSingleScalarResult_and_the_attribute_doesnt_exist($connection)
    {
        $result = $this->ldapEntries;
        $result['count'] = 1;
        unset($result[1]);
        $this->operation->setAttributes(['foo']);

        $connection->execute(Argument::that(function($op) {
            return $op->getAttributes() == ['foo'];
        }))->willReturn($result);

        $this->shouldThrow('\LdapTools\Exception\AttributeNotFoundException')->duringGetSingleScalarResult();
    }

    function it_should_throw_an_exception_when_calling_getSingleScalarResult_and_no_LDAP_object_is_found($connection)
    {
        $this->operation->setAttributes(['sn']);

        $connection->execute(Argument::that(function($op) {
            return $op->getAttributes() == ['sn'];
        }))->willReturn(['count' => 0]);

        $this->shouldThrow('\LdapTools\Exception\EmptyResultException')->duringGetSingleScalarResult();
    }

    function it_should_throw_an_exception_when_calling_getSingleScalarResult_and_more_than_one_LDAP_object_is_found($connection)
    {
        $this->operation->setAttributes(['sn']);

        $connection->execute(Argument::that(function($op) {
            return $op->getAttributes() == ['sn'];
        }))->willReturn($this->ldapEntries);

        $this->shouldThrow('\LdapTools\Exception\MultiResultException')->duringGetSingleScalarResult();
    }

    function it_should_throw_an_exception_when_calling_getSingleScalarResult_and_more_than_one_attribute_is_selected($connection)
    {
        $this->operation->setAttributes(['sn','givenname']);
        $connection->execute($this->operation)->willReturn($this->ldapEntries);

        $this->shouldThrow('\LdapTools\Exception\LdapQueryException')->duringGetSingleScalarResult();
        $this->operation->setAttributes(['*']);
        $e = new LdapQueryException('When retrieving a single value you should only select a single attribute. All are selected.');
        $this->shouldThrow($e)->duringGetSingleScalarResult();
        $this->operation->setAttributes(['**']);
        $this->shouldThrow($e)->duringGetSingleScalarResult();
    }

    function it_should_return_a_single_attribute_value_when_calling_getSingleScalarOrNullResult($connection)
    {
        $result = $this->ldapEntries;
        $result['count'] = 1;
        unset($result[1]);
        $this->operation->setAttributes(['sn']);

        $connection->execute(Argument::that(function($op) {
            return $op->getAttributes() == ['sn'];
        }))->willReturn($result);

        $this->getSingleScalarOrNullResult()->shouldBeEqualTo('Bourke');
    }

    function it_should_return_a_null_value_when_calling_getSingleScalarOrNullResult_and_no_attribute_is_found($connection)
    {
        $result = $this->ldapEntries;
        $result['count'] = 1;
        unset($result[1]);
        $this->operation->setAttributes(['foo']);

        $connection->execute(Argument::that(function($op) {
            return $op->getAttributes() == ['foo'];
        }))->willReturn($result);

        $this->getSingleScalarOrNullResult()->shouldBeNull();
    }

    function it_should_throw_an_exception_when_calling_getSingleScalarOrNullResult_and_no_LDAP_object_is_found($connection)
    {
        $this->operation->setAttributes(['sn']);

        $connection->execute(Argument::that(function($op) {
            return $op->getAttributes() == ['sn'];
        }))->willReturn(['count' => 0]);

        $this->shouldThrow('\LdapTools\Exception\EmptyResultException')->duringGetSingleScalarOrNullResult();
    }

    function it_should_throw_an_exception_when_calling_getSingleScalarOrNullResult_and_more_than_one_LDAP_object_is_found($connection)
    {
        $this->operation->setAttributes(['sn']);

        $connection->execute(Argument::that(function($op) {
            return $op->getAttributes() == ['sn'];
        }))->willReturn($this->ldapEntries);

        $this->shouldThrow('\LdapTools\Exception\MultiResultException')->duringGetSingleScalarOrNullResult();
    }

    function it_should_throw_an_exception_when_calling_getSingleScalarOrNullResult_and_more_than_one_attribute_is_selected($connection)
    {
        $this->operation->setAttributes(['sn','givenname']);
        $connection->execute($this->operation)->willReturn($this->ldapEntries);

        $this->shouldThrow('\LdapTools\Exception\LdapQueryException')->duringGetSingleScalarOrNullResult();
    }

    function it_should_set_the_order_by_attributes()
    {
        $this->setOrderBy(['foo' => 'ASC'])->getOrderBy()->shouldBeEqualTo(['foo' => 'ASC']);
    }

    function it_should_have_an_empty_array_for_the_default_order_by()
    {
        $this->getOrderBy()->shouldBeEqualTo([]);
    }

    function it_should_add_order_by_attributes_to_the_selection_if_not_explicitly_done($connection)
    {
        $this->setOrderBy(['foo' => 'ASC']);
        $this->operation->setAttributes(['cn','givenName']);
        $this->operation->setBaseDn('dc=foo,dc=bar');
        
        $connection->execute(Argument::that(function($op) {
            return $op->getAttributes() == ['cn', 'givenName', 'foo'];
        }))->willReturn($this->ldapEntries);
        
        $this->execute(HydratorFactory::TO_ARRAY);
    }

    function it_should_force_arrays_on_multivalued_attributes_when_returning_results($connection)
    {
        $connection->execute(Argument::any())->willReturn($this->ldapEntries);
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setMultivaluedAttributes(['otherHomePhone']);
        $schema->setFilter(new Comparison('foo','=', 'bar'));

        $this->operation->setAttributes(['otherHomePhone']);
        $this->operation->setBaseDn('dc=foo,dc=bar');
        $this->filter->addLdapObjectSchema($schema);

        $this->execute(HydratorFactory::TO_OBJECT)->first()->getOtherHomePhone()->shouldBeArray();
        $this->execute(HydratorFactory::TO_OBJECT)->last()->getOtherHomePhone()->shouldBeArray();
    }

    function it_should_select_all_schema_attributes_with_a_wildcard($connection)
    {
        $map = [
            'firstName' => 'givenName',
            'lastName' => 'sn',
        ];
        $connection->execute($this->operation)->willReturn($this->ldapEntries);
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setAttributeMap($map);
        $schema->setFilter(new Comparison('foo','=', 'bar'));

        $this->operation->setAttributes(['*']);
        $this->operation->setBaseDn('dc=foo,dc=bar');
        $this->operation->getFilter()->addLdapObjectSchema($schema);

        $connection->execute(Argument::that(function($op) {
            return $op->getAttributes() == ['givenName', 'sn'] && $op->getBaseDn() == 'dc=foo,dc=bar';
        }))->willReturn($this->ldapEntries);

        $this->getResult()->first()->toArray()->shouldHaveKeys(array_keys($map));
    }

    function it_should_select_all_LDAP_attributes_with_a_double_wildcard($connection)
    {
        $attributes = [
            "givenname",
            "cn",
            "sn",
            "otherhomephone",
            "dn",
        ];
        $map = [
            'firstName' => 'givenName',
            'lastName' => 'sn',
        ];
        $connection->execute(Argument::that(function($op) {
            return $op->getAttributes() == ['*'];
        }))->willReturn($this->ldapEntries);
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setAttributeMap($map);
        $schema->setFilter(new Comparison('foo','=', 'bar'));

        $this->operation->setAttributes(['**']);
        $this->operation->setBaseDn('dc=foo,dc=bar');
        $this->operation->getFilter()->addLdapObjectSchema($schema);
        $this->getResult()->first()->toArray()->shouldHaveKeys($attributes);
    }

    function it_should_not_add_an_order_by_attribute_to_the_selection_when_a_wildcard_is_used($connection)
    {
        $attributes = [
            "givenname",
            "cn",
            "sn",
            "otherhomephone",
            "dn",
        ];
        $map = [
            'firstName' => 'givenName',
            'lastName' => 'sn',
        ];
        $connection->execute(Argument::that(function($op) {
            return $op->getAttributes() == ['*'];
        }))->willReturn($this->ldapEntries);
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setAttributeMap($map);
        $schema->setFilter(new Comparison('foo','=', 'bar'));

        $this->setOrderBy(['firstName' => 'ASC']);
        $this->operation->setAttributes(['**']);
        $this->operation->setBaseDn('dc=foo,dc=bar');
        $this->operation->getFilter()->addLdapObjectSchema($schema);
        $this->getResult()->first()->toArray()->shouldHaveKeys($attributes);
    }


    function it_should_set_a_base_dn_from_the_schema_if_specified($connection)
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setFilter(new Comparison('foo', '=', 'bar'));
        $schema->setBaseDn('ou=employees,dc=example,dc=local');
        $this->operation->getFilter()->addLdapObjectSchema($schema);

        $connection->execute(Argument::that(function($op) {
            return $op->getBaseDn() == 'ou=employees,dc=example,dc=local';
        }))->willReturn($this->ldapEntries);

        $this->execute();
    }

    function it_should_honor_an_explicitly_set_dn_over_one_from_the_schema_if_specified($connection)
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setFilter(new Comparison('foo','=', 'bar'));
        $schema->setBaseDn('ou=employees,dc=example,dc=local');
        $this->operation->getFilter()->addLdapObjectSchema($schema);
        $this->operation->setBaseDn('dc=foo,dc=bar');

        $connection->execute(Argument::that(function($op) {
            return $op->getBaseDn() == 'dc=foo,dc=bar';
        }))->willReturn($this->ldapEntries);

        $this->execute();
    }

    function it_should_honor_default_attributes_to_select_when_present_in_the_LdapObjectSchema($connection)
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setFilter(new Comparison('foo','=', 'bar'));
        $schema->setAttributesToSelect(['sn','givenName']);
        $this->operation->getFilter()->addLdapObjectSchema($schema);
        $this->operation->setBaseDn('dc=foo,dc=bar');

        $connection->execute(Argument::that(function($op) {
            return $op->getAttributes() == ['sn', 'givenName'];
        }))->willReturn($this->ldapEntries);

        $this->operation->setAttributes([]);
        $this->execute();
    }

    function it_should_override_default_attributes_to_select_when_explicitly_setting_attributes($connection)
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setFilter(new Comparison('foo','=', 'bar'));
        $schema->setAttributesToSelect(['sn','givenName']);
        $this->operation->getFilter()->addLdapObjectSchema($schema);
        $this->operation->setBaseDn('dc=foo,dc=bar');

        $connection->execute(Argument::that(function($op) {
            return $op->getAttributes() == ['bar'];
        }))->willReturn($this->ldapEntries);

        $this->operation->setAttributes(['bar']);
        $this->execute();
    }

    function it_should_resolve_base_dn_parameters_when_querying_ldap($connection)
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setBaseDn('%_configurationnamingcontext_%');
        $schema->setFilter(new Comparison('foo','=', 'bar'));
        $this->operation->getFilter()->addLdapObjectSchema($schema);
        $this->operation->setBaseDn('%_configurationnamingcontext_%');

        $connection->execute(Argument::that(function($op) {
            return $op->getBaseDn() == 'cn=Configuration,dc=example,dc=local';
        }))->willReturn($this->ldapEntries);

        $this->execute();
    }

    function it_should_sort_results_when_specified($connection)
    {
        $this->operation->setAttributes(['givenName', 'sn', 'whenCreated']);
        $this->setOrderBy(['givenName' => 'ASC']);

        $connection->execute(Argument::that(function($op) {
            return $op->getAttributes() == ['givenName', 'sn', 'whenCreated'];
        }))->willReturn($this->sortEntries);

        $this->getArrayResult()->shouldHaveCount(2);
        $this->getResult()->shouldHaveFirstValue('givenName', 'Archie');
        $this->setOrderBy(['givenName' => 'DESC']);
        $this->getResult()->shouldHaveFirstValue('givenName', 'John');
    }

    function it_should_sort_results_for_multiple_aliases($connection)
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
        $ou = $schemaFactory->get('ad','ou');
        $container = $schemaFactory->get('ad','container');;

        $filter = new OperatorCollection();
        $filter->addLdapObjectSchema($ou);
        $filter->addLdapObjectSchema($container);
        $this->operation->setFilter($filter);
        $this->operation->setAttributes([]);
        
        $connection->execute(Argument::that(function($op) {
            return $op->getFilter() == '(objectClass=organizationalUnit)';
        }))->shouldBeCalled()->willReturn($this->ous);
        $connection->execute(Argument::that(function($op) {
            return $op->getFilter() == '(&(objectCategory=container))';
        }))->shouldBeCalled()->willReturn($this->containers);

        $this->setOrderBy([
            'Name' => LdapQuery::ORDER['ASC'],
            'ou.Description' => LdapQuery::ORDER['DESC'],
        ]);
        $this->getResult()->shouldHavePlaceKeyAndValue(0, 'name', 'Computers');
        $this->getResult()->shouldHavePlaceKeyAndValue(1, 'name', 'Employees');
        $this->getResult()->shouldHavePlaceKeyAndValue(2, 'name', 'Users');
        $this->getResult()->shouldHavePlaceKeyAndValue(3, 'name', 'West');
    }
    
    function it_should_query_results_from_multiple_schema_types($connection)
    {
        $foo = new LdapObjectSchema('foo','foo');
        $bar = new LdapObjectSchema('foo','bar');
        $foo->setFilter(new Comparison('foo','=','bar'));
        $bar->setFilter(new Comparison('bar','=','foo'));

        $map = [
            'firstName' => 'givenname',
            'lastName' => 'sn',
            'created' => 'whencreated',
            'name' => 'cn',
        ];
        $bar->setAttributeMap($map);
        $bar->setAttributesToSelect(['name', 'created']);
        $bar->setConverterMap(['generalized_time' => ['created']]);
        $foo->setAttributeMap($map);
        $foo->setAttributesToSelect(['firstName', 'lastName']);
        
        $fb = new FilterBuilder();
        
        $filter = new OperatorCollection();
        $filter->addLdapObjectSchema($foo);
        $filter->addLdapObjectSchema($bar);
        $filter->add($fb->bAnd(
            $fb->startsWith('foo.firstName','J'),
            $fb->startsWith('bar.name', 'Smith'),
            $fb->present('lastName')
        ));
        $this->operation->setFilter($filter);
        $this->operation->setAttributes([]);

        $connection->execute(Argument::that(function($op) {
            return $op->getFilter() == '(&(foo=bar)(&(givenname=J*)(sn=*)))'
                && $op->getAttributes() == ['givenname', 'sn'];
        }))->shouldBeCalled()->willReturn($this->ldapEntries);

        $connection->execute(Argument::that(function($op) {
            return $op->getFilter() == '(&(bar=foo)(&(cn=Smith*)(sn=*)))'
                && $op->getAttributes() == ['cn', 'whencreated'];
        }))->shouldBeCalled()->willReturn($this->sortEntries);
        
        $this->getResult()->count()->shouldBeEqualTo(4);
        $this->getArrayResult()->shouldHaveCount(4);
    }

    function it_should_limit_the_results_for_subsequent_operations_if_a_size_limit_is_set_so_we_dont_go_over_the_limit($connection)
    {
        $foo = new LdapObjectSchema('foo','foo');
        $bar = new LdapObjectSchema('foo','bar');
        $foo->setFilter(new Comparison('foo','=','bar'));
        $bar->setFilter(new Comparison('bar','=','foo'));

        $filter = new OperatorCollection();
        $filter->addLdapObjectSchema($foo);
        $filter->addLdapObjectSchema($bar);
        $this->operation->setFilter($filter);
        $this->operation->setAttributes([]);
        $this->operation->setSizeLimit(4);

        $connection->execute(Argument::that(function($op) {
            return $op->getFilter() == '(foo=bar)' && $op->getSizeLimit() == 4;
        }))->shouldBeCalled()->willReturn($this->ldapEntries);

        // The above returns 2 results, since the limit is 4 this next call should be set to a max of 2...
        $connection->execute(Argument::that(function($op) {
            return $op->getFilter() == '(bar=foo)' && $op->getSizeLimit() == 2;
        }))->shouldBeCalled()->willReturn($this->sortEntries);

        $this->getResult();
    }

    function it_should_sort_case_insensitive_by_default()
    {
        $this->getIsCaseSensitiveSort()->shouldBeEqualTo(false);
    }

    function it_should_sort_case_sensitive_if_specified($connection)
    {
        $this->setIsCaseSensitiveSort(true)->shouldReturnAnInstanceOf('LdapTools\Query\LdapQuery');
        $this->operation->setAttributes(['givenName', 'sn', 'whenCreated']);
        $this->setOrderBy(['givenName' => 'ASC']);

        $entries = $this->sortEntries;
        $entries[1]['givenname'][0] = 'archie';
        $connection->execute(Argument::that(function($op) {
            return $op->getAttributes() == ['givenName', 'sn', 'whenCreated'];
        }))->willReturn($entries);

        $this->getResult()->shouldHaveFirstValue('givenName', 'archie');
        $this->setOrderBy(['givenName' => 'DESC']);
        $this->getResult()->shouldHaveFirstValue('givenName', 'Archie');
    }


    function it_should_set_whether_or_not_to_use_the_cache()
    {
        $this->useCache(true)->shouldReturnAnInstanceOf('LdapTools\Query\LdapQuery');
        $this->getQueryOperation()->getUseCache()->shouldBeEqualTo(true);
    }

    function it_should_set_whether_or_not_to_execute_on_a_cache_miss()
    {
        $this->executeOnCacheMiss(false)->shouldReturnAnInstanceOf('LdapTools\Query\LdapQuery');
        $this->getQueryOperation()->getExecuteOnCacheMiss()->shouldBeEqualTo(false);
    }

    function it_should_set_whether_or_not_to_invalidate_the_cache()
    {
        $this->invalidateCache(true)->shouldReturnAnInstanceOf('LdapTools\Query\LdapQuery');
        $this->getQueryOperation()->getInvalidateCache()->shouldBeEqualTo(true);
    }

    function it_should_set_a_cache_expiration()
    {
        $date = new \DateTime();
        $this->expireCacheAt($date)->shouldReturnAnInstanceOf('LdapTools\Query\LdapQuery');
        $this->getQueryOperation()->getExpireCacheAt()->shouldBeEqualTo($date);
    }

    public function getMatchers()
    {
        return [
            'haveKeys' => function($subject, $keys) {
                return (count(array_intersect_key(array_flip($keys), $subject)) === count($keys));
            },
            'haveFirstValue' => function ($subject, $key, $value) {
                $subject = is_array($subject) ? reset($subject) : $subject->first();
                $subject = is_array($subject) ? $subject[$key] : $subject->get($key);
                return ($subject === $value);
            },
            'havePlaceKeyAndValue' => function ($subject, $place, $key, $value) {
                $subject = is_array($subject) ? $subject[$place] : $subject->toArray()[$place];
                $subject = is_array($subject) ? $subject[$key] : $subject->get($key);
                return ($subject === $value);
            },
        ];
    }
}
