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

use LdapTools\Security\Ace\Ace;
use LdapTools\Security\Ace\AceFlags;
use LdapTools\Security\Ace\AceObjectFlags;
use LdapTools\Security\Ace\AceRights;
use LdapTools\Security\Ace\AceType;
use LdapTools\Security\GUID;
use LdapTools\Security\SID;
use PhpSpec\ObjectBehavior;

class AceSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith(hex2bin('050a4800070000000300000001c975c9ea6c6f4b8319d67f4544950614cc28483714bc459b07ad6f015e5f2801050000000000051500000015b34c4bd2fb9073c2df39b95c040000'));
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(Ace::class);
    }

    function it_should_allow_being_constructed_with_a_ACE_type_short_name()
    {
        $this->beConstructedWith('D');

        $this->getType()->getShortName()->shouldBeEqualTo('D');
    }

    function it_should_parse_the_flags_and_contain_an_AceFlags_object()
    {
        $this->getFlags()->shouldImplement('LdapTools\Security\Flags');
        $this->getFlags()->shouldReturnAnInstanceOf('LdapTools\Security\Ace\AceFlags');
        $this->getFlags()->getValue()->shouldBeEqualTo(10);
    }

    function it_should_be_able_to_set_the_AceFlags(AceFlags $flags)
    {
        $this->setFlags($flags)->shouldReturnAnInstanceOf('LdapTools\Security\Ace\Ace');
        $this->getFlags()->shouldBeEqualTo($flags);
    }

    function it_should_parse_the_rights_and_contain_an_AceRights_object()
    {
        $this->getRights()->shouldImplement('LdapTools\Security\Flags');
        $this->getRights()->shouldReturnAnInstanceOf('LdapTools\Security\Ace\AceRights');
        $this->getRights()->getValue()->shouldBeEqualTo(7);
    }

    function it_should_be_able_to_set_the_AceRights(AceRights $aceRights)
    {
        $this->setRights($aceRights)->shouldReturnAnInstanceOf('LdapTools\Security\Ace\Ace');
        $this->getRights()->shouldBeEqualTo($aceRights);
    }

    function it_should_parse_the_trustee_and_contain_a_sid_object()
    {
        $this->getTrustee()->shouldReturnAnInstanceOf('LdapTools\Security\SID');
        $this->getTrustee()->toString()->shouldBeEqualTo('S-1-5-21-1263317781-1938881490-3107577794-1116');
    }

    function it_should_be_able_to_set_the_trustee(SID $sid)
    {
        $this->setTrustee($sid)->shouldReturnAnInstanceOf('LdapTools\Security\Ace\Ace');
        $this->getTrustee()->shouldBeEqualTo($sid);
    }

    function it_should_parse_the_type_and_contain_an_AceType_object()
    {
        $this->getType()->shouldReturnAnInstanceOf('LdapTools\Security\Ace\AceType');
        $this->getType()->getValue()->shouldBeEqualTo(5);
    }

    function it_should_set_the_AceType(AceType $aceType)
    {
        $this->setType($aceType)->getType()->shouldBeEqualTo($aceType);
        $this->setType('D')->getType()->getShortName()->shouldBeEqualTo('D');

    }

    function it_should_parse_and_contain_the_object_type_GUID()
    {
        $this->getObjectType()->shouldReturnAnInstanceOf('LdapTools\Security\GUID');
        $this->getObjectType()->toString()->shouldBeEqualTo('c975c901-6cea-4b6f-8319-d67f45449506');
    }

    function it_should_set_the_object_type_GUID(GUID $guid)
    {
        $this->setObjectType($guid)->shouldReturnAnInstanceOf('LdapTools\Security\Ace\Ace');
        $this->getObjectType()->shouldBeEqualTo($guid);
        $this->setObjectType(null)->getObjectType()->shouldBeNull();
    }

    function it_should_parse_and_contain_the_inherited_object_type_GUID()
    {
        $this->getInheritedObjectType()->shouldReturnAnInstanceOf('LdapTools\Security\GUID');
        $this->getInheritedObjectType()->toString()->shouldBeEqualTo('4828cc14-1437-45bc-9b07-ad6f015e5f28');
    }

    function it_should_set_the_inherited_object_type_GUID(GUID $guid)
    {
        $this->setInheritedObjectType($guid)->shouldReturnAnInstanceOf('LdapTools\Security\Ace\Ace');
        $this->getInheritedObjectType()->shouldBeEqualTo($guid);
        $this->setInheritedObjectType(null)->getInheritedObjectType()->shouldBeNull();
    }

    function it_should_parse_the_object_type_flags_and_contain_an_object_flags_object()
    {
        $this->getObjectFlags()->shouldImplement('LdapTools\Security\Flags');
        $this->getObjectFlags()->shouldReturnAnInstanceOf('LdapTools\Security\Ace\AceObjectFlags');
        $this->getObjectFlags()->getValue()->shouldBeEqualTo(3);
    }

    function it_should_set_the_object_flags(AceObjectFlags $objectFlags)
    {
        $this->setObjectFlags($objectFlags)->shouldReturnAnInstanceOf('LdapTools\Security\Ace\Ace');
        $this->getObjectFlags()->shouldBeEqualTo($objectFlags);
        $this->setObjectFlags(null)->getObjectFlags()->shouldBeNull();
    }

    function it_should_get_the_binary_representation_of_the_ACE()
    {
        $this->toBinary()->shouldBeEqualTo(hex2bin('050a4800070000000300000001c975c9ea6c6f4b8319d67f4544950614cc28483714bc459b07ad6f015e5f2801050000000000051500000015b34c4bd2fb9073c2df39b95c040000'));
    }

    function it_should_get_the_SDDL_string_format_of_the_ACE_when_caling_toSddl()
    {
        $this->toSddl()->shouldBeEqualTo('(OA;CIIO;CCDCLC;c975c901-6cea-4b6f-8319-d67f45449506;4828cc14-1437-45bc-9b07-ad6f015e5f28;S-1-5-21-1263317781-1938881490-3107577794-1116)');
    }

    function it_should_have_a_string_representation_of_the_SDDL()
    {
        $this->__toString()->shouldBeEqualTo('(OA;CIIO;CCDCLC;c975c901-6cea-4b6f-8319-d67f45449506;4828cc14-1437-45bc-9b07-ad6f015e5f28;S-1-5-21-1263317781-1938881490-3107577794-1116)');
    }

    function it_should_throw_an_exception_if_converting_to_SDDL_and_the_SID_or_type_is_not_set()
    {
        $this->beConstructedWith(null);

        $this->shouldThrow('LdapTools\Exception\LogicException')->duringToSddl();
        $this->setTrustee(new SID('PS'));
        $this->shouldThrow('LdapTools\Exception\LogicException')->duringToSddl();
        $this->setType(new AceType('D'));
        $this->shouldNotThrow('LdapTools\Exception\LogicException')->duringToSddl();
    }

    function it_should_check_whether_the_ace_allows_access()
    {
        $this->beConstructedWith('A');

        $this->isAllowAce()->shouldBeEqualTo(true);
        $this->setType(new AceType('D'));
        $this->isAllowAce()->shouldBeEqualTo(false);
        $this->setType(new AceType('OA'));
        $this->isAllowAce()->shouldBeEqualTo(true);
        $this->setType(new AceType('OD'));
        $this->isAllowAce()->shouldBeEqualTo(false);
    }

    function it_should_check_whether_the_ace_denies_access()
    {
        $this->beConstructedWith('D');

        $this->isDenyAce()->shouldBeEqualTo(true);
        $this->setType(new AceType('A'));
        $this->isDenyAce()->shouldBeEqualTo(false);
        $this->setType(new AceType('OD'));
        $this->isDenyAce()->shouldBeEqualTo(true);
        $this->setType(new AceType('OA'));
        $this->isDenyAce()->shouldBeEqualTo(false);
    }

    function it_should_check_if_this_is_an_object_based_ace()
    {
        $this->beConstructedWith('OA');

        $this->isObjectAce()->shouldBeEqualTo(true);
        $this->setType(new AceType('A'));
        $this->isObjectAce()->shouldBeEqualTo(false);
    }

    function it_should_toggle_the_object_type_flags_automatically()
    {
        $this->beConstructedWith('OA');
        $this->getObjectFlags()->shouldBeNull();

        $this->setObjectType(new GUID(AceRights::EXTENDED['CHANGE_PASSWORD']));
        $this->getObjectFlags()->has(AceObjectFlags::FLAG['OBJECT_TYPE_PRESENT'])->shouldBeEqualTo(true);
        $this->getObjectFlags()->has(AceObjectFlags::FLAG['INHERITED_OBJECT_TYPE_PRESENT'])->shouldBeEqualTo(false);

        $this->setInheritedObjectType(new GUID(AceRights::EXTENDED['CHANGE_PASSWORD']));
        $this->getObjectFlags()->has(AceObjectFlags::FLAG['OBJECT_TYPE_PRESENT'])->shouldBeEqualTo(true);
        $this->getObjectFlags()->has(AceObjectFlags::FLAG['INHERITED_OBJECT_TYPE_PRESENT'])->shouldBeEqualTo(true);

        $this->setObjectType(null);
        $this->getObjectFlags()->has(AceObjectFlags::FLAG['OBJECT_TYPE_PRESENT'])->shouldBeEqualTo(false);
        $this->getObjectFlags()->has(AceObjectFlags::FLAG['INHERITED_OBJECT_TYPE_PRESENT'])->shouldBeEqualTo(true);

        $this->setInheritedObjectType(null);
        $this->getObjectFlags()->has(AceObjectFlags::FLAG['OBJECT_TYPE_PRESENT'])->shouldBeEqualTo(false);
        $this->getObjectFlags()->has(AceObjectFlags::FLAG['INHERITED_OBJECT_TYPE_PRESENT'])->shouldBeEqualTo(false);
    }

    function it_should_allow_setting_the_trustee_by_a_string_sid()
    {
        $this->setTrustee('PS')->getTrustee()->toString()->shouldBeEqualTo(SID::SHORT_NAME['PS']);
    }

    function it_should_allow_setting_object_types_by_a_string_guid()
    {
        $this->setObjectType(null)->getObjectType()->shouldBeEqualTo(null);
        $this->setObjectType(AceRights::EXTENDED['CHANGE_PASSWORD'])->getObjectType()->toString()->shouldBeEqualTo(AceRights::EXTENDED['CHANGE_PASSWORD']);

        $this->setInheritedObjectType(null)->getInheritedObjectType()->shouldBeEqualTo(null);
        $this->setInheritedObjectType(AceRights::EXTENDED['CHANGE_PASSWORD'])->getInheritedObjectType()->toString()->shouldBeEqualTo(AceRights::EXTENDED['CHANGE_PASSWORD']);
    }
}
