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

use LdapTools\DomainConfiguration;
use LdapTools\Exception\LdapQueryException;
use LdapTools\Factory\HydratorFactory;
use LdapTools\Object\LdapObject;
use LdapTools\Operation\QueryOperation;
use LdapTools\Schema\LdapObjectSchema;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LdapQuerySpec extends ObjectBehavior
{
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

    protected $ldap;

    /**
     * @var QueryOperation
     */
    protected $operation;

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $ldap
     */
    function let($ldap)
    {
        $attribbutes = [
            'defaultNamingContext' => 'dc=example,dc=local',
            'configurationNamingContext' => 'cn=Configuration,dc=example,dc=local',
        ];
        $rootDse = new LdapObject($attribbutes);

        $this->operation = new QueryOperation();
        $this->operation->setAttributes(["cn", "givenName", "foo"]);
        $ldap->execute($this->operation)
            ->willReturn($this->ldapEntries);
        $ldap->getRootDse()->willReturn($rootDse);

        $this->beConstructedWith($ldap);
        $this->setQueryOperation($this->operation);
        $ldap->getConfig()->willReturn(new DomainConfiguration('example.local'));
        $this->ldap = $ldap;
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

    function it_should_return_a_single_result_when_calling_getSingleResult()
    {
        $result = $this->ldapEntries;
        $result['count'] = 1;
        unset($result[1]);
        $this->operation->setAttributes(['objectGuid']);
        $this->ldap->execute($this->operation)->willReturn($result);

        $this->getSingleResult()->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObject');
        $this->getSingleResult(HydratorFactory::TO_ARRAY)->shouldBeArray();
    }

    function it_should_return_a_single_result_when_calling_getOneOrNullResult()
    {
        $result = $this->ldapEntries;
        $result['count'] = 1;
        unset($result[1]);
        $this->operation->setAttributes(['objectGuid']);
        $this->ldap->execute($this->operation)->willReturn($result);

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

    function it_should_throw_EmptyResultException_when_no_results_are_returned_but_one_is_expected()
    {
        $this->operation->setAttributes(['objectGuid']);
        $this->ldap->execute($this->operation)->willReturn([]);

        $this->shouldThrow('\LdapTools\Exception\EmptyResultException')->duringGetSingleResult();
        $this->shouldThrow('\LdapTools\Exception\EmptyResultException')->duringGetSingleResult(HydratorFactory::TO_ARRAY);
    }

    function it_should_return_null_when_calling_getOneOrNullResult_and_no_results_are_found()
    {
        $this->operation->setAttributes(['objectGuid']);
        $this->ldap->execute($this->operation)->willReturn([]);

        $this->getOneOrNullResult()->shouldBeNull();
        $this->getOneOrNullResult(HydratorFactory::TO_ARRAY)->shouldBeNull();
    }

    function it_should_return_a_single_attribute_value_when_calling_getSingleScalarResult()
    {
        $result = $this->ldapEntries;
        $result['count'] = 1;
        unset($result[1]);
        $this->operation->setAttributes(['sn']);
        $this->ldap->execute($this->operation)->willReturn($result);

        $this->getSingleScalarResult()->shouldBeEqualTo('Bourke');
    }

    function it_should_throw_an_error_when_calling_getSingleScalarResult_and_the_attribute_doesnt_exist()
    {
        $result = $this->ldapEntries;
        $result['count'] = 1;
        unset($result[1]);
        $this->operation->setAttributes(['foo']);
        $this->ldap->execute($this->operation)->willReturn($result);

        $this->shouldThrow('\LdapTools\Exception\AttributeNotFoundException')->duringGetSingleScalarResult();
    }

    function it_should_throw_an_exception_when_calling_getSingleScalarResult_and_no_LDAP_object_is_found()
    {
        $this->operation->setAttributes(['sn']);
        $this->ldap->execute($this->operation)->willReturn(['count' => 0]);

        $this->shouldThrow('\LdapTools\Exception\EmptyResultException')->duringGetSingleScalarResult();
    }

    function it_should_throw_an_exception_when_calling_getSingleScalarResult_and_more_than_one_LDAP_object_is_found()
    {
        $this->operation->setAttributes(['sn']);
        $this->ldap->execute($this->operation)->willReturn($this->ldapEntries);

        $this->shouldThrow('\LdapTools\Exception\MultiResultException')->duringGetSingleScalarResult();
    }

    function it_should_throw_an_exception_when_calling_getSingleScalarResult_and_more_than_one_attribute_is_selected()
    {
        $this->operation->setAttributes(['sn','givenname']);
        $this->ldap->execute($this->operation)->willReturn($this->ldapEntries);

        $this->shouldThrow('\LdapTools\Exception\LdapQueryException')->duringGetSingleScalarResult();
        $this->operation->setAttributes(['*']);
        $e = new LdapQueryException('When retrieving a single value you should only select a single attribute. All are selected.');
        $this->shouldThrow($e)->duringGetSingleScalarResult();
        $this->operation->setAttributes(['**']);
        $this->shouldThrow($e)->duringGetSingleScalarResult();
    }

    function it_should_return_a_single_attribute_value_when_calling_getSingleScalarOrNullResult()
    {
        $result = $this->ldapEntries;
        $result['count'] = 1;
        unset($result[1]);
        $this->operation->setAttributes(['sn']);
        $this->ldap->execute($this->operation)->willReturn($result);

        $this->getSingleScalarOrNullResult()->shouldBeEqualTo('Bourke');
    }

    function it_should_return_a_null_value_when_calling_getSingleScalarOrNullResult_and_no_attribute_is_found()
    {
        $result = $this->ldapEntries;
        $result['count'] = 1;
        unset($result[1]);
        $this->operation->setAttributes(['foo']);
        $this->ldap->execute($this->operation)->willReturn($result);

        $this->getSingleScalarOrNullResult()->shouldBeNull();
    }

    function it_should_throw_an_exception_when_calling_getSingleScalarOrNullResult_and_no_LDAP_object_is_found()
    {
        $this->operation->setAttributes(['sn']);
        $this->ldap->execute($this->operation)->willReturn(['count' => 0]);

        $this->shouldThrow('\LdapTools\Exception\EmptyResultException')->duringGetSingleScalarOrNullResult();
    }

    function it_should_throw_an_exception_when_calling_getSingleScalarOrNullResult_and_more_than_one_LDAP_object_is_found()
    {
        $this->operation->setAttributes(['sn']);
        $this->ldap->execute($this->operation)->willReturn($this->ldapEntries);

        $this->shouldThrow('\LdapTools\Exception\MultiResultException')->duringGetSingleScalarOrNullResult();
    }

    function it_should_throw_an_exception_when_calling_getSingleScalarOrNullResult_and_more_than_one_attribute_is_selected()
    {
        $this->operation->setAttributes(['sn','givenname']);
        $this->ldap->execute($this->operation)->willReturn($this->ldapEntries);

        $this->shouldThrow('\LdapTools\Exception\LdapQueryException')->duringGetSingleScalarOrNullResult();
    }

    function it_should_set_the_LdapObjectSchemas_when_calling_setLdapObjectSchemas()
    {
        $schema = new LdapObjectSchema('foo','bar');

        $this->setLdapObjectSchemas($schema)->getLdapObjectSchemas()->shouldBeEqualTo([$schema]);
    }

    function it_should_set_the_order_by_attributes()
    {
        $this->setOrderBy(['foo' => 'ASC'])->getOrderBy()->shouldBeEqualTo(['foo' => 'ASC']);
    }

    function it_should_have_an_empty_array_for_the_default_order_by()
    {
        $this->getOrderBy()->shouldBeEqualTo([]);
    }

    function it_should_add_order_by_attributes_to_the_selection_if_not_explicitly_done()
    {
        $this->setOrderBy(['foo' => 'ASC']);
        $this->operation->setAttributes(['cn','givenName']);
        $this->operation->setBaseDn('dc=foo,dc=bar');
        $this->execute(HydratorFactory::TO_ARRAY);
    }

    function it_should_force_arrays_on_multivalued_attributes_when_returning_results()
    {
        $this->ldap->execute(Argument::any())->willReturn($this->ldapEntries);
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setMultivaluedAttributes(['otherHomePhone']);

        $this->operation->setAttributes(['otherHomePhone']);
        $this->operation->setBaseDn('dc=foo,dc=bar');
        $this->setLdapObjectSchemas($schema);
        $this->execute(HydratorFactory::TO_OBJECT)->first()->getOtherHomePhone()->shouldBeArray();
        $this->execute(HydratorFactory::TO_OBJECT)->last()->getOtherHomePhone()->shouldBeArray();
    }

    function it_should_select_all_schema_attributes_with_a_wildcard()
    {
        $map = [
            'firstName' => 'givenName',
            'lastName' => 'sn',
        ];
        $this->ldap->execute($this->operation)->willReturn($this->ldapEntries);
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setAttributeMap($map);

        $this->operation->setAttributes(['givenName','sn']);
        $this->operation->setAttributes(['*']);
        $this->operation->setBaseDn('dc=foo,dc=bar');
        $this->setLdapObjectSchemas($schema);
        $this->getResult()->first()->toArray()->shouldHaveKeys(array_keys($map));
    }

    function it_should_select_all_LDAP_attributes_with_a_double_wildcard()
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
        $this->ldap->execute($this->operation)->willReturn($this->ldapEntries);
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setAttributeMap($map);

        $this->operation->setAttributes(['**']);
        $this->operation->setBaseDn('dc=foo,dc=bar');
        $this->setLdapObjectSchemas($schema);
        $this->getResult()->first()->toArray()->shouldHaveKeys($attributes);
    }

    function it_should_not_add_an_order_by_attribute_to_the_selection_when_a_wildcard_is_used()
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
        $this->ldap->execute($this->operation)->willReturn($this->ldapEntries);
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setAttributeMap($map);

        $this->setOrderBy(['firstName' => 'ASC']);
        $this->operation->setAttributes(['**']);
        $this->operation->setBaseDn('dc=foo,dc=bar');
        $this->setLdapObjectSchemas($schema);
        $this->getResult()->first()->toArray()->shouldHaveKeys($attributes);
    }


    function it_should_set_a_base_dn_from_the_schema_if_specified()
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setObjectClass('foo');
        $schema->setBaseDn('ou=employees,dc=example,dc=local');
        $this->setLdapObjectSchemas($schema);

        $this->execute();
        $this->getQueryOperation()->getBaseDn()->shouldBeEqualTo('ou=employees,dc=example,dc=local');
    }

    function it_should_honor_an_explicitly_set_dn_over_one_from_the_schema_if_specified()
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setObjectClass('foo');
        $schema->setBaseDn('ou=employees,dc=example,dc=local');
        $this->setLdapObjectSchemas($schema);

        $this->operation->setBaseDn('dc=foo,dc=bar');
        $this->execute();
        $this->getQueryOperation()->getBaseDn()->shouldNotBeEqualTo('ou=employees,dc=example,dc=local');
    }

    function it_should_resolve_base_dn_parameters_when_querying_ldap()
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setBaseDn('%_configurationnamingcontext_%');
        $this->setLdapObjectSchemas($schema);

        $this->operation->setBaseDn('%_configurationnamingcontext_%');
        $this->execute();
        $this->getQueryOperation()->getBaseDn()->shouldBeEqualTo('cn=Configuration,dc=example,dc=local');
    }

    public function getMatchers()
    {
        return [
            'haveKeys' => function($subject, $keys) {
                return (count(array_intersect_key(array_flip($keys), $subject)) === count($keys));
            },
        ];
    }
}
