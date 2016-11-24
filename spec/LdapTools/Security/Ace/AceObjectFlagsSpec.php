<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Security\Ace;

use LdapTools\Security\Ace\AceObjectFlags;
use PhpSpec\ObjectBehavior;

class AceObjectFlagsSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith(3);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(AceObjectFlags::class);
    }

    function it_should_extend_Flags()
    {
        $this->beAnInstanceOf('LdapTools\Security\Flags');
    }

    function it_should_check_the_flags()
    {
        $this->has(AceObjectFlags::FLAG['OBJECT_TYPES_INVALID'])->shouldBeEqualTo(false);
        $this->has(AceObjectFlags::FLAG['OBJECT_TYPE_PRESENT'])->shouldBeEqualTo(true);
        $this->has(AceObjectFlags::FLAG['INHERITED_OBJECT_TYPE_PRESENT'])->shouldBeEqualTo(true);
    }

    function it_should_check_or_set_whether_the_object_type_is_present()
    {
        $this->objectTypePresent()->shouldBeEqualTo(true);
        $this->objectTypePresent(false)->objectTypePresent()->shouldBeEqualTo(false);
    }

    function it_should_check_or_set_whether_the_inherited_object_type_is_present()
    {
        $this->inheritedObjectTypePresent()->shouldBeEqualTo(true);
        $this->inheritedObjectTypePresent(false)->inheritedObjectTypePresent()->shouldBeEqualTo(false);
    }

    function it_should_check_whether_both_object_type_are_invalid()
    {
        $this->objectTypesInvalid()->shouldBeEqualTo(false);
        $this->inheritedObjectTypePresent(false)->objectTypePresent(false)->objectTypesInvalid()->shouldBeEqualTo(true);
    }
}
