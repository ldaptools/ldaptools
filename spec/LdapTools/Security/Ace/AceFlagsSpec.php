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

use LdapTools\Security\Ace\AceFlags;
use PhpSpec\ObjectBehavior;

class AceFlagsSpec extends ObjectBehavior
{
    function let()
    {
        $flags = 0;
        foreach (AceFlags::FLAG as $name => $value) {
            $flags += $value;
        }
        $this->beConstructedWith($flags);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(AceFlags::class);
    }

    function it_should_extend_Flags()
    {
        $this->beAnInstanceOf('LdapTools\Security\Flags');
    }

    function it_should_get_the_short_names_of_the_flags()
    {
        $this->getShortNames()->shouldBeEqualTo(array_keys(AceFlags::SHORT_NAME));
    }

    function it_should_have_a_string_representation_for_SDDL()
    {
        $this->__toString()->shouldBeEqualTo(implode('', array_keys(AceFlags::SHORT_NAME)));
    }

    function it_should_check_whether_it_is_an_inherited_ACE()
    {
        $this->isInherited()->shouldBeEqualTo(true);
    }

    function it_should_check_or_set_whether_it_is_an_inherit_only_ace()
    {
        $this->inheritOnly()->shouldBeEqualTo(true);
        $this->inheritOnly(false)->inheritOnly()->shouldBeEqualTo(false);
    }

    function it_should_check_or_set_whether_it_is_a_container_inherit_ace()
    {
        $this->containerInherit()->shouldBeEqualTo(true);
        $this->containerInherit(false)->containerInherit()->shouldBeEqualTo(false);
    }

    function it_should_check_or_set_whether_it_is_an_object_inherit_ace()
    {
        $this->objectInherit()->shouldBeEqualTo(true);
        $this->objectInherit(false)->objectInherit()->shouldBeEqualTo(false);
    }

    function it_should_check_or_set_whether_the_ace_should_propagate_inheritance()
    {
        $this->propagateInheritance()->shouldBeEqualTo(false);
        $this->propagateInheritance(true)->propagateInheritance()->shouldBeEqualTo(true);
    }

    function it_should_check_or_set_whether_it_should_audit_successful_access()
    {
        $this->auditSuccessfulAccess()->shouldBeEqualTo(true);
        $this->auditSuccessfulAccess(false)->auditSuccessfulAccess()->shouldBeEqualTo(false);
    }

    function it_should_check_or_set_whether_it_should_audit_failed_access()
    {
        $this->auditFailedAccess()->shouldBeEqualTo(true);
        $this->auditFailedAccess(false)->auditFailedAccess()->shouldBeEqualTo(false);
    }
}
