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

class ConvertGpOptionsSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\AttributeConverter\ConvertGpOptions');
    }

    function it_should_implement_AttributeConverterInterface()
    {
        $this->shouldImplement('\LdapTools\AttributeConverter\AttributeConverterInterface');
    }

    function it_should_return_a_php_bool_from_ldap()
    {
        $this->fromLdap('1')->shouldBeEqualTo(true);
        $this->fromLdap('0')->shouldBeEqualTo(false);
    }

    function it_should_return_string_when_going_to_ldap()
    {
        $this->toLdap(true)->shouldBeEqualTo('1');
        $this->toLdap(false)->shouldBeString('0');
    }
}
