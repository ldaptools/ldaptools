<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Object;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LdapObjectSpec extends ObjectBehavior
{
    protected $attributes = [
        'firstName' => 'Chad',
        'lastName' => 'Sikorra',
        'emailAddress' => 'chad.sikorra@example.com',
    ];

    function let()
    {
        $this->beConstructedWith($this->attributes, ['top', 'organizationalPerson', 'user'], 'user', 'user');
    }
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Object\LdapObject');
    }

    function it_should_return_an_array_with_the_exact_attributes_when_calling_to_array()
    {
        $this->toArray()->shouldBeEqualTo($this->attributes);
    }

    function it_should_allow_me_to_call_a_magical_getter_for_an_attribute()
    {
        $this->getFirstName()->shouldBeEqualTo('Chad');
    }

    function it_should_allow_me_to_call_a_magical_getter_case_insensitive()
    {
        $this->getfIrStnAmE()->shouldBeEqualTo('Chad');
    }

    function it_should_error_when_getting_an_attribute_that_doesnt_exist()
    {
        $this->shouldThrow('\InvalidArgumentException')->duringGetFoo();
    }

    function it_should_return_self_from_a_magic_setter()
    {
        $this->setFirstName('Foo')->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObject');
    }

    function it_should_set_the_attribute_when_calling_the_magical_setter()
    {
        $this->setFirstName('Foo');
        $this->getFirstName()->shouldBeEqualTo('Foo');
    }

    function it_should_properly_check_whether_an_attribute_exists()
    {
        $this->hasAttribute('foo')->shouldBeEqualTo(false);
        $this->hasAttribute('lastName')->shouldBeEqualTo(true);
    }

    function it_should_be_case_insensitive_when_checking_whether_an_attribute_exists()
    {
        $this->hasAttribute('LaStNaMe')->shouldBeEqualTo(true);
    }

    function it_should_get_an_attribute_using_the_magic_property_getter()
    {
        $this->__get('firstName')->shouldBeEqualTo('Chad');
    }

    function it_should_set_an_attribute_using_the_magic_property_setter()
    {
        $this->__set('firstName', 'foo');
        $this->__get('firstName')->shouldBeEqualTo('foo');
    }

    function it_should_add_an_additional_attribute_when_calling_the_magical_add()
    {
        $this->addEmailAddress('foo@bar.com');
        $this->getEmailAddress()->shouldBeArray();
        $this->getEmailAddress()->shouldContain('foo@bar.com');
        $this->getEmailAddress()->shouldContain('chad.sikorra@example.com');
    }

    function it_should_return_self_when_calling_the_magical_add()
    {
        $this->addEmailAddress('Foo.Bar@yay.com')->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObject');
    }

    function it_should_remove_a_value_when_calling_the_magical_remove()
    {
        $this->removeLastName('Sikorra');
        $this->getLastName()->shouldBeEqualTo('');

        $this->addEmailAddress('foo@bar.com');
        $this->getEmailAddress()->shouldBeArray();
        $this->getEmailAddress()->shouldContain('foo@bar.com');
        $this->removeEmailAddress('chad.sikorra@example.com');
        $this->getEmailAddress()->shouldNotContain('chad.sikorra@example.com');
    }

    function it_should_return_self_when_calling_the_magical_remove()
    {
        $this->removeFirstName('Chad')->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObject');
    }

    function it_should_remove_an_attribute_when_calling_the_magical_reset()
    {
        $this->resetFirstName();
        $this->hasAttribute('firstName')->shouldBeEqualTo(false);
    }

    function it_should_return_self_when_calling_the_magical_reset()
    {
        $this->resetFirstName()->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObject');
    }

    function it_should_implement_a_magic_isset_to_check_for_an_attribute()
    {
        $this->__isset('firstName')->shouldBeEqualTo(true);
        $this->__isset('foo')->shouldBeEqualTo(false);
    }

    function it_should_allow_getting_an_attriute_with_get()
    {
        $this->get('lastName')->shouldBeEqualTo('Sikorra');
    }

    function it_should_be_case_insensitive_when_getting_an_attribute()
    {
        $this->get('FirstNamE')->shouldBeEqualTo('Chad');
    }

    function it_should_allow_setting_an_attribute_using_set()
    {
        $this->set('firstName', 'Foo');
        $this->get('firstName')->shouldBeEqualTo('Foo');
    }

    function it_should_return_self_when_calling_set()
    {
        $this->set('firstName', 'Foo')->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObject');
    }

    function it_should_be_case_insensitive_when_setting_an_attribute()
    {
        $this->set('FirstNamE', 'Foo');
        $this->get('firstName')->shouldBeEqualTo('Foo');
    }

    function it_should_remove_an_attribute_completely_when_calling_reset()
    {
        $this->reset('firstName');
        $this->hasAttribute('firstName')->shouldBeEqualTo(false);
    }

    function it_should_be_case_insensitive_when_calling_reset()
    {
        $this->reset('FirsTName');
        $this->hasAttribute('firstName')->shouldBeEqualTo(false);
    }

    function it_should_return_self_when_calling_reset()
    {
        $this->reset('firstName')->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObject');
    }

    function it_should_add_an_attribute_value_when_calling_add()
    {
        $this->add('lastName', 'Bar');
        $this->get('lastName')->shouldContain('Bar');
    }

    function it_should_be_case_insensitive_when_calling_add()
    {
        $this->add('LaStNaMe', 'Bar');
        $this->get('lastName')->shouldContain('Bar');
    }

    function it_should_return_self_when_calling_add()
    {
        $this->add('firstName', 'Foo')->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObject');
    }

    function it_should_remove_an_attribute_value_when_calling_remove()
    {
        $this->remove('lastName', 'Sikorra');
        $this->get('lastName')->shouldBeEqualTo('');
    }

    function it_should_be_case_insensitive_when_calling_remove()
    {
        $this->remove('LaStNaMe', 'Sikorra');
        $this->get('lastName')->shouldBeEqualTo('');
    }

    function it_should_return_self_when_calling_remove()
    {
        $this->remove('firstName', 'Chad')->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObject');
    }

    function it_should_check_if_the_object_contains_an_objectClass()
    {
        $this->isClass('organizationalPerson')->shouldBeEqualTo(true);
        $this->isClass('group')->shouldBeEqualTo(false);
    }

    function it_should_check_if_the_object_is_a_specific_category()
    {
        $this->isCategory('user')->shouldBeEqualTo(true);
        $this->isCategory('contact')->shouldBeEqualTo(false);
    }

    function it_should_check_if_the_object_is_a_specific_type()
    {
        $this->isType('user')->shouldBeEqualTo(true);
        $this->isType('computer')->shouldBeEqualTo(false);
    }

    function it_should_add_batch_modifications_for_each_action()
    {
        $this->addFirstName('Foo');
        $this->getBatchModifications()->shouldHaveCount(1);
        $this->removeLastName('Sikorra');
        $this->getBatchModifications()->shouldHaveCount(2);
        $this->reset('emailAddress');
        $this->getBatchModifications()->shouldHaveCount(3);
        $this->set('phoneNumber', '555-5555');
        $this->getBatchModifications()->shouldHaveCount(4);
    }

    function it_should_return_the_ldap_type_when_calling_get_type()
    {
        $this->getType()->shouldBeEqualTo('user');
    }
}
