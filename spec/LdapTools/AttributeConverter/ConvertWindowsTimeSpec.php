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

class ConvertWindowsTimeSpec extends ObjectBehavior
{
    protected $timestamp = "130660331300000000";

    protected $genTime = "20150118053850";

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\AttributeConverter\ConvertWindowsTime');
    }

    function it_should_implement_AttributeConverterInterface()
    {
        $this->shouldImplement('\LdapTools\AttributeConverter\AttributeConverterInterface');
    }

    function it_should_return_a_DateTime_object_with_the_correct_time_when_calling_fromLdap()
    {
        $this->fromLdap($this->timestamp)->shouldBeLike(new \DateTime($this->genTime, new \DateTimeZone('UTC')));
    }

    function it_should_return_a_windows_timestamp_when_calling_toLdap()
    {
        $this->toLdap(new \DateTime($this->genTime, new \DateTimeZone('UTC')))->shouldBeEqualTo($this->timestamp);
    }

    function it_should_error_when_the_value_going_to_ldap_is_not_a_datetime_object()
    {
        $this->shouldThrow('\LdapTools\Exception\AttributeConverterException')->duringToLdap('foo');
    }
}
