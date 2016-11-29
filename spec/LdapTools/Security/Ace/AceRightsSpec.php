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

use LdapTools\Security\Ace\AceRights;
use PhpSpec\ObjectBehavior;

class AceRightsSpec extends ObjectBehavior
{
    function let()
    {
        $flags = 0;
        foreach (AceRights::FLAG as $name => $value) {
            $flags += $value;
        }
        $this->beConstructedWith($flags);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(AceRights::class);
    }

    function it_should_extend_Flags()
    {
        $this->beAnInstanceOf('LdapTools\Security\Flags');
    }

    function it_should_get_the_short_names_of_the_flags()
    {
        $this->getShortNames()->shouldBeEqualTo(array_keys(AceRights::SHORT_NAME));
    }

    function it_should_have_a_string_representation_for_SDDL()
    {
        $this->__toString()->shouldBeEqualTo(implode('', array_keys(AceRights::SHORT_NAME)));
    }

    function it_should_check_or_set_the_ability_to_read_a_property()
    {
        $this->readProperty()->shouldBeEqualTo(true);
        $this->readProperty(false)->readProperty()->shouldBeEqualTo(false);
    }

    function it_should_check_or_set_the_ability_to_write_a_property()
    {
        $this->writeProperty()->shouldBeEqualTo(true);
        $this->writeProperty(false)->writeProperty()->shouldBeEqualTo(false);
    }

    function it_should_check_or_set_the_ability_to_create_child_objects()
    {
        $this->createChildObject()->shouldBeEqualTo(true);
        $this->createChildObject(false)->createChildObject()->shouldBeEqualTo(false);
    }

    function it_should_check_or_set_the_ability_to_delete_child_objects()
    {
        $this->deleteChildObject()->shouldBeEqualTo(true);
        $this->deleteChildObject(false)->deleteChildObject()->shouldBeEqualTo(false);
    }

    function it_should_check_or_set_the_ability_to_list_child_objects()
    {
        $this->listChildObject()->shouldBeEqualTo(true);
        $this->listChildObject(false)->listChildObject()->shouldBeEqualTo(false);
    }

    function it_should_check_or_set_the_ability_to_delete_objects()
    {
        $this->deleteObject()->shouldBeEqualTo(true);
        $this->deleteObject(false)->deleteObject()->shouldBeEqualTo(false);
    }

    function it_should_check_or_set_the_ability_to_list_objects()
    {
        $this->listObject()->shouldBeEqualTo(true);
        $this->listObject(false)->listObject()->shouldBeEqualTo(false);
    }

    function it_should_check_or_set_the_ability_to_perform_validated_writes()
    {
        $this->validatedWrite()->shouldBeEqualTo(true);
        $this->validatedWrite(false)->validatedWrite()->shouldBeEqualTo(false);
    }

    function it_should_check_or_set_the_ability_to_validate_writes()
    {
        $this->validatedWrite()->shouldBeEqualTo(true);
        $this->validatedWrite(false)->validatedWrite()->shouldBeEqualTo(false);
    }

    function it_should_check_or_set_control_access()
    {
        $this->controlAccess()->shouldBeEqualTo(true);
        $this->controlAccess(false)->controlAccess()->shouldBeEqualTo(false);
    }

    function it_should_check_or_set_read_security()
    {
        $this->readSecurity()->shouldBeEqualTo(true);
        $this->readSecurity(false)->readSecurity()->shouldBeEqualTo(false);
    }

    function it_should_check_or_set_sacl_access()
    {
        $this->accessSacl()->shouldBeEqualTo(true);
        $this->accessSacl(false)->accessSacl()->shouldBeEqualTo(false);
    }

    function it_should_check_or_set_the_ability_to_write_dacl()
    {
        $this->writeDacl()->shouldBeEqualTo(true);
        $this->writeDacl(false)->writeDacl()->shouldBeEqualTo(false);
    }

    function it_should_check_or_set_the_ability_to_write_the_owner()
    {
        $this->writeOwner()->shouldBeEqualTo(true);
        $this->writeOwner(false)->writeOwner()->shouldBeEqualTo(false);
    }

    function it_should_check_or_set_the_ability_to_read_all()
    {
        $this->readAll()->shouldBeEqualTo(true);
        $this->readAll(false)->readAll()->shouldBeEqualTo(false);
    }

    function it_should_check_or_set_the_ability_to_write_all()
    {
        $this->writeAll()->shouldBeEqualTo(true);
        $this->writeAll(false)->writeAll()->shouldBeEqualTo(false);
    }

    function it_should_check_or_set_full_control_access()
    {
        $this->fullControl()->shouldBeEqualTo(true);
        $this->fullControl(false)->fullControl()->shouldBeEqualTo(false);
    }

    function it_should_check_or_set_the_ability_to_execute()
    {
        $this->execute()->shouldBeEqualTo(true);
        $this->execute(false)->execute()->shouldBeEqualTo(false);
    }

    function it_should_check_or_set_the_ability_to_synchronize()
    {
        $this->synchronize()->shouldBeEqualTo(true);
        $this->synchronize(false)->synchronize()->shouldBeEqualTo(false);
    }

    function it_should_check_or_set_the_ability_to_perform_a_delete_tree_operation()
    {
        $this->deleteTree()->shouldBeEqualTo(true);
        $this->deleteTree(false)->deleteTree()->shouldBeEqualTo(false);
    }
}
