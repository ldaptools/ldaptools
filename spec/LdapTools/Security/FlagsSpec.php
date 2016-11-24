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

use LdapTools\Security\Ace\AceFlags;
use LdapTools\Security\Flags;
use PhpSpec\ObjectBehavior;

class FlagsSpec extends ObjectBehavior
{
    function let()
    {
        $flags = AceFlags::FLAG['CONTAINER_INHERIT'] + AceFlags::FLAG['SUCCESSFUL_ACCESS'];
        $this->beConstructedWith($flags);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(Flags::class);
    }

    function it_should_get_the_flags_value()
    {
        $this->getValue()->shouldBeEqualTo(66);
    }

    function it_should_check_if_a_flag_is_set_when_calling_has()
    {
        $this->has(AceFlags::FLAG['FAILED_ACCESS'])->shouldBeEqualTo(false);
        $this->has(AceFlags::FLAG['NO_PROPAGATE_INHERIT'])->shouldBeEqualTo(false);
        $this->has(AceFlags::FLAG['CONTAINER_INHERIT'])->shouldBeEqualTo(true);
        $this->has(AceFlags::FLAG['SUCCESSFUL_ACCESS'])->shouldBeEqualTo(true);
    }

    function it_should_add_a_flag()
    {
        $this->has(AceFlags::FLAG['INHERIT_ONLY'])->shouldBeEqualTo(false);
        $this->add(AceFlags::FLAG['INHERIT_ONLY'])->shouldReturnAnInstanceOf('LdapTools\Security\Flags');
        $this->has(AceFlags::FLAG['INHERIT_ONLY'])->shouldBeEqualTo(true);
        $this->has(AceFlags::FLAG['CONTAINER_INHERIT'])->shouldBeEqualTo(true);

        $this->add(AceFlags::FLAG['OBJECT_INHERIT'], AceFlags::FLAG['SUCCESSFUL_ACCESS']);
        $this->has(AceFlags::FLAG['SUCCESSFUL_ACCESS'])->shouldBeEqualTo(true);
        $this->has(AceFlags::FLAG['OBJECT_INHERIT'])->shouldBeEqualTo(true);
    }

    function it_should_remove_a_flag()
    {
        $this->add(AceFlags::FLAG['NO_PROPAGATE_INHERIT']);
        $this->has(AceFlags::FLAG['SUCCESSFUL_ACCESS'])->shouldBeEqualTo(true);
        $this->has(AceFlags::FLAG['CONTAINER_INHERIT'])->shouldBeEqualTo(true);

        $this->remove(AceFlags::FLAG['CONTAINER_INHERIT'])->shouldReturnAnInstanceOf('LdapTools\Security\Flags');
        $this->has(AceFlags::FLAG['CONTAINER_INHERIT'])->shouldBeEqualTo(false);

        $this->has(AceFlags::FLAG['SUCCESSFUL_ACCESS'])->shouldBeEqualTo(true);

        $this->remove(AceFlags::FLAG['SUCCESSFUL_ACCESS'], AceFlags::FLAG['NO_PROPAGATE_INHERIT']);
        $this->has(AceFlags::FLAG['NO_PROPAGATE_INHERIT'])->shouldBeEqualTo(false);
    }
}
