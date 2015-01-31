<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Factory;

use LdapTools\Configuration;
use LdapTools\Factory\CacheFactory;
use LdapTools\Factory\SchemaParserFactory;
use LdapTools\Object\LdapObjectType;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LdapObjectSchemaFactorySpec extends ObjectBehavior
{
    function let()
    {
        $config = new Configuration();
        $parser = SchemaParserFactory::get('yml', $config->getSchemaFolder());
        $cache = CacheFactory::get('none', []);
        $this->beConstructedWith($cache, $parser);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Factory\LdapObjectSchemaFactory');
    }

    function it_should_return_an_LdapObjectSchema_object_when_calling_get()
    {
        $this->get('ad', LdapObjectType::USER)->shouldReturnAnInstanceOf('\LdapTools\Schema\LdapObjectSchema');
    }

    function it_should_throw_a_parser_exception_when_the_schema_object_type_is_not_found()
    {
        $this->shouldThrow('\LdapTools\Exception\SchemaParserException')->duringGet('foo', 'bar');
    }
}
