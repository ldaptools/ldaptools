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

use LdapTools\Connection\LdapConnection;
use LdapTools\Factory\HydratorFactory;
use LdapTools\Query\LdapQuery;
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

    function let(LdapConnection $ldap)
    {
        $ldap->search(Argument::any(), ["cn", "givenName", "foo"], Argument::any(), Argument::any(), Argument::any())
            ->willReturn($this->ldapEntries);
        $this->beConstructedWith($ldap);
        $this->ldap = $ldap;
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Query\LdapQuery');
    }

    function it_should_return_a_LdapObjectCollection_by_default()
    {
        $this->setAttributes(["cn", "givenName", "foo"]);
        $this->execute()->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObjectCollection');
    }

    function it_should_return_a_result_when_calling_getResult()
    {
        $this->setAttributes(["cn", "givenName", "foo"]);
        $this->getResult()->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObjectCollection');
    }

    function it_should_return_an_array_result_when_calling_getArrayResult()
    {
        $this->setAttributes(["cn", "givenName", "foo"]);
        $this->getArrayResult()->shouldBeArray();
    }

    function it_should_return_a_single_result_when_calling_getSingleResult()
    {
        $result = $this->ldapEntries;
        $result['count'] = 1;
        unset($result[1]);
        $this->ldap->search(Argument::any(), ["objectGuid"], Argument::any(), Argument::any(), Argument::any())
            ->willReturn($result);

        $this->setAttributes(["objectGuid"]);
        $this->getSingleResult()->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObject');
        $this->getSingleResult(HydratorFactory::TO_ARRAY)->shouldBeArray();
    }

    function it_should_return_a_single_result_when_calling_getOneOrNullResult()
    {
        $result = $this->ldapEntries;
        $result['count'] = 1;
        unset($result[1]);
        $this->ldap->search(Argument::any(), ["objectGuid"], Argument::any(), Argument::any(), Argument::any())
            ->willReturn($result);

        $this->setAttributes(["objectGuid"]);
        $this->getOneOrNullResult()->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObject');
        $this->getOneOrNullResult(HydratorFactory::TO_ARRAY)->shouldBeArray();
    }

    function it_should_throw_MultiResultException_when_many_results_are_returned_when_only_one_is_expected()
    {
        $this->setAttributes(["cn", "givenName", "foo"]);
        $this->shouldThrow('\LdapTools\Exception\MultiResultException')->duringGetSingleResult();
        $this->shouldThrow('\LdapTools\Exception\MultiResultException')->duringGetSingleResult(HydratorFactory::TO_ARRAY);
        $this->shouldThrow('\LdapTools\Exception\MultiResultException')->duringGetOneOrNullResult();
        $this->shouldThrow('\LdapTools\Exception\MultiResultException')->duringGetOneOrNullResult(HydratorFactory::TO_ARRAY);
    }

    function it_should_throw_EmptyResultException_when_no_results_are_returned_but_one_is_expected()
    {
        $this->ldap->search(Argument::any(), ["objectGuid"], Argument::any(), Argument::any(), Argument::any())
            ->willReturn(array());

        $this->setAttributes(["objectGuid"]);
        $this->shouldThrow('\LdapTools\Exception\EmptyResultException')->duringGetSingleResult();
        $this->shouldThrow('\LdapTools\Exception\EmptyResultException')->duringGetSingleResult(HydratorFactory::TO_ARRAY);
    }

    function it_should_return_null_when_calling_getOneOrNullResult_and_no_results_are_found()
    {
        $this->ldap->search(Argument::any(), ["objectGuid"], Argument::any(), Argument::any(), Argument::any())
            ->willReturn(array());

        $this->setAttributes(["objectGuid"]);
        $this->getOneOrNullResult()->shouldBeNull();
        $this->getOneOrNullResult(HydratorFactory::TO_ARRAY)->shouldBeNull();
    }

    function it_should_return_a_single_attribute_value_when_calling_getSingleScalarResult()
    {
        $result = $this->ldapEntries;
        $result['count'] = 1;
        unset($result[1]);
        $this->ldap->search(Argument::any(), ["sn"], Argument::any(), Argument::any(), Argument::any())
            ->willReturn($result);

        $this->setAttributes(["sn"]);
        $this->getSingleScalarResult()->shouldBeEqualTo('Bourke');
    }

    function it_should_throw_an_error_when_calling_getSingleScalarResult_and_the_attribute_doesnt_exist()
    {
        $result = $this->ldapEntries;
        $result['count'] = 1;
        unset($result[1]);
        $this->ldap->search(Argument::any(), ["foo"], Argument::any(), Argument::any(), Argument::any())
            ->willReturn($result);

        $this->setAttributes(["foo"]);
        $this->shouldThrow('\LdapTools\Exception\AttributeNotFoundException')->duringGetSingleScalarResult();
    }

    function it_should_throw_an_exception_when_calling_getSingleScalarResult_and_no_LDAP_object_is_found()
    {
        $this->ldap->search(Argument::any(), ["sn"], Argument::any(), Argument::any(), Argument::any())
            ->willReturn(['count' => 0]);

        $this->setAttributes(["sn"]);
        $this->shouldThrow('\LdapTools\Exception\EmptyResultException')->duringGetSingleScalarResult();
    }

    function it_should_throw_an_exception_when_calling_getSingleScalarResult_and_more_than_one_LDAP_object_is_found()
    {
        $this->ldap->search(Argument::any(), ["sn"], Argument::any(), Argument::any(), Argument::any())
            ->willReturn($this->ldapEntries);

        $this->setAttributes(["sn"]);
        $this->shouldThrow('\LdapTools\Exception\MultiResultException')->duringGetSingleScalarResult();
    }

    function it_should_throw_an_exception_when_calling_getSingleScalarResult_and_more_than_one_attribute_is_selected()
    {
        $this->ldap->search(Argument::any(), ["sn","givenname"], Argument::any(), Argument::any(), Argument::any())
            ->willReturn($this->ldapEntries);

        $this->setAttributes(["sn","givenname"]);
        $this->shouldThrow('\LdapTools\Exception\LdapQueryException')->duringGetSingleScalarResult();
    }

    function it_should_return_a_single_attribute_value_when_calling_getSingleScalarOrNullResult()
    {
        $result = $this->ldapEntries;
        $result['count'] = 1;
        unset($result[1]);
        $this->ldap->search(Argument::any(), ["sn"], Argument::any(), Argument::any(), Argument::any())
            ->willReturn($result);

        $this->setAttributes(["sn"]);
        $this->getSingleScalarOrNullResult()->shouldBeEqualTo('Bourke');
    }

    function it_should_return_a_null_value_when_calling_getSingleScalarOrNullResult_and_no_attribute_is_found()
    {
        $result = $this->ldapEntries;
        $result['count'] = 1;
        unset($result[1]);
        $this->ldap->search(Argument::any(), ["foo"], Argument::any(), Argument::any(), Argument::any())
            ->willReturn($result);

        $this->setAttributes(["foo"]);
        $this->getSingleScalarOrNullResult()->shouldBeNull();
    }

    function it_should_throw_an_exception_when_calling_getSingleScalarOrNullResult_and_no_LDAP_object_is_found()
    {
        $this->ldap->search(Argument::any(), ["sn"], Argument::any(), Argument::any(), Argument::any())
            ->willReturn(['count' => 0]);

        $this->setAttributes(["sn"]);
        $this->shouldThrow('\LdapTools\Exception\EmptyResultException')->duringGetSingleScalarOrNullResult();
    }

    function it_should_throw_an_exception_when_calling_getSingleScalarOrNullResult_and_more_than_one_LDAP_object_is_found()
    {
        $this->ldap->search(Argument::any(), ["sn"], Argument::any(), Argument::any(), Argument::any())
            ->willReturn($this->ldapEntries);

        $this->setAttributes(["sn"]);
        $this->shouldThrow('\LdapTools\Exception\MultiResultException')->duringGetSingleScalarOrNullResult();
    }

    function it_should_throw_an_exception_when_calling_getSingleScalarOrNullResult_and_more_than_one_attribute_is_selected()
    {
        $this->ldap->search(Argument::any(), ["sn","givenname"], Argument::any(), Argument::any(), Argument::any())
            ->willReturn($this->ldapEntries);

        $this->setAttributes(["sn","givenname"]);
        $this->shouldThrow('\LdapTools\Exception\LdapQueryException')->duringGetSingleScalarOrNullResult();
    }

    function it_should_set_the_filter_when_calling_setLdapFilter()
    {
        $filter = '(objectClass=*)';
        $this->setLdapFilter($filter)->getLdapFilter()->shouldBeEqualTo($filter);
    }

    function it_should_set_the_baseDn_when_calling_setBaseDn()
    {
        $baseDn = 'dc=foo,dc=bar';
        $this->setBaseDn($baseDn)->getBaseDn()->shouldBeEqualTo($baseDn);
    }

    function it_should_set_the_page_size_when_calling_setPageSize()
    {
        $pageSize = 1000;
        $this->setPageSize($pageSize)->getPageSize()->shouldBeEqualTo($pageSize);
    }

    function it_should_set_the_attributes_to_get_when_calling_setAttributes()
    {
        $attributes = ['foo', 'bar'];
        $this->setAttributes($attributes)->getAttributes()->shouldBeEqualTo($attributes);
    }

    function it_should_set_the_scope_when_calling_setScope()
    {
        $this->setScope(LdapQuery::SCOPE_BASE)->getScope()->shouldBeEqualTo(LdapQuery::SCOPE_BASE);
    }

    function it_should_throw_InvalidArgumentException_when_setting_an_invalid_scope()
    {
        $this->shouldThrow('\InvalidArgumentException')->duringSetScope('foo');
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
        $this->setAttributes(['cn', 'givenName']);
        $this->setBaseDn('dc=foo,dc=bar');
        $this->execute(HydratorFactory::TO_ARRAY);
    }

    function it_should_force_arrays_on_multivalued_attributes_when_returning_results()
    {
        $this->ldap->getEncoding()->willReturn('UTF-8');
        $this->ldap->search(Argument::any(), Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willReturn($this->ldapEntries);
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setMultivaluedAttributes(['otherHomePhone']);

        $this->setAttributes(['otherHomePhone']);
        $this->setBaseDn('dc=foo,dc=bar');
        $this->setLdapObjectSchemas($schema);
        $this->execute(HydratorFactory::TO_OBJECT)->first()->getOtherHomePhone()->shouldBeArray();
        $this->execute(HydratorFactory::TO_OBJECT)->last()->getOtherHomePhone()->shouldBeArray();
    }
}
