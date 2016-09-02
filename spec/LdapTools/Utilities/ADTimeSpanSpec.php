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

use LdapTools\Utilities\ADTimeSpan;
use PhpSpec\ObjectBehavior;

class ADTimeSpanSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Utilities\ADTimeSpan');
    }

    function it_should_be_initializable_with_a_time_from_ldap()
    {
        $this->beConstructedWith('-4576900000000');
        $this->getDays()->shouldBeEqualTo('5');
        $this->getHours()->shouldBeEqualTo('7');
        $this->getMinutes()->shouldBeEqualTo('8');
        $this->getSeconds()->shouldBeEqualTo('10');
    }

    function it_should_initialize_properly_with_a_Never_value_from_LDAP()
    {
        $this->beConstructedWith(ADTimeSpan::NEVER);
        $this->getNever()->shouldBeEqualTo(true);
        $this->getDays()->shouldBeEqualTo(0);
        $this->getHours()->shouldBeEqualTo(0);
        $this->getMinutes()->shouldBeEqualTo(0);
        $this->getSeconds()->shouldBeEqualTo(0);
    }

    function it_should_initialize_properly_with_a_Zero_value_from_LDAP()
    {
        $this->beConstructedWith('0');
        $this->getNever()->shouldBeEqualTo(false);
        $this->getDays()->shouldBeEqualTo(0);
        $this->getHours()->shouldBeEqualTo(0);
        $this->getMinutes()->shouldBeEqualTo(0);
        $this->getSeconds()->shouldBeEqualTo(0);
    }

    function it_should_allow_for_a_value_of_zero_to_go_to_LDAP()
    {
        $this->getLdapValue()->shouldBeEqualTo('0');
    }

    function it_should_set_days_properly()
    {
        $this->setDays(9)->getDays()->shouldBeEqualTo(9);
        $this->getLdapValue()->shouldBeEqualTo('-7776000000000');
    }

    function it_should_set_hours_properly()
    {
        $this->setHours(10)->getHours()->shouldBeEqualTo(10);
        $this->getLdapValue()->shouldBeEqualTo('-360000000000');
    }

    function it_should_set_minutes_properly()
    {
        $this->setMinutes(60)->getMinutes()->shouldBeEqualTo(60);
        $this->getLdapValue()->shouldBeEqualTo('-36000000000');
    }

    function it_should_set_seconds_properly()
    {
        $this->setSeconds(45)->getSeconds()->shouldBeEqualTo(45);
        $this->getLdapValue()->shouldBeEqualTo('-450000000');
    }

    function it_should_chain_calls_when_setting_values()
    {
        $this->setDays(1)->shouldReturnAnInstanceOf('\LdapTools\Utilities\ADTimeSpan');
        $this->setHours(1)->shouldReturnAnInstanceOf('\LdapTools\Utilities\ADTimeSpan');
        $this->setMinutes(1)->shouldReturnAnInstanceOf('\LdapTools\Utilities\ADTimeSpan');
        $this->setSeconds(1)->shouldReturnAnInstanceOf('\LdapTools\Utilities\ADTimeSpan');
    }

    function it_should_have_a_string_representation()
    {
        $this->setDays(5);
        $this->setHours(7);
        $this->setMinutes(8);
        $this->setSeconds(10);
        $this->__toString()->shouldBeEqualTo('5 day(s) 7 hour(s) 8 minute(s) 10 second(s)');
    }

    function it_should_have_a_string_representation_for_Never()
    {
        $this->setNever(true);
        $this->__toString()->shouldBeEqualTo('Never');
    }

    function it_should_get_a_value_formatted_for_LDAP()
    {
        $this->setDays(5);
        $this->setHours(7);
        $this->setMinutes(8);
        $this->setSeconds(10);
        $this->getLdapValue()->shouldBeEqualTo('-4576900000000');
    }

    function it_should_allow_a_time_span_of_never_to_be_set()
    {
        $this->setNever(true);
        $this->getLdapValue()->shouldBeEqualTo(ADTimeSpan::NEVER);
    }
}
