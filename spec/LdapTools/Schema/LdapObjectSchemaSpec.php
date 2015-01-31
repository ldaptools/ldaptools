<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Schema;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LdapObjectSchemaSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('ad', 'user');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Schema\LdapObjectSchema');
    }

    function it_should_implement_CacheableItemInterface()
    {
        $this->shouldImplement('\LdapTools\Cache\CacheableItemInterface');
    }

    function it_should_return_the_correct_schema_name_when_constructed_with_one()
    {
        $this->getSchemaName()->shouldBeEqualTo('ad');
    }

    function it_should_return_the_correct_object_type_when_constructed_with_one()
    {
        $this->getObjectType()->shouldBeEqualTo('user');
    }

    function it_should_return_a_string_when_calling_getSchemaName()
    {
        $this->getSchemaName()->shouldBeString();
    }

    function it_should_return_a_string_when_calling_getObjectType()
    {
        $this->getObjectType()->shouldBeString();
    }

    function it_should_set_the_schema_name_when_calling_setSchemaName()
    {
        $this->setSchemaName('foo');
        $this->getSchemaName()->shouldBeEqualTo('foo');
    }

    function it_should_set_the_object_type_when_calling_setObjectType()
    {
        $this->setObjectType('foo');
        $this->getObjectType()->shouldBeEqualTo('foo');
    }

    function it_should_return_an_array_when_calling_getAttributeMap()
    {
        $this->getAttributeMap()->shouldBeArray();
    }

    function it_should_set_the_attribute_map_when_calling_setAttributeMap()
    {
        $attributeMap = ['foo' => 'bar'];
        $this->setAttributeMap($attributeMap);
        $this->getAttributeMap()->shouldBeEqualTo($attributeMap);
    }

    function it_should_return_an_array_when_calling_getConverterMap()
{
    $this->getConverterMap()->shouldBeArray();
}

    function it_should_set_the_converter_map_when_calling_setConverterMap()
    {
        $this->setConverterMap(['foo' => 'bar']);
        $this->getConverterMap()->shouldBeEqualTo(['foo' => 'bar']);
    }

    function it_should_return_a_string_when_calling_getObjectClass()
    {
        $this->getObjectClass()->shouldBeString();
    }

    function it_should_set_the_objectclass_when_calling_setObjectClass()
    {
        $objectClass = 'foo';
        $this->setObjectClass($objectClass);
        $this->getObjectClass()->shouldBeEqualTo($objectClass);
    }

    function it_should_return_a_string_when_calling_getObjectCategory()
    {
        $this->getObjectCategory()->shouldBeString();
    }

    function it_should_set_the_objectcategory_when_calling_setObjectCategory()
    {
        $objectCategory = 'bar';
        $this->setObjectCategory($objectCategory);
        $this->getObjectCategory()->shouldBeEqualTo($objectCategory);
    }

    function it_should_return_the_mapped_attribute_when_calling_getAttributeToLdap()
    {
        $this->setAttributeMap(['foo' => 'bar']);
        $this->getAttributeToLdap('foo')->shouldBeEqualTo('bar');
    }

    function it_should_return_the_same_attribute_you_pass_it_when_calling_getAttributeToLdap_and_there_is_no_mapping()
    {
        $this->getAttributeToLdap('foo')->shouldBeEqualTo('foo');
    }

    function it_should_return_true_when_calling_hasAttribute_and_the_attribute_is_in_the_schema()
    {
        $this->setAttributeMap(['foo' => 'bar']);
        $this->hasAttribute('foo')->shouldBeEqualTo(true);
    }

    function it_should_return_false_when_calling_hasAttribute_and_the_attribute_is_not_in_the_schema()
    {
        $this->setAttributeMap(['foo' => 'bar']);
        $this->hasAttribute('bar')->shouldBeEqualTo(false);
    }

    function it_should_return_an_array_when_calling_getAttributesToSelect()
    {
        $this->getAttributesToSelect()->shouldBeArray();
    }

    function it_should_properly_set_the_default_attributes_to_get_when_calling_setAttributesToSelect()
    {
        $attributes = ['foo', 'bar'];
        $this->setAttributesToSelect($attributes);
        $this->getAttributesToSelect()->shouldBeEqualTo($attributes);
    }

    function it_should_return_all_names_mapped_to_one_attribute_when_calling_getNamesMappedToAttribute()
    {
        $this->setAttributeMap(['foo' => 'bar', 'panda' => 'bar']);
        $this->getNamesMappedToAttribute('bar')->shouldBeEqualTo(['foo', 'panda']);
    }

    function it_should_return_whether_an_ldap_attribute_has_a_name_mapped_to_it_when_calling_hasNameMappedToAttribute()
    {
        $this->setAttributeMap(['foo' => 'bar', 'panda' => 'bar']);
        $this->hasNamesMappedToAttribute('bar')->shouldBeEqualTo(true);
        $this->hasNamesMappedToAttribute('foo')->shouldBeEqualTo(false);
    }

    function it_should_return_the_default_repository_when_calling_getRepository()
    {
        $this->getRepository()->shouldBeEqualTo('\LdapTools\LdapObjectRepository');
    }

    function it_should_set_the_repository_when_calling_setRepository()
    {
        $this->setRepository('\Foo\Bar');
        $this->getRepository()->shouldBeEqualTo('\Foo\Bar');
    }
}
