<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Connection;

use LdapTools\Enums\LdapControlOid;
use LdapTools\Exception\InvalidArgumentException;
use PhpSpec\ObjectBehavior;

class LdapControlSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith(LdapControlOid::SubTreeDelete);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Connection\LdapControl');
    }

    function it_should_set_the_oid_in_the_constructor()
    {
        $this->getOid()->shouldBeEqualTo(LdapControlOid::SubTreeDelete);
    }

    function it_should_be_able_to_set_the_criticality_and_value_in_the_constructor()
    {
        $this->beConstructedWith(LdapControlOid::SubTreeDelete, true, 'foo');
        $this->getOid()->shouldBeEqualTo(LdapControlOid::SubTreeDelete);
        $this->getCriticality()->shouldBeEqualTo(true);
        $this->getValue()->shouldBeEqualTo('foo');
    }

    function it_should_have_a_default_criticality_of_false()
    {
        $this->getCriticality()->shouldBeEqualTo(false);
    }

    function it_should_have_a_default_value_of_null()
    {
        $this->getValue()->shouldBeNull();
    }

    function it_should_set_the_oid()
    {
        $this->setOid(LdapControlOid::ShowDeleted)->getOid()->shouldBeEqualTo(LdapControlOid::ShowDeleted);
    }

    function it_should_set_the_criticality()
    {
        $this->setCriticality(true)->getCriticality()->shouldBeEqualTo(true);
    }

    function it_should_set_the_value()
    {
        $this->setValue(false)->getValue()->shouldBeEqualTo(false);
    }

    function it_should_allow_setting_a_reset_value()
    {
        $this->setResetValue(0)->getResetValue()->shouldBeEqualTo(0);
    }

    function it_should_have_a_default_reset_vaulue_of_bool_false()
    {
        $this->getResetValue()->shouldBeEqualTo(false);
    }

    function it_should_get_the_array_structure_of_the_control()
    {
        $this->toArray()->shouldBeEqualTo([
           'oid' => LdapControlOid::SubTreeDelete,
            'iscritical' => false
        ]);

        $this->setCriticality(true);
        $this->setValue(false);

        $this->toArray()->shouldBeEqualTo([
            'oid' => LdapControlOid::SubTreeDelete,
            'iscritical' => true,
            'value' => false
        ]);
    }

    function it_should_encode_a_simple_int_control_value_with_the_helper_berEncodeInt()
    {
        $this::berEncodeInt(7)->shouldBeEqualTo(hex2bin('3003020107'));
    }

    function it_should_accept_an_ldap_control_oid_enum_as_an_oid_value()
    {
        $oid = new LdapControlOid('ShowDeleted');
        $this->beConstructedWith($oid);

        $this->toArray()->shouldBeEqualTo([
            'oid' => "1.2.840.113556.1.4.417",
            'iscritical' => false,
        ]);
    }

    function it_should_accept_an_ldap_control_oid_enum_name_as_an_oid_value()
    {
        $this->beConstructedWith('ShowDeleted');

        $this->toArray()->shouldBeEqualTo([
            'oid' => "1.2.840.113556.1.4.417",
            'iscritical' => false,
        ]);
    }

    function it_should_throw_an_error_when_an_invalid_oid_or_enum_name_is_used_on_toArray()
    {
        $this->beConstructedWith('Foo');

        $this->shouldThrow(InvalidArgumentException::class)->during('toArray');
    }
}
