<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Factory;

use LdapTools\AttributeConverter\ConvertBoolean;
use LdapTools\Schema\LdapObjectSchema;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class AttributeConverterFactorySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Factory\AttributeConverterFactory');
    }

    function it_should_return_ConvertBoolean_when_calling_get_with_convert_bool()
    {
        $this::get('convert_bool')->shouldReturnAnInstanceOf('\LdapTools\AttributeConverter\ConvertBoolean');
    }

    function it_should_return_ConvertGeneralizedTime_when_calling_get_with_convert_generalized_time()
    {
        $this::get('convert_generalized_time')->shouldReturnAnInstanceOf('\LdapTools\AttributeConverter\ConvertGeneralizedTime');
    }

    function it_should_return_ConvertInteger_when_calling_get_with_convert_int()
    {
        $this::get('convert_int')->shouldReturnAnInstanceOf('\LdapTools\AttributeConverter\ConvertInteger');
    }

    function it_should_return_ConvertStringToUtf8_when_calling_get_with_convert_string_to_utf8()
    {
        $this::get('convert_string_to_utf8')->shouldReturnAnInstanceOf('\LdapTools\AttributeConverter\ConvertStringToUtf8');
    }

    function it_should_return_ConvertWindowsGuid_when_calling_get_with_convert_windows_guid()
    {
        $this::get('convert_windows_guid')->shouldReturnAnInstanceOf('\LdapTools\AttributeConverter\ConvertWindowsGuid');
    }

    function it_should_return_ConvertWindowsSid_when_calling_get_with_convert_windows_sid()
    {
        $this::get('convert_windows_sid')->shouldReturnAnInstanceOf('\LdapTools\AttributeConverter\ConvertWindowsSid');
    }

    function it_should_return_ConvertWindowsTime_when_calling_get_with_convert_windows_time()
    {
        $this::get('convert_windows_time')->shouldReturnAnInstanceOf('\LdapTools\AttributeConverter\ConvertWindowsTime');
    }

    function it_should_return_ConvertWindowsGeneralizedTime_when_calling_get_with_convert_windows_generalized_time()
    {
        $this::get('convert_windows_generalized_time')->shouldReturnAnInstanceOf('\LdapTools\AttributeConverter\ConvertWindowsGeneralizedTime');
    }

    function it_should_return_EncodeWindowsPassword_when_calling_get_with_encode_windows_password()
    {
        $this::get('encode_windows_password')->shouldReturnAnInstanceOf('\LdapTools\AttributeConverter\EncodeWindowsPassword');
    }

    function it_should_throw_InvalidArgumentException_when_retrieving_an_invalid_converter_name()
    {
        $this->shouldThrow('\InvalidArgumentException')->duringGet('foo_bar');
    }

    function it_should_throw_InvalidArgumentException_when_the_converter_name_is_already_registered()
    {
        $this->shouldThrow('\InvalidArgumentException')->duringRegister('convert_bool', new ConvertBoolean());
    }

    function it_should_error_when_registering_a_converter_that_does_not_implement_AttributeConverterInterface()
    {
        $this->shouldThrow('\Exception')->duringRegister('foo_bar', new LdapObjectSchema('foo', 'bar'));
    }
}
