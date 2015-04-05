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
    }

    function it_should_throw_an_error_on_an_invalid_dn()
    {
        $this->shouldThrow('\InvalidArgumentException')->during('explodeDn', ['foo-bar']);
    }

    function it_should_encode_values_to_the_desired_type()
    {
        // How to properly test this?
        $this::encode('foo', 'UTF-8')->shouldBeEqualTo('foo');
    }
}
