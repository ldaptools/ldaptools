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

class ConvertIntegerSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\AttributeConverter\ConvertInteger');
    }

    function it_should_implement_AttributeConverterInterface()
    {
        $this->shouldImplement('\LdapTools\AttributeConverter\AttributeConverterInterface');
    }

    function it_should_return_a_string_when_calling_toLdap()
    {
        $this->toLdap(123)->shouldBeString();
        $this->toLdap(0)->shouldBeString();
    }

    function it_should_return_a_integer_when_calling_fromLdap()
    {
        $this->fromLdap('123')->shouldBeInteger();
        $this->fromLdap('0')->shouldBeInteger();
    }

    function it_should_convert_the_string_integer_from_ldap_to_the_correct_integer()
    {
        $this->fromLdap('123')->shouldBeEqualTo(123);
        $this->fromLdap('0')->shouldBeEqualTo(0);
    }

    function it_should_convert_the_integer_from_php_to_the_correct_string_integer_for_ldap()
    {
        $this->toLdap(123)->shouldBeEqualTo('123');
        $this->toLdap(0)->shouldBeEqualTo('0');
    }
}
