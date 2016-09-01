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

class ConvertAccountExpirationSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\AttributeConverter\ConvertAccountExpiration');
    }

    function it_should_implement_AttributeConverterInterface()
    {
        $this->shouldImplement('\LdapTools\AttributeConverter\AttributeConverterInterface');
    }

    function it_should_convert_a_never_expiring_account_to_a_bool_from_ldap()
    {
        $this->fromLdap('0')->shouldBeEqualTo(false);
        $this->fromLdap('9223372036854775807')->shouldBeEqualTo(false);
    }

    function it_should_convert_an_account_with_an_expiration_date_to_a_datetime_object()
    {
        $this->fromLdap('130660331300000000')->shouldReturnAnInstanceOf('\DateTime');
    }

    function it_should_convert_a_datetime_object_to_windows_time_when_going_to_ldap()
    {
        $this->toLdap(new \DateTime("20150118053850", new \DateTimeZone('UTC')))->shouldBeEqualTo('130660331300000000');
    }

    function it_should_convert_a_bool_false_to_a_zero_when_going_to_ldap()
    {
        $this->toLdap(false)->shouldBeEqualTo('0');
    }

    function it_should_throw_an_attribute_converter_exception_if_the_value_to_ldap_is_not_supported()
    {
        $this->shouldThrow('\LdapTools\Exception\AttributeConverterException')->duringToLdap(new \SplObjectStorage());
    }

    function it_should_convert_a_bool_to_the_correct_LDAP_filter_when_querying()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);
        $this->toLdap(false)->toLdapFilter()->shouldBeEqualTo('(|(accountExpires=0)(accountExpires=9223372036854775807))');
        $this->toLdap(true)->toLdapFilter()->shouldBeEqualTo('(&(accountExpires>=1)(accountExpires<=9223372036854775806))');
    }
}
