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

use LdapTools\AttributeConverter\AttributeConverterInterface;
use PhpSpec\ObjectBehavior;

class ConvertLockoutTimeSpec extends ObjectBehavior
{
    function let()
    {
        $this->setOptions(['bool' => 'locked']);
        $this->setAttribute('locked');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\AttributeConverter\ConvertLockoutTime');
    }

    function it_should_implement_AttributeConverterInterface()
    {
        $this->shouldImplement('\LdapTools\AttributeConverter\AttributeConverterInterface');
    }

    function it_should_convert_a_lockoutTime_to_a_bool_for_whether_it_is_locked()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_FROM);

        $this->fromLdap('0')->shouldBeEqualTo(false);
        $this->fromLdap('1')->shouldBeEqualTo(true);
        $this->fromLdap('130660331300000000')->shouldBeEqualTo(true);
    }

    function it_should_convert_a_lockoutTime_to_a_DateTime_object_if_the_attribute_is_no_expecting_bool()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_FROM);
        $this->setAttribute('lockedDate');

        $this->fromLdap('0')->shouldBeEqualTo(false);
        $this->fromLdap('130660331300000000')->format('Y-m-d H:i:s')->shouldEqual('2015-01-18 05:38:50');
    }

    function it_should_convert_a_datetime_object_to_windows_time_when_going_to_ldap()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);
        $this->setAttribute('lockedDate');

        $this->toLdap(new \DateTime("20150118053850", new \DateTimeZone('UTC')))->shouldBeEqualTo('130660331300000000');
    }

    function it_should_convert_a_bool_false_to_a_zero_when_going_to_ldap()
    {
        $this->toLdap(false)->shouldBeEqualTo('0');
    }

    function it_should_throw_an_attribute_converter_exception_if_the_value_to_ldap_is_not_supported()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);
        $this->setAttribute('lockedDate');

        $this->shouldThrow('\LdapTools\Exception\AttributeConverterException')->duringToLdap(new \SplObjectStorage());
    }

    function it_should_convert_a_bool_to_the_correct_LDAP_filter_when_querying()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);

        $this->toLdap(false)->toLdapFilter()->shouldBeEqualTo('(|(!(lockoutTime=*))(lockoutTime=0))');
        $this->toLdap(true)->toLdapFilter()->shouldBeEqualTo('(lockoutTime>=1)');
    }
}
