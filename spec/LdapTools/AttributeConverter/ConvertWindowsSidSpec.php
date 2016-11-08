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

class ConvertWindowsSidSpec extends ObjectBehavior
{
    protected $sidHex = '\01\05\00\00\00\00\00\05\15\00\00\00\dc\f4\dc\3b\83\3d\2b\46\82\8b\a6\28\00\02\00\00';
    protected $sidString = 'S-1-5-21-1004336348-1177238915-682003330-512';

    /**
     * @var string Builtin Administrators group
     */
    protected $sidBuiltinString = 'S-1-5-32-544';
    protected $sidBuiltinHex = '\01\02\00\00\00\00\00\05\20\00\00\00\20\02\00\00';

    /**
     * @var string Nobody
     */
    protected $sidNobodyString = 'S-1-0-0';
    protected $sidNobodyHex = '\01\01\00\00\00\00\00\00\00\00\00\00';

    /**
     * @var string Null
     */
    protected $sidNullString = 'S-1-0';
    protected $sidNullHex = '\01\00\00\00\00\00\00\00';

    /**
     * @var string Self
     */
    protected $sidSelfString = 'S-1-5-10';
    protected $sidSelfHex = '\01\01\00\00\00\00\00\05\0a\00\00\00';

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\AttributeConverter\ConvertWindowsSid');
    }

    function it_should_implement_AttributeConverterInterface()
    {
        $this->shouldImplement('\LdapTools\AttributeConverter\AttributeConverterInterface');
    }

    function it_should_return_a_searchable_hex_sid_when_calling_toLdap()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);

        $this->toLdap($this->sidString)->shouldBeEqualTo($this->sidHex);
    }

    function it_should_return_a_string_sid_when_calling_fromLdap()
    {
        $this->fromLdap(pack('H*', str_replace('\\', '', $this->sidHex)))->shouldBeEqualTo($this->sidString);
    }

    function it_should_return_a_searchable_hex_sid_for_well_known_sids_when_calling_toLdap()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);

        $this->toLdap($this->sidBuiltinString)->shouldBeEqualTo($this->sidBuiltinHex);
        $this->toLdap($this->sidNobodyString)->shouldBeEqualTo($this->sidNobodyHex);
        $this->toLdap($this->sidSelfString)->shouldBeEqualTo($this->sidSelfHex);
        $this->toLdap($this->sidNullString)->shouldBeEqualTo($this->sidNullHex);
    }

    function it_should_return_a_string_sid_for_well_known_sids_when_calling_fromLdap()
    {
        $this->fromLdap(pack('H*', str_replace('\\', '', $this->sidBuiltinHex)))->shouldBeEqualTo($this->sidBuiltinString);
        $this->fromLdap(pack('H*', str_replace('\\', '', $this->sidNobodyHex)))->shouldBeEqualTo($this->sidNobodyString);
        $this->fromLdap(pack('H*', str_replace('\\', '', $this->sidSelfHex)))->shouldBeEqualTo($this->sidSelfString);
        $this->fromLdap(pack('H*', str_replace('\\', '', $this->sidNullHex)))->shouldBeEqualTo($this->sidNullString);
    }

    function it_should_return_binary_data_when_going_to_ldap_for_creation_or_modification()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);

        $this->toLdap($this->sidString)->shouldBeEqualTo(hex2bin(str_replace('\\', '', $this->sidHex)));
        $this->toLdap($this->sidBuiltinString)->shouldBeEqualTo(hex2bin(str_replace('\\', '', $this->sidBuiltinHex)));
        $this->toLdap($this->sidNobodyString)->shouldBeEqualTo(hex2bin(str_replace('\\', '', $this->sidNobodyHex)));

        $this->setOperationType(AttributeConverterInterface::TYPE_CREATE);

        $this->toLdap($this->sidString)->shouldBeEqualTo(hex2bin(str_replace('\\', '', $this->sidHex)));
        $this->toLdap($this->sidBuiltinString)->shouldBeEqualTo(hex2bin(str_replace('\\', '', $this->sidBuiltinHex)));
        $this->toLdap($this->sidNobodyString)->shouldBeEqualTo(hex2bin(str_replace('\\', '', $this->sidNobodyHex)));
    }

    function it_should_convert_a_self_sid()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);

        $this->toLdap('S-1-5-10')->shouldBeEqualTo('\01\01\00\00\00\00\00\05\0a\00\00\00');
    }

    function it_should_throw_an_exception_if_an_invalid_SID_is_passed_to_LDAP()
    {
        $this->shouldThrow('LdapTools\Exception\AttributeConverterException')->duringToLdap('foo');
    }
}
