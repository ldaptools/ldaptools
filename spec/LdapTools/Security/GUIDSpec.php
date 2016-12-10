<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Security;

use LdapTools\Security\GUID;
use PhpSpec\ObjectBehavior;

class GUIDSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beConstructedWith(hex2bin('d0b40d279d24a7469cc5eb695d9af9ac'));
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(GUID::class);
    }

    function it_should_get_the_string_representation_of_the_GUID()
    {
        $this->toString()->shouldBeEqualTo('270db4d0-249d-46a7-9cc5-eb695d9af9ac');
    }

    function it_should_allow_being_constructed_with_a_string_GUID()
    {
        $guid = '270db4d0-249d-46a7-9cc5-eb695d9af9ac';
        $this->beConstructedWith($guid);

        $this->toString()->shouldBeEqualTo($guid);
        $this->toBinary()->shouldBeEqualTo(hex2bin('d0b40d279d24a7469cc5eb695d9af9ac'));
    }

    function it_should_have_a_magic_to_string_function_that_outputs_the_friendly_string_name()
    {
        $this->__toString()->shouldBeEqualTo('270db4d0-249d-46a7-9cc5-eb695d9af9ac');
    }

    function it_should_get_the_binary_representation_of_the_GUID()
    {
        $this->toBinary()->shouldBeEqualTo(hex2bin('d0b40d279d24a7469cc5eb695d9af9ac'));
    }

    function it_should_throw_an_exception_if_the_binary_guid_was_not_valid()
    {
        $this->shouldThrow('\UnexpectedValueException')->during('__construct', [hex2bin('0101010101')]);
    }
}
