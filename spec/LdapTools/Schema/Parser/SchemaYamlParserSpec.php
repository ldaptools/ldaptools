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
use LdapTools\DomainConfiguration;
use LdapTools\Exception\SchemaParserException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

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

        $this->shouldThrow(new SchemaParserException('Cannot read schema file: '.$fakePath.'/ad.yml'))->duringParse(
            $domain->getLdapType(),
            'user'
        );
    }

    function it_should_throw_a_SchemaParserException_when_the_schema_is_missing_an_objects_definition(){
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->shouldThrow(new SchemaParserException('Cannot find the "objects" section in the schema file.'))->duringParse(
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

    function it_should_throw_a_SchemaParserException_when_the_schema_object_type_has_no_class(){
        $this->beConstructedWith(__DIR__.'/../../../resources/schema');

        $this->shouldThrow(new SchemaParserException('Object type "group" has no class defined.'))->duringParse(
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
        $attributes = ['firstName', 'lastName','username', 'emailAddress', 'dn', 'guid'];
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
}
