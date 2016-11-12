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

    function it_should_return_a_searchable_hex_guid_when_calling_toLdap_on_a_search()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);
        $this->toLdap($this->guidString)->shouldBeEqualTo($this->guidHex);
    }

    function it_should_return_a_string_guid_from_binary_when_calling_fromLdap()
    {
        $this->fromLdap(pack('H*', str_replace('\\', '', $this->guidHex)))->shouldBeEqualTo($this->guidString);
    }

    function it_should_return_binary_data_when_calling_toLdap_on_create_or_modify()
    {
        $expected = hex2bin(str_replace('\\', '', $this->guidHex));

        $this->setOperationType(AttributeConverterInterface::TYPE_CREATE);
        $this->toLdap($this->guidString)->shouldBeEqualTo($expected);

        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->toLdap($this->guidString)->shouldBeEqualTo($expected);
    }

    function it_should_accept_the_term_auto_to_generate_a_guid_going_to_ldap()
    {
        $escapedHex = '/^(\\\[0-9a-fA-F]{2})+$/';

        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);
        $this->toLdap('auto')->shouldMatch($escapedHex);
        $this->toLdap('AUTO')->shouldMatch($escapedHex);

        $this->setOperationType(AttributeConverterInterface::TYPE_CREATE);
        $this->toLdap('auto')->shouldHaveBinaryGuid();

        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->toLdap('auto')->shouldHaveBinaryGuid();
    }

    function it_should_throw_an_exception_when_an_invalid_guid_is_being_sent_to_LDAP()
    {
        $this->shouldThrow('LdapTools\Exception\AttributeConverterException')->duringToLdap('foo');
    }

    public function getMatchers()
    {
        return [
            'haveBinaryGuid' => function ($subject) {
                return strlen(bin2hex($subject)) === 32;
            },
        ];
    }
}
