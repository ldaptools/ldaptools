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

use LdapTools\AttributeConverter\ConvertWindowsSecurity;
use LdapTools\Security\SddlParser;
use PhpSpec\ObjectBehavior;

class ConvertWindowsSecuritySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(ConvertWindowsSecurity::class);
    }

    function it_should_accept_a_SDDL_string_or_SecurityDescriptor_going_to_ldap()
    {
        $sddl = 'O:PSG:PSD:(A;CI;RCCC;;;PS)';
        $sd = (new SddlParser())->parse($sddl);

        $this->toLdap($sddl)->shouldBeEqualTo($sd->toBinary());
        $this->toLdap($sd)->shouldBeEqualTo($sd->toBinary());
    }

    function it_should_return_a_security_descriptor_from_ldap()
    {
        $sddl = 'O:PSG:PSD:(A;CI;RCCC;;;PS)';
        $sd = (new SddlParser())->parse($sddl);

        $this->fromLdap($sd->toBinary())->shouldBeLike($sd);
    }

    function it_should_throw_an_exception_if_a_invalid_value_is_passed_going_to_ldap()
    {
        $this->shouldThrow('LdapTools\Exception\AttributeConverterException')->duringToLdap('foo');
        $this->shouldThrow('LdapTools\Exception\AttributeConverterException')->duringToLdap(new \DateTime());
    }
}
