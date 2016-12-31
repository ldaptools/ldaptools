<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Schema\Parser;

use LdapTools\Configuration;
use LdapTools\Connection\LdapControl;
use LdapTools\DomainConfiguration;
use LdapTools\Exception\SchemaParserException;
use LdapTools\Operation\QueryOperation;
use LdapTools\Schema\LdapObjectSchema;
use PhpSpec\ObjectBehavior;

class SchemaYamlParserSpec extends ObjectBehavior
{
    function let()
    {
        $config = new Configuration();
        $this->beConstructedWith($config->getSchemaFolder());
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Schema\Parser\SchemaYamlParser');
    }

    function it_should_implement_SchemaParserInferface()
    {
        $this->shouldImplement('\LdapTools\Schema\Parser\SchemaParserInterface');
    }

    function it_should_return_LdapObjectSchema_when_parsing()
    {
        $domain = new DomainConfiguration('example.com');
        $domain->setLdapType('ad');

        $this->parse($domain->getLdapType(), 'user')->shouldReturnAnInstanceOf('\LdapTools\Schema\LdapObjectSchema');
    }

    function it_should_throw_a_SchemaParserException_when_the_schema_file_is_not_readable()
    {
        $fakePath = '/this/path/should/never/really/exist/I/would/hope';
        $this->beConstructedWith($fakePath);

        $domain = new DomainConfiguration('example.com');
        $domain->setLdapType('ad');

        $this->shouldThrow(new SchemaParserException('Cannot find schema for "ad" in "'.$fakePath.'"'))->duringParse(
            $domain->getLdapType(),
            'user'
        );
    }

    function it_should_throw_a_SchemaParserException_when_the_schema_is_missing_an_objects_definition(){
        $folder = __DIR__.'/../../../resources/schema';
        $schema = 'no_objects';
        $this->beConstructedWith($folder);

        $this->shouldThrow(new SchemaParserException('Cannot find the "objects" section in the schema "'.$schema.'" in "'.$folder.'".'))->duringParse(
            'no_objects',
            'user'
        );
    }

    function it_should_throw_a_SchemaParserException_when_the_schema_does_not_have_the_object_type(){
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->shouldThrow(new SchemaParserException('Cannot find object type "pandas" in schema.'))->duringParse(
            'missing_fields',
            'pandas'
        );
    }

    function it_should_throw_a_SchemaParserException_when_the_schema_object_type_has_no_class_or_category(){
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->shouldThrow(new SchemaParserException('Object type "group" must have one of the following defined: class, category, filter'))->duringParse(
            'missing_fields',
            'group'
        );
    }

    function it_should_throw_a_SchemaParserException_when_the_schema_object_has_no_attributes(){
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->shouldThrow(new SchemaParserException('Object type "distributionlist" has no attributes defined.'))->duringParse(
            'missing_fields',
            'distributionlist'
        );
    }

    function it_should_set_default_attributes_to_select_in_LdapObjectSchema_when_parsing()
    {
        $attributes = ['name', 'firstName', 'lastName','username', 'emailAddress', 'dn', 'guid'];
        $this->parse('ad', 'user')
            ->getAttributesToSelect()
            ->shouldBeEqualTo($attributes);
    }

    function it_should_parse_a_custom_repository_for_an_object()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->parse('example', 'user')
            ->getRepository()
            ->shouldBeEqualTo('\Foo\Bar');
    }

    function it_should_return_a_datetime_when_calling_getSchemaModificationTime()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->getSchemaModificationTime('example')->shouldReturnAnInstanceOf('\DateTime');
    }

    function it_should_error_when_calling_getSchemaModificationTime_for_a_non_existing_schema()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->shouldThrow('\Exception')->duringGetSchemaModificationTime('foo');
    }

    function it_should_parse_default_attributes_for_an_object()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->parse('example', 'user')
            ->getDefaultValues()
            ->shouldHaveKey('displayName');
    }

    function it_should_parse_required_attributes_for_an_object()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->parse('example', 'user')
            ->getRequiredAttributes()
            ->shouldBeEqualTo(['username', 'password']);
    }

    function it_should_parse_the_default_container_for_an_object()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->parse('example', 'user')
            ->getDefaultContainer()
            ->shouldBeEqualTo('ou=foo,ou=bar,dc=example,dc=local');
    }

    function it_should_parse_the_base_dn_for_an_object()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->parse('example', 'user')
            ->getBaseDn()
            ->shouldBeEqualTo('ou=bar,dc=example,dc=local');
    }

    function it_should_parse_a_schema_that_extends_a_default_schema()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->parse('extends_default', 'user')
            ->getDefaultContainer()
            ->shouldBeEqualTo('ou=foo,ou=bar,dc=example,dc=local');
        $this->parse('extends_default', 'user')
            ->hasAttribute('username')
            ->shouldBeEqualTo(true);
        $this->parse('extends_default', 'user')
            ->hasAttribute('foo')
            ->shouldBeEqualTo(true);
        $this->parse('extends_default', 'user')
            ->hasAttribute('username')
            ->shouldBeEqualTo(true);
        $this->parse('extends_default', 'user')
            ->getRepository()
            ->shouldBeEqualTo('\Foo\Bar\Repo');
        $this->parse('extends_default', 'user')
            ->getRequiredAttributes()
            ->shouldContain('foo');
        $this->parse('extends_default', 'user')
            ->getRequiredAttributes()
            ->shouldContain('username');
    }

    function it_should_parse_a_schema_with_an_object_that_extends_a_default_schema_object()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->parse('example', 'extend_default')
            ->getRepository()
            ->shouldBeEqualTo('\Foo\Bar');
        $this->parse('example', 'extend_default')
            ->getRequiredAttributes()
            ->shouldContain('username');
    }

    function it_should_parse_a_schema_with_an_object_that_extends_another_object()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->parse('example', 'custom_contact')
            ->getAttributesToSelect()
            ->shouldContain('fax');
        $this->parse('example', 'custom_contact')
            ->getRequiredAttributes()
            ->shouldContain('name');
    }

    function it_should_parse_a_schema_with_an_object_that_extends_a_schema_from_the_same_directory()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->parse('extends', 'foo')
            ->getRepository()
            ->shouldBeEqualTo('\no\type');
    }

    function it_should_parse_a_schema_objects_converter_options()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->parse('example', 'converter_options')
            ->getConverterOptions()
            ->shouldHaveKey('generalized_time');
        $this->parse('example', 'converter_options')
            ->getConverterOptions()
            ->shouldContain(['type' => 'windows']);
    }

    function it_should_parse_a_schema_objects_multivalued_attriutes()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->parse('example', 'user')
            ->getMultivaluedAttributes()
            ->shouldBeEqualTo(['otherHomePhone']);
    }

    function it_should_be_able_to_parse_all_types_in_a_schema_and_return_an_array_of_LdapObjectSchema()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->parseAll('example')->shouldBeArray();
        $this->parseAll('example')->shouldHaveCount(15);
        $this->parseAll('example')->shouldReturnAnArrayOfLdapObjectSchemas();
    }

    function it_should_parse_a_schema_that_includes_additional_schema_files()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->parse('includes', 'user')->getObjectClass()->shouldBeEqualTo(['user']);
        $this->parse('includes', 'foo')->shouldReturnAnInstanceOf('\LdapTools\Schema\LdapObjectSchema');
    }

    function it_should_parse_a_schema_that_includes_additional_default_schema_files()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->parse('includes_default', 'user')->shouldReturnAnInstanceOf('\LdapTools\Schema\LdapObjectSchema');
        $this->parse('includes_default', 'foo')->shouldReturnAnInstanceOf('\LdapTools\Schema\LdapObjectSchema');
    }

    function it_should_be_able_to_load_a_file_with_a_YAML_extension()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->parse('extension', 'foo')->shouldReturnAnInstanceOf('\LdapTools\Schema\LdapObjectSchema');
    }

    function it_should_allow_a_schema_object_type_that_has_multiple_classes_defined_in_objectClass()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->parse('filter', 'MultipleClasses')->getFilter()->toLdapFilter()->shouldBeEqualTo('(&(objectClass=user)(objectClass=person))');
    }

    function it_should_allow_a_schema_object_type_that_has_only_a_category_defined()
    {
        $this->beConstructedWith(__DIR__ . '/../../../resources/schema');

        $this->parse('filter', 'CategoryOnly')->getFilter()->toLdapFilter()->shouldBeEqualTo('(objectCategory=user)');
    }

    function it_should_allow_a_schema_object_type_that_has_only_a_single_class_defined()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->parse('filter', 'ClassOnly')->getFilter()->toLdapFilter()->shouldBeEqualTo('(objectClass=user)');
    }

    function it_should_allow_a_schema_object_type_that_has_a_single_category_and_class_defined()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->parse('filter', 'ClassAndCategory')->getFilter()->toLdapFilter()->shouldBeEqualTo('(&(objectCategory=person)(objectClass=user))');
    }

    function it_should_allow_a_schema_object_type_that_has_a_single_category_and_multiple_classes_defined()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->parse('filter', 'MultipleClassesAndCategory')->getFilter()->toLdapFilter()->shouldBeEqualTo('(&(objectCategory=foo)(&(objectClass=user)(objectClass=person)))');
    }
    
    function it_should_allow_a_schema_object_type_that_has_only_a_filter_defined()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->parse('filter', 'FilterOnly')->getFilter()->toLdapFilter()->shouldBeEqualTo('(&(objectClass=user)(objectCategory=person))');
    }

    function it_should_allow_a_schema_object_type_that_has_a_filter_defined_with_a_class_and_category()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->parse('filter', 'All')->getFilter()->toLdapFilter()->shouldBeEqualTo('(&(&(objectCategory=person)(objectClass=user))(&(foo=*)))');
    }

    function it_should_parse_a_schema_object_with_controls_listed()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $control1 = new LdapControl('foo', true, 'bar');
        $control2 = new LdapControl('bar');
        
        $this->parse('example', 'controls')->getControls()->shouldBeLike([$control1, $control2]);
    }
    
    function it_should_parse_a_schema_object_with_paging_set()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->parse('example', 'paging')->getUsePaging()->shouldBeEqualTo(false);   
    }

    function it_should_parse_a_schema_object_with_the_scope_set()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->parse('example', 'scope')->getScope()->shouldBeEqualTo(QueryOperation::SCOPE['ONELEVEL']);
    }

    function it_should_throw_an_error_when_parsing_an_incorrect_scope()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->shouldThrow(new SchemaParserException('The scope "foo" is not valid. Valid types are: subtree, onelevel, base'))->duringParse('incorrect_scope', 'scope');
    }

    function it_should_parse_a_schema_that_extends_a_default_schema_with_a_type_that_extends_a_different_default_schema()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->parse('extend_default_twice', 'CustomRootDSE')->getFilter()->toLdapFilter()->shouldBeEqualTo('(&(objectClass=*))');
        $this->parse('extend_default_twice', 'CustomRootDSE')->getUsePaging()->shouldBeEqualTo(true);
        $this->parse('extend_default_twice', 'CustomRootDSE')->getAttributeMap()->shouldContain('bar');
        $this->parse('extend_default_twice', 'CustomRootDSE')->getAttributeMap()->shouldContain('defaultNamingContext');
        $this->parse('extend_default_twice', 'CustomRootDSE')->getAttributeMap()->shouldHaveKey('foo');
    }

    function it_should_be_case_insensitive_when_getting_a_ldap_object_schema_type()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->parse('example', 'UseR')->getObjectType()->shouldBeEqualTo('user');
    }


    function it_should_set_a_default_RDN_in_the_LdapObjectSchema_when_parsing()
    {
        $this->parse('ad', 'user')->getRdn()->shouldBeEqualTo(['name']);
    }

    function it_should_set_a_RDN_when_specified()
    {
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->parse('example', 'CustomRDN')->getRdn()->shouldBeEqualTo(['foo']);
    }

    function getMatchers()
    {
        return [
            'returnAnArrayOfLdapObjectSchemas' => function($ldapObjectSchemas) {
                foreach ($ldapObjectSchemas as $schema) {
                    if (!($schema instanceof LdapObjectSchema)) {
                        return false;
                    }
                }

                return true;
            }
        ];
    }
}
