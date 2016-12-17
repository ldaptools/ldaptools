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

use LdapTools\BatchModify\BatchCollection;
use PhpSpec\ObjectBehavior;

class LdapObjectSpec extends ObjectBehavior
{
    protected $attributes = [
        'firstName' => 'Chad',
        'lastName' => 'Sikorra',
        'emailAddress' => 'chad.sikorra@example.com',
        'dn' => 'CN=chad,DC=example,DC=com'
    ];

    function let()
    {
        $this->beConstructedWith($this->attributes, 'user');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Object\LdapObject');
    }

    function it_should_be_constructable_with_no_attributes()
    {
        $this->beConstructedWith();

        $this->toArray()->shouldBeEqualTo([]);
    }

    function it_should_set_the_dn_for_the_batch_collection_on_construction()
    {
        $this->getBatchCollection()->getDn()->shouldBeEqualTo($this->attributes['dn']);
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
        $this->shouldThrow('\LdapTools\Exception\InvalidArgumentException')->duringGetFoo();
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
        $this->has('foo')->shouldBeEqualTo(false);
        $this->has('lastName')->shouldBeEqualTo(true);
    }

    function it_should_properly_check_whether_an_attribute_with_a_specific_value_exists()
    {
        $this->has('firstName', 'Foo')->shouldBeEqualTo(false);
        $this->has('lastName', 'Sikorra')->shouldBeEqualTo(true);
    }

    function it_should_be_case_insensitive_when_checking_whether_an_attribute_exists()
    {
        $this->has('LaStNaMe')->shouldBeEqualTo(true);
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
        $this->has('firstName')->shouldBeEqualTo(false);
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
        $this->has('firstName')->shouldBeEqualTo(false);

        $this->reset('lastName', 'emailAddress');
        $this->has('lastName')->shouldBeEqualTo(false);
        $this->has('emailAddress')->shouldBeEqualTo(false);
    }

    function it_should_be_case_insensitive_when_calling_reset()
    {
        $this->reset('FirsTName');
        $this->has('firstName')->shouldBeEqualTo(false);
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

    function it_should_check_if_the_object_is_a_specific_type()
    {
        $this->isType('user')->shouldBeEqualTo(true);
        $this->isType('computer')->shouldBeEqualTo(false);
    }

    function it_should_add_to_the_batch_collection_for_each_action()
    {
        $this->addFirstName('Foo');
        $this->getBatchCollection()->getBatchArray()->shouldHaveCount(1);
        $this->removeLastName('Sikorra');
        $this->getBatchCollection()->getBatchArray()->shouldHaveCount(2);
        $this->reset('emailAddress');
        $this->getBatchCollection()->getBatchArray()->shouldHaveCount(3);
        $this->set('phoneNumber', '555-5555');
        $this->getBatchCollection()->getBatchArray()->shouldHaveCount(4);
    }

    function it_should_return_the_ldap_type_when_calling_get_type()
    {
        $this->getType()->shouldBeEqualTo('user');
    }

    function it_should_be_able_to_clear_the_batch_modifcations_array()
    {
        $this->addFirstName('Foo');
        $this->removeLastName('Sikorra');
        $this->reset('emailAddress');
        $this->set('phoneNumber', '555-5555');
        $this->getBatchCollection()->getBatchArray()->shouldHaveCount(4);
        $this->setBatchCollection(new BatchCollection())->getBatchCollection()->getBatchArray()->shouldHaveCount(0);
    }

    function it_should_check_for_an_attribute_when_calling_the_magical_has()
    {
        $this->hasFirstName()->shouldBeEqualTo(true);
    }

    function it_should_check_for_an_attribute_with_a_value_if_specified_in_the_magical_has()
    {
        $this->hasFirstName('Chad')->shouldBeEqualTo(true);
        $this->hasFirstName('foo')->shouldBeEqualTo(false);
    }

    function it_should_properly_check_for_an_attribute_with_a_value_even_when_the_attribute_doesnt_exist()
    {
        $this->hasFoo()->shouldBeEqualTo(false);
        $this->hasFoo('bar')->shouldBeEqualTo(false);
    }

    function it_should_refresh_attributes_without_triggering_a_batch_modification()
    {
        $this->refresh(['firstName' => 'Foo'])->getBatchCollection()->toArray()->shouldHaveCount(0);
        $this->getFirstName()->shouldBeEqualTo('Foo');
    }

    function it_should_be_case_insensitive_when_refreshing()
    {
        $this->refresh(['FirstName' => 'foo', 'LastName' => 'bar']);
        $this->getFirstName()->shouldBeEqualTo('foo');
        $this->getLastName()->shouldBeEqualTo('bar');
    }

    function it_should_add_attributes_that_dont_exist_when_refreshing()
    {
        $this->refresh(['foo' => 'bar']);
        $this->getFoo()->shouldBeEqualTo('bar');
    }

    function it_should_be_able_to_add_multiple_values_at_once()
    {
        $addresses = ['foo@bar.com', 'bar@foo.com'];
        $this->addEmailAddress(...$addresses);
        $this->add('emailAddress', 'foobar@foo.com', 'foobar@bar.com', 'foobar@foobar.com');

        $this->getEmailAddress()->shouldBeArray();
        $this->getEmailAddress()->shouldContain('foo@bar.com');
        $this->getEmailAddress()->shouldContain('bar@foo.com');
        $this->getEmailAddress()->shouldContain('foobar@bar.com');
        $this->getEmailAddress()->shouldContain('foobar@foo.com');
        $this->getEmailAddress()->shouldContain('foobar@foobar.com');
        $this->getEmailAddress()->shouldContain('chad.sikorra@example.com');
    }

    function it_should_be_able_to_remove_multiple_values_at_once()
    {
        $attributes = $this->attributes;
        $attributes['emailAddress'] = [
            'chad.sikorra@example.com',
            'foo@bar.com',
            'bar@foo.com',
            'foobar@foo.com'
        ];
        $this->refresh($attributes);
        $this->getEmailAddress()->shouldBeEqualTo($attributes['emailAddress']);

        $this->removeEmailAddress(...['foo@bar.com', 'bar@foo.com']);
        $this->getEmailAddress()->shouldNotContain('foo@bar.com');
        $this->getEmailAddress()->shouldNotContain('bar@foo.com');
        $this->remove('emailAddress', 'chad.sikorra@example.com', 'foobar@foo.com');
        $this->getEmailAddress()->shouldBeEqualTo([]);

    }

    function it_should_remove_an_attribute_completely_when_calling_set_with_an_empty_string_empty_array_or_null()
    {
        $attributes = $this->attributes;
        $attributes['foo1'] = 'bar';
        $attributes['foo2'] = 'bar';
        $attributes['foo3'] = 'bar';
        $this->refresh($attributes);

        $this->set('foo1', '');
        $this->has('foo1')->shouldBeEqualTo(false);

        $this->set('foo2', []);
        $this->has('foo2')->shouldBeEqualTo(false);

        $this->set('foo3', null);
        $this->has('foo3')->shouldBeEqualTo(false);
    }

    function it_should_have_a_string_representation()
    {
        $this->__toString()->shouldBeEqualTo('CN=chad,DC=example,DC=com');
        $this->reset('dn')->__toString()->shouldBeEqualTo('');
    }
}
