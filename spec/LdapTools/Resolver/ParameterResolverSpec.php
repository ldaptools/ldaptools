<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Resolver;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ParameterResolverSpec extends ObjectBehavior
{
    function let()
    {
        $attributes = [
            'firstName' => '%foo%',
            'lastName' => '%bar%',
            'displayName' => '%lastname%, %firstname%',
        ];
        $parameters = [
            'foo' => 'Emmett',
            'bar' => 'Brown',
        ];
        $this->beConstructedWith($attributes, $parameters);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Resolver\ParameterResolver');
    }

    function it_should_return_an_array_when_calling_resolve()
    {
        $this->resolve()->shouldBeArray();
    }

    function it_should_correctly_parse_passed_parameters()
    {
        $this->resolve()->shouldHaveKeyWithValue('firstName', 'Emmett');
        $this->resolve()->shouldHaveKeyWithValue('lastName', 'Brown');
        $this->resolve()->shouldHaveKeyWithValue('displayName', 'Brown, Emmett');
    }

    function it_should_be_case_insensitive_for_parameter_names()
    {
        $attributes = [
            'firstName' => '%BAR%',
            'lastName' => '%fIrStNaMe%',
        ];
        $parameters = [
            'bAr' => 'Emmett'
        ];
        $this->beConstructedWith($attributes, $parameters);

        $this->resolve()->shouldHaveKeyWithValue('firstName', 'Emmett');
        $this->resolve()->shouldHaveKeyWithValue('lastName', 'Emmett');
    }

    function it_should_return_attributes_in_the_same_case_they_were_passed()
    {
        $attributes = [
            'FirstNamE' => '%BAR%',
        ];
        $parameters = [
            'bAr' => 'Emmett'
        ];
        $this->beConstructedWith($attributes, $parameters);

        $this->resolve()->shouldHaveKey('FirstNamE');
    }

    function it_should_detect_circular_references_in_parameters()
    {
        $attributes = [
            'firstName' => '%lastName%',
            'lastName' => '%firstName%',
            'username' => 'foo',
        ];
        $this->beConstructedWith($attributes, []);

        $this->shouldThrow('LdapTools\Exception\LogicException')->duringResolve();
    }

    function it_should_return_attributes_correctly_when_no_parameters_are_used()
    {
        $attributes = [
            'firstName' => 'Foo',
            'lastName' => 'Bar',
        ];
        $this->beConstructedWith($attributes, []);

        $this->resolve()->shouldBeEqualTo($attributes);
    }

    function it_should_resolve_parameters_in_the_correct_order()
    {
        $attributes = [
            'displayName' => '%lastname%, %firstname% as %username%',
            'firstName' => '%foo%',
            'lastName' => '%bar%',
            'username' => '%city%',
            'city' => 'Hill Valley',
        ];
        $parameters = [
            'foo' => 'Emmett',
            'bar' => 'Brown',
        ];
        $this->beConstructedWith($attributes, $parameters);

        $this->resolve()->shouldHaveKeyWithValue('displayName', 'Brown, Emmett as Hill Valley');
        $this->resolve()->shouldHaveKeyWithValue('firstName', 'Emmett');
        $this->resolve()->shouldHaveKeyWithValue('lastName', 'Brown');
        $this->resolve()->shouldHaveKeyWithValue('username', 'Hill Valley');
        $this->resolve()->shouldHaveKeyWithValue('city', 'Hill Valley');
    }

    function it_should_handle_an_attribute_value_as_an_array()
    {
        $attributes = [
            'displayName' => '%lastname%, %firstname% as %username%',
            'firstName' => ['%foo%'],
            'lastName' => '%bar%',
            'username' => '%city%',
            'city' => 'Hill Valley',
            'foobar' => ['%foo%', '%bar%', 'bleh']
        ];
        $parameters = [
            'foo' => 'Emmett',
            'bar' => 'Brown',
        ];
        $this->beConstructedWith($attributes, $parameters);

        $this->resolve()->shouldHaveKeyWithValue('displayName', 'Brown, Emmett as Hill Valley');
        $this->resolve()->shouldHaveKeyWithValue('firstName', ['Emmett']);
        $this->resolve()->shouldHaveKeyWithValue('lastName', 'Brown');
        $this->resolve()->shouldHaveKeyWithValue('username', 'Hill Valley');
        $this->resolve()->shouldHaveKeyWithValue('city', 'Hill Valley');
        $this->resolve()->shouldHaveKeyWithValue('foobar', ['Emmett', 'Brown', 'bleh']);
    }

    function it_should_throw_an_exception_when_trying_to_use_a_multivalued_attribute_as_a_parameter()
    {
        $attributes = [
            'displayName' => '%firstname%',
            'firstName' => ['%foo%', '%bar%'],
        ];
        $parameters = [
            'foo' => 'Emmett',
            'bar' => 'Brown',
        ];
        $this->beConstructedWith($attributes, $parameters);

        $this->shouldThrow('\LdapTools\Exception\InvalidArgumentException')->duringResolve();
    }

    public function getMatchers()
    {
        return [
            'haveKeyWithValue' => function($subject, $key, $value) {
                return isset($subject[$key]) && ($subject[$key] === $value);
            },
        ];
    }
}
