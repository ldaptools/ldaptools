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

use LdapTools\Connection\LdapControl;
use LdapTools\Operation\QueryOperation;
use LdapTools\Query\Operator\bAnd;
use LdapTools\Query\Operator\Comparison;
use PhpSpec\ObjectBehavior;

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

    function it_should_return_an_array_when_calling_getObjectClass()
    {
        $this->getObjectClass()->shouldBeArray();
    }

    function it_should_set_the_objectclass_when_calling_setObjectClass()
    {
        $objectClass = 'foo';
        $this->setObjectClass($objectClass);
        $this->getObjectClass()->shouldBeEqualTo([$objectClass]);
    }

    function it_should_allow_multiple_objectclasses_when_calling_setObjectClass()
    {
        $objectClasses = ['foo', 'bar'];
        $this->setObjectClass($objectClasses);
        $this->getObjectClass()->shouldBeEqualTo($objectClasses);
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
        $this->setAttributeMap(['foo' => 'bar', 'fÓObar' => 'foo']);
        $this->getAttributeToLdap('foo')->shouldBeEqualTo('bar');
        $this->getAttributeToLdap('Fóobar')->shouldBeEqualTo('foo');
    }

    function it_should_return_the_same_attribute_you_pass_it_when_calling_getAttributeToLdap_and_there_is_no_mapping()
    {
        $this->getAttributeToLdap('foo')->shouldBeEqualTo('foo');
    }

    function it_should_return_true_when_calling_hasAttribute_and_the_attribute_is_in_the_schema()
    {
        $this->setAttributeMap(['foo' => 'bar', 'fóo' => 'bar']);
        $this->hasAttribute('foo')->shouldBeEqualTo(true);
        $this->hasAttribute('fÓO')->shouldBeEqualTo(true);
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
        $this->setAttributeMap(['foo' => 'bar', 'panda' => 'bar', 'foobar' => 'fóo']);
        $this->getNamesMappedToAttribute('fÓO')->shouldBeEqualTo(['foobar']);
    }

    function it_should_return_whether_an_ldap_attribute_has_a_name_mapped_to_it_when_calling_hasNameMappedToAttribute()
    {
        $this->setAttributeMap(['foo' => 'bar', 'panda' => 'bar', 'foobar' => 'fóo']);
        $this->hasNamesMappedToAttribute('bar')->shouldBeEqualTo(true);
        $this->hasNamesMappedToAttribute('fÓo')->shouldBeEqualTo(true);
        $this->hasNamesMappedToAttribute('foo')->shouldBeEqualTo(false);
    }

    function it_should_return_the_default_repository_when_calling_getRepository()
    {
        $this->getRepository()->shouldBeEqualTo('\LdapTools\Object\LdapObjectRepository');
    }

    function it_should_set_the_repository_when_calling_setRepository()
    {
        $this->setRepository('\Foo\Bar');
        $this->getRepository()->shouldBeEqualTo('\Foo\Bar');
    }

    function it_should_set_the_required_attributes_when_calling_setRequiredAttributes()
    {
        $this->setRequiredAttributes(['foo', 'bar']);
        $this->getRequiredAttributes()->shouldBeEqualTo(['foo','bar']);
    }

    function it_should_set_the_default_values_when_calling_setDefaultValues()
    {
        $values = ['foo' => 'bar', 'bar' => 'foo'];
        $this->setDefaultValues($values);
        $this->getDefaultValues()->shouldBeEqualTo($values);
    }

    function it_should_have_an_empty_default_container_when_instantiated()
    {
        $this->getDefaultContainer()->shouldBeEqualTo('');
    }

    function it_should_properly_set_the_default_container()
    {
        $ou = 'ou=foo,ou=bar,dc=example,dc=local';
        $this->setDefaultContainer($ou);
        $this->getDefaultContainer()->shouldBeEqualTo($ou);
    }

    function it_should_have_an_empty_for_converter_options_when_instantiated()
    {
        $this->getConverterOptions()->shouldBeEqualTo([]);
    }

    function it_should_properly_set_the_converter_options()
    {
        $options = ['foo' => ['bar']];
        $this->setConverterOptions($options);
        $this->getConverterOptions()->shouldBeEqualTo($options);
    }

    function it_should_properly_set_attributes_that_should_always_return_an_array()
    {
        $attributes = ['foo', 'bar'];
        $this->setMultivaluedAttributes($attributes);
        $this->getMultivaluedAttributes()->shouldBeEqualTo($attributes);
    }

    function it_should_be_able_to_tell_whether_a_specific_attribute_should_return_an_array()
    {
        $attributes = ['foo', 'fóo'];
        $this->setMultivaluedAttributes($attributes);
        $this->isMultivaluedAttribute('foo')->shouldBeEqualTo(true);
        $this->isMultivaluedAttribute('FOO')->shouldBeEqualTo(true);
        $this->isMultivaluedAttribute('FÓO')->shouldBeEqualTo(true);
        $this->isMultivaluedAttribute('fóo')->shouldBeEqualTo(true);
        $this->isMultivaluedAttribute('bar')->shouldBeEqualTo(false);
    }

    function it_should_get_the_converter_for_an_attribute()
    {
        $this->setConverterMap(['foo' => 'bar', 'fóo' => 'bar']);
        $this->getConverter('foo')->shouldBeEqualTo('bar');
        $this->getConverter('FOO')->shouldBeEqualTo('bar');
        $this->getConverter('fÓo')->shouldBeEqualTo('bar');
    }

    function it_should_throw_an_error_if_the_converter_doesnt_exist()
    {
        $this->shouldThrow('\LdapTools\Exception\InvalidArgumentException')->duringGetConverter('foo');
    }

    function it_should_set_the_base_dn()
    {
        $this->getBaseDn()->shouldBeNull();
        $this->setBaseDn('dc=foo,dc=bar');
        $this->getBaseDn()->shouldBeEqualTo('dc=foo,dc=bar');
    }
    
    function it_should_set_the_filter()
    {
        $operator = new bAnd(new Comparison('objectClass', '=', 'user'));
        
        $this->getFilter()->shouldBeNull();
        $this->setFilter($operator);
        $this->getFilter()->shouldBeEqualTo($operator);
    }
    
    function it_should_set_whether_paging_is_used()
    {
        $this->getUsePaging()->shouldBeNull();
        $this->setUsePaging(false);
        $this->getUsePaging()->shouldBeEqualTo(false);
    }
    
    function it_should_set_the_scope()
    {
        $this->getScope()->shouldBeNull();
        $this->setScope(QueryOperation::SCOPE['SUBTREE']);
        $this->getScope()->shouldBeEqualTo(QueryOperation::SCOPE['SUBTREE']);
    }
    
    function it_should_set_ldap_controls()
    {
        $this->getControls()->shouldBeEqualTo([]);
        
        $control1 = new LdapControl('foo', true);
        $control2 = new LdapControl('bar');
        
        $this->setControls($control1, $control2);
        $this->getControls()->shouldBeEqualTo([$control1, $control2]);
    }

    function it_should_set_the_RDN()
    {
        $this->getRdn()->shouldBeEqualTo(['name']);
        $this->setRdn(['foo'])->getRdn()->shouldBeEqualTo(['foo']);
    }
}
