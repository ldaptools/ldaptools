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

use LdapTools\Security\Ace\AceType;
use PhpSpec\ObjectBehavior;

class AceTypeSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beConstructedWith(AceType::TYPE['ACCESS_ALLOWED']);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(AceType::class);
    }

    function it_should_get_the_type_value()
    {
        $this->getValue()->shouldBeEqualTo(0);
    }

    function it_should_set_the_type()
    {
        $this->setValue(AceType::TYPE['ACCESS_DENIED'])->getValue()->shouldBeEqualTo(AceType::TYPE['ACCESS_DENIED']);
    }

    function it_should_get_the_short_name_for_the_type()
    {
        $this->getShortName()->shouldBeEqualTo('A');
    }

    function it_should_have_a_string_representation_of_the_SDDL_short_name()
    {
        $this->__toString()->shouldBeEqualTo('A');
    }

    function it_should_allow_being_constructed_by_the_SDDL_short_name()
    {
        $this->beConstructedWith('D');

        $this->getValue()->shouldBeEqualTo(1);
    }

    function it_should_allow_being_constructed_by_the_constant_name()
    {
        $this->beConstructedWith('ACCESS_ALLOWED');

        $this->getValue()->shouldBeEqualTo(0);
    }

    function it_should_throw_an_InvalidArgumentExcecption_when_being_constructed_with_an_invalid_type()
    {
        $this->shouldThrow('LdapTools\Exception\InvalidArgumentException')->during('__construct', ['foo']);
    }

    function it_should_throw_an_InvalidArgumentExcecption_when_setting_an_invalid_type()
    {
        $this->shouldThrow('LdapTools\Exception\InvalidArgumentException')->duringSetValue('foo');
    }
}
