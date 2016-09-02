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

use PhpSpec\ObjectBehavior;

class SchemaParserFactorySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Factory\SchemaParserFactory');
    }

    function it_should_have_a_constant_for_the_yaml_parser_type()
    {
        $this->shouldHaveConstant('TYPE_YML');
    }

    function it_should_return_the_correct_parser_type_when_calling_get()
    {
        $this->get('yml', 'foo')->shouldBeAnInstanceOf('\LdapTools\Schema\Parser\SchemaYamlParser');
    }

    function it_should_thrown_InvalidArgumentException_when_passing_unknown_parser_types()
    {
        $this->shouldThrow('\LdapTools\Exception\InvalidArgumentException')->duringGet('foo', 'bar');
    }

    public function getMatchers()
    {
        return [
            'haveConstant' => function($subject, $constant) {
                return defined('\LdapTools\Factory\SchemaParserFactory::'.$constant);
            }
        ];
    }
}
