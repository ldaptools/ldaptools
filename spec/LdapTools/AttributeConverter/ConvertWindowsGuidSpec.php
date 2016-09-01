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

class ConvertWindowsGuidSpec extends ObjectBehavior
{
    protected $guidString = "270db4d0-249d-46a7-9cc5-eb695d9af9ac";

    protected $guidHex = '\d0\b4\0d\27\9d\24\a7\46\9c\c5\eb\69\5d\9a\f9\ac';

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\AttributeConverter\ConvertWindowsGuid');
    }

    function it_should_implement_AttributeConverterInterface()
    {
        $this->shouldImplement('\LdapTools\AttributeConverter\AttributeConverterInterface');
    }

    function it_should_return_a_searchable_hex_guid_when_calling_toLdap()
    {
        $this->toLdap($this->guidString)->shouldBeEqualTo($this->guidHex);
    }

    function it_should_return_a_string_guid_from_binary_when_calling_fromLdap()
    {
        $this->fromLdap(pack('H*', str_replace('\\', '', $this->guidHex)))->shouldBeEqualTo($this->guidString);
    }
}
