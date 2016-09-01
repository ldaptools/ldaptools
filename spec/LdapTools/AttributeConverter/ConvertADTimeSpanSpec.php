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

use LdapTools\Utilities\ADTimeSpan;
use PhpSpec\ObjectBehavior;

class ConvertADTimeSpanSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\AttributeConverter\ConvertADTimeSpan');
    }

    function it_should_convert_an_ADTimeSpec_to_a_string_for_LDAP()
    {
        $adTimeSpan = (new ADTimeSpan())->setDays(10);
        $this->toLdap($adTimeSpan)->shouldBeEqualTo('-8640000000000');
    }

    function it_should_allow_a_ADTimeSpan_going_to_LDAP_set_to_a_Never_value()
    {
        $adTimeSpan = (new ADTimeSpan())->setNever(true);
        $this->toLdap($adTimeSpan)->shouldBeEqualTo(ADTimeSpan::NEVER);
    }

    function it_should_error_when_not_an_instace_of_ADTimeSpan_going_to_ldap()
    {
        $this->shouldThrow('\LdapTools\Exception\AttributeConverterException')->duringToLdap('foo');
        $this->shouldThrow('\LdapTools\Exception\AttributeConverterException')->duringToLdap(true);
    }

    function it_should_convert_a_ldap_time_span_value_into_an_ADTimeSpan_object()
    {
        $timeSpan = '-4576900000000';
        $this->fromLdap($timeSpan)->shouldReturnAnInstanceOf('\LdapTools\Utilities\ADTimeSpan');
        $this->fromLdap($timeSpan)->getDays()->shouldBeEqualTo('5');
        $this->fromLdap($timeSpan)->getHours()->shouldBeEqualTo('7');
        $this->fromLdap($timeSpan)->getMinutes()->shouldBeEqualTo('8');
        $this->fromLdap($timeSpan)->getSeconds()->shouldBeEqualTo('10');
        $this->fromLdap(ADTimeSpan::NEVER)->getNever()->shouldBeEqualTo(true);
    }
}
