<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Utilities;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LdapUtilitiesSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Utilities\LdapUtilities');
    }

    function it_should_escape_values_when_calling_escape_values()
    {
        $this::escapeValue('*)(user=*)(')->shouldBeEqualTo('\2a\29\28\75\73\65\72\3d\2a\29\28');
    }

    function it_should_ignore_specified_values_when_escaping()
    {
        $this::escapeValue('*)(user=*)(', '*')->shouldBeEqualTo('*\29\28\75\73\65\72\3d*\29\28');
    }

    function it_should_escape_to_hex_by_default()
    {
        $this::escapeValue("foo=bar\r")->shouldBeEqualTo('\66\6f\6f\3d\62\61\72\0d');
    }

    function it_should_allow_escaping_in_the_context_of_a_search_filter()
    {
        $this::escapeValue("cn=test=,stuff )", null, LDAP_ESCAPE_FILTER)->shouldBeEqualTo('cn=test=,stuff \29');
    }

    function it_should_allow_escaping_a_dn()
    {
        $this::escapeValue(' Joe,= Smith ', null, LDAP_ESCAPE_DN)->shouldBeEqualTo('\20Joe\2c\3d Smith\20');
    }

    function it_should_encode_carriage_returns_when_escaping_a_dn()
    {
        $this->escapeValue("Before\rAfter", null, LDAP_ESCAPE_DN)
            ->shouldBeEqualTo('Before\0dAfter');
        $this->escapeValue("Before\rAfter", null, LDAP_ESCAPE_FILTER)
            ->shouldBeEqualTo("Before\rAfter");
    }

    function it_should_unescape_hex_values_back_to_a_string()
    {
        $this::unescapeValue('\46\6f\6f\3d\42\61\72')->shouldBeEqualTo('Foo=Bar');
    }

    function it_should_explode_a_dn_to_an_array()
    {
        $this::explodeDn('cn=Foo,dc=foo,dc=bar')->shouldHaveCount(3);
        $this::explodeDn('cn=Foo,dc=foo,dc=bar')->shouldBeEqualTo(['Foo','foo', 'bar']);
        $this::explodeDn('cn=Foo,dc=foo,dc=bar', 0)->shouldBeEqualTo(['cn=Foo','dc=foo', 'dc=bar']);
    }

    function when_exploding_a_dn_it_should_unescape_hex_values()
    {
        $this::explodeDn('cn=Foo\,\=bar,dc=foo,dc=bar')->shouldContain('Foo=bar');
        $this::explodeDn('cn=Foo\,\=bar,dc=foo,dc=bar')->shouldHaveCount(3);
        $this::explodeDn('cn=Foo\,\=bar,dc=foo,dc=bar', 0)->shouldContain('cn=Foo=bar');
    }

    function it_should_throw_an_error_on_an_invalid_dn()
    {
        $this->shouldThrow('\InvalidArgumentException')->during('explodeDn', ['foo-bar']);
    }

    function it_should_implode_a_dn_array_to_a_string_dn()
    {
        $this::implodeDn(['CN=Foo','DC=example','DC=local'])->shouldBeEqualTo('CN=Foo,DC=example,DC=local');
    }

    function it_should_escape_special_characters_when_imploding_a_DN_array()
    {
        $this::implodeDn(['CN=Foo,=Bar','DC=example','DC=local'])->shouldBeEqualTo('CN=Foo\2c\3dBar,DC=example,DC=local');
    }

    function it_should_throw_an_error_when_imploding_a_DN_in_invalid_array_form()
    {
        $this->shouldThrow('\InvalidArgumentException')->during('implodeDn', [['foo', 'bar']]);
    }

    function it_should_encode_values_to_the_desired_type()
    {
        // How to properly test this?
        $this::encode('foo', 'UTF-8')->shouldBeEqualTo('foo');
    }

    function it_should_check_whether_a_dn_is_a_valid_ldap_object()
    {
        $this::isValidLdapObjectDn('cn=foo,dc=example,dc=com')->shouldBeEqualTo(true);
        $this::isValidLdapObjectDn('foo,=bar')->shouldBeEqualTo(false);
    }

    function it_should_check_if_an_attribute_is_a_valid_OID_or_short_name()
    {
        $this->isValidAttributeFormat('1.2.840.113556.1.4.221')->shouldBeEqualTo(true);

        $this->isValidAttributeFormat('foo-bar')->shouldBeEqualTo(true);
        $this->isValidAttributeFormat('foo1-bar')->shouldBeEqualTo(true);
        $this->isValidAttributeFormat('fooBar')->shouldBeEqualTo(true);
        $this->isValidAttributeFormat('имя')->shouldBeEqualTo(true);
        $this->isValidAttributeFormat('名字')->shouldBeEqualTo(true);

        $this->isValidAttributeFormat('1foobar')->shouldBeEqualTo(false);
        $this->isValidAttributeFormat('foo_bar')->shouldBeEqualTo(false);
        $this->isValidAttributeFormat(')(&)')->shouldBeEqualTo(false);
        $this->isValidAttributeFormat('Test=*')->shouldBeEqualTo(false);
        $this->isValidAttributeFormat('foo bar')->shouldBeEqualTo(false);
        $this->isValidAttributeFormat('им=я')->shouldBeEqualTo(false);
        $this->isValidAttributeFormat('名(字')->shouldBeEqualTo(false);
    }
}
