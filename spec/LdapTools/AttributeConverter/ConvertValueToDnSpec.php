<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\AttributeConverter;

use LdapTools\DomainConfiguration;
use LdapTools\Exception\AttributeConverterException;
use LdapTools\Object\LdapObject;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ConvertValueToDnSpec extends ObjectBehavior
{
    protected $entry = [
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

    protected $entryWithSelect = [
        'count' => 1,
        0 => [
            "distinguishedname" => [
                "count" => 1,
                0 => "CN=Foo,DC=bar,DC=foo",
            ],
            0 => "distinguishedName",
            "foo" => [
                "count" => 1,
                0 => "bar",
            ],
            1 => "foo",
            "cn" => [
                "count" => 1,
                0 => "Foo",
            ],
            2 => "cn",
            'count' => 2,
            'dn' => "CN=Foo,DC=bar,DC=foo",
        ],
    ];

    function let(\LdapTools\Connection\LdapConnectionInterface $connection)
    {
        $connection->getConfig()->willReturn(new DomainConfiguration('example.local'));
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\AttributeConverter\ConvertValueToDn');
    }

    function it_should_implement_AttributeConverterInterface()
    {
        $this->shouldImplement('\LdapTools\AttributeConverter\AttributeConverterInterface');
    }

    function it_should_convert_a_dn_to_a_normal_name()
    {
        $this->setOptions(['foo' =>[ 'filter' => ['objectClass' => 'bar'],  'attribute' => 'foo']]);
        $this->setAttribute('foo');
        $this->fromLdap('cn=Foo,dc=bar,dc=foo')->shouldBeEqualTo('Foo');
    }

    function it_should_convert_a_name_back_to_a_dn($connection)
    {
        $connection->execute(Argument::that(function($operation) {
            return $operation->getFilter() == '(&(&(objectClass=bar))(cn=Foo))' && $operation->getAttributes() == ['dn'];
        }))->willReturn($this->entry);
        $this->setOptions(['foo' => [
            'attribute' => 'cn',
            'filter' => [
                'objectClass' => 'bar'
            ],
        ]]);
        $this->setLdapConnection($connection);
        $this->setAttribute('foo');

        $this->toLdap('Foo')->shouldBeEqualTo($this->entry[0]['distinguishedname'][0]);
    }

    function it_should_convert_a_GUID_back_to_a_dn($connection)
    {
        $guid = 'a1131cd3-902b-44c6-b49a-1f6a567cda25';
        $guidHex = '\d3\1c\13\a1\2b\90\c6\44\b4\9a\1f\6a\56\7c\da\25';

        $connection->execute(Argument::that(function($operation) use ($guidHex, $guid) {
            return $operation->getFilter() == '(&(&(objectClass=bar))(|(objectGuid='.$guidHex.')(cn='.$guid.')))';
        }))->willReturn($this->entry);
        $this->setOptions(['foo' => [
            'attribute' => 'cn',
            'filter' => [
                'objectClass' => 'bar'
            ],
        ]]);
        $this->setAttribute('foo');
        $this->setLdapConnection($connection);
        $this->toLdap($guid)->shouldBeEqualTo($this->entry[0]['distinguishedname'][0]);
    }

    function it_should_convert_a_SID_back_to_a_dn($connection)
    {
        $sid = 'S-1-5-21-1004336348-1177238915-682003330-512';
        $sidHex = '\01\05\00\00\00\00\00\05\15\00\00\00\dc\f4\dc\3b\83\3d\2b\46\82\8b\a6\28\00\02\00\00';

        $connection->execute(Argument::that(function($operation) use ($sid, $sidHex) {
            return $operation->getFilter() == '(&(&(objectClass=bar))(|(objectSid='.$sidHex.')(cn='.$sid.')))';
        }))->willReturn($this->entry);
        $this->setOptions(['foo' => [
            'attribute' => 'cn',
            'filter' => [
                'objectClass' => 'bar'
            ],
        ]]);
        $this->setAttribute('foo');
        $this->setLdapConnection($connection);
        $this->toLdap($sid)->shouldBeEqualTo($this->entry[0]['distinguishedname'][0]);
    }

    function it_should_convert_a_LdapObject_back_to_a_dn($connection)
    {
        $dn = 'CN=Chad,OU=Employees,DC=example,DC=com';
        $ldapObject = new LdapObject(['dn' => $dn], ['user'], 'user', 'user');
        $this->setOptions(['foo' => [
            'attribute' => 'cn',
            'filter' => [
                'objectClass' => 'bar'
            ],
        ]]);
        $this->setAttribute('foo');
        $this->setLdapConnection($connection);
        $this->toLdap($ldapObject)->shouldBeEqualTo($dn);
    }

    function it_should_error_if_a_LdapObject_is_missing_a_DN($connection)
    {
        $ldapObject = new LdapObject(['cn' => 'foo'], ['user'], 'user', 'user');
        $this->setOptions(['foo' => [
            'attribute' => 'cn',
            'filter' => [
                'objectClass' => 'bar'
            ],
        ]]);
        $this->setAttribute('foo');
        $this->setLdapConnection($connection);
        $this->shouldThrow('\LdapTools\Exception\AttributeConverterException')->duringToLdap($ldapObject);
    }

    function it_should_convert_a_dn_back_to_a_dn($connection)
    {
        $dn = $this->entry[0]['distinguishedname'][0];
        $connection->execute(Argument::any())->shouldNotBeCalled();
        $this->setOptions(['foo' => [
            'attribute' => 'cn',
            'filter' => [
                'objectClass' => 'bar'
            ],
        ]]);
        $this->setAttribute('foo');
        $this->setLdapConnection($connection);
        $this->toLdap($dn)->shouldBeEqualTo($dn);
    }

    function it_should_convert_a_dn_into_its_common_name()
    {
        $this->setOptions(['foo' =>[ 'filter' => ['objectClass' => 'bar'],  'attribute' => 'foo']]);
        $this->setAttribute('foo');
        $this->fromLdap('cn=Foo\,\=bar,dc=foo,dc=bar')->shouldBeEqualTo('Foo,=bar');
    }

    function it_should_throw_an_error_if_no_options_exist_for_the_current_attribute($connection)
    {
        $this->setLdapConnection($connection);
        $this->shouldThrow('\LdapTools\Exception\AttributeConverterException')->duringToLdap('foo');
    }

    function it_should_display_the_dn_from_ldap_if_specified()
    {
        $this->setOptions(['foo' =>[ 'filter' => ['objectClass' => 'bar'],  'attribute' => 'foo', 'display_dn' => true]]);
        $this->setAttribute('foo');
        $this->fromLdap('cn=Foo,dc=bar,dc=foo')->shouldBeEqualTo('cn=Foo,dc=bar,dc=foo');
    }

    function it_should_allow_an_or_filter_for_an_attribute($connection)
    {
        $connection->execute(Argument::that(function($operation) {
            return $operation->getFilter() == '(&(|(objectClass=bar)(objectClass=foo))(cn=Foo))';
        }))->willReturn($this->entry);
        $this->setOptions(['foo' => [
            'attribute' => 'cn',
            'filter' => [
                'objectClass' => ['bar', 'foo'],
            ],
            'or_filter' => true,
        ]]);
        $this->setAttribute('foo');
        $this->setLdapConnection($connection);
        $this->toLdap('Foo')->shouldBeEqualTo($this->entry[0]['distinguishedname'][0]);
    }

    function it_should_use_a_base_dn_option($connection)
    {
        $connection->execute(Argument::that(function($operation) {
            return $operation->getBaseDn() == 'ou=user,dc=foo,dc=bar';
        }))->shouldBeCalled()->willReturn($this->entry);

        $this->setOptions(['foo' => [
            'attribute' => 'cn',
            'filter' => [
                'objectClass' => 'bar'
            ],
            'base_dn' => 'ou=user,dc=foo,dc=bar',
        ]]);
        $this->setLdapConnection($connection);
        $this->setAttribute('foo');

        $this->toLdap('Foo');
    }

    function it_should_allow_specifying_the_attribute_to_select_when_converting($connection)
    {
        $connection->execute(Argument::that(function($operation) {
            return $operation->getAttributes() == ['foo'];
        }))->shouldBeCalled()->willReturn($this->entryWithSelect);

        $this->setOptions(['foo' => [
            'attribute' => 'cn',
            'filter' => [
                'objectClass' => 'bar'
            ],
            'select' => 'foo',
        ]]);
        $this->setLdapConnection($connection);
        $this->setAttribute('foo');

        $this->toLdap('Foo')->shouldBeEqualTo('bar');
        $this->toLdap(new LdapObject(['foo' => 'bar']))->shouldBeEqualTo('bar');
        $this->shouldThrow('\LdapTools\Exception\AttributeConverterException')->duringToLdap(new LdapObject(['dn' => 'foo']));
    }

    function it_should_accept_a_legacy_dn_from_ldap_if_specified()
    {
        $this->setOptions(['foo' => [
            'attribute' => 'cn',
            'filter' => [
                'objectClass' => 'bar'
            ],
            'legacy_dn' => true,
        ]]);
        $this->setAttribute('foo');

        $this->fromLdap('/o=LdapTools/ou=Exchange Administrative Group (FYDIBOHF23SPDLT)/cn=Foo')->shouldBeEqualTo('Foo');
    }

    function it_should_throw_a_useful_message_if_a_value_cannot_be_converted_from_searching_ldap($connection)
    {
        $connection->execute(Argument::any())->willReturn([]);
        $this->setOptions(['foo' => [
            'attribute' => 'cn',
            'filter' => [
                'objectClass' => 'bar'
            ],
        ]]);
        $this->setLdapConnection($connection);
        $this->setAttribute('foo');

        $this->shouldThrow(new AttributeConverterException('Unable to convert value "Bar" to a dn for attribute "foo"'))->duringToLdap('Bar');
    }
}
