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

use PhpSpec\ObjectBehavior;

class ConvertBooleanSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\AttributeConverter\ConvertBoolean');
    }

    function it_should_implement_AttributeConverterInterface()
    {
        $this->shouldImplement('\LdapTools\AttributeConverter\AttributeConverterInterface');
    }

    function it_should_return_a_php_bool_from_a_ldap_bool()
    {
        $this->fromLdap('TRUE')->shouldBeBool();
        $this->fromLdap('true')->shouldBeBool();
        $this->fromLdap('FALSE')->shouldBeBool();
        $this->fromLdap('false')->shouldBeBool();
    }

    function it_should_return_a_php_true_bool_from_a_ldap_true_bool()
    {
        $this->fromLdap('TRUE')->shouldBeEqualTo(true);
    }

    function it_should_return_a_php_false_bool_from_a_ldap_false_bool()
    {
        $this->fromLdap('FALSE')->shouldBeEqualTo(false);
    }

    function it_should_return_a_ldap_false_bool_from_a_php_false_bool()
    {
        $this->toLdap(false)->shouldBeEqualTo('FALSE');
    }

    function it_should_return_a_ldap_true_bool_from_a_php_true_bool()
    {
        $this->toLdap(true)->shouldBeEqualTo('TRUE');
    }

    function it_should_return_a_string_when_calling_toLdap()
    {
        $this->toLdap(true)->shouldBeString();
        $this->toLdap(false)->shouldBeString();
    }
}
