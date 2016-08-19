<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Utilities;

use PhpSpec\ObjectBehavior;

class MBStringSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Utilities\MBString');
    }

    function it_should_get_a_specific_character_given_a_code_point()
    {
        $this::chr(65)->shouldEqual('A');
        $this->chr(225)->shouldEqual('á');
    }
    
    function it_should_get_a_code_point_for_a_given_character()
    {
        $this::ord('A')->shouldEqual(65);
        $this::ord('á')->shouldEqual(225);        
    }
    
    function it_should_split_a_string_and_return_an_array_of_chars()
    {
        $this::str_split('foo')->shouldEqual(['f', 'o', 'o']);
        $this::str_split('bár')->shouldEqual(['b', 'á', 'r']);
    }
    
    function it_should_compare_two_strings_and_return_an_integer_value()
    {
        $this::compare('foo', 'bar')->shouldBeEqualTo(1);
        $this::compare('bar', 'foo')->shouldBeEqualTo(-1);
        $this::compare('foo', 'foo')->shouldBeEqualTo(0);

        $this::compare('Böb', 'Ädam')->shouldBeEqualTo(1);
        $this::compare('Ädam', 'Böb')->shouldBeEqualTo(-1);
        $this::compare('Ädam', 'Ädam')->shouldBeEqualTo(0);
    }

    function it_should_make_a_string_lower_case()
    {
        $this::strtolower('Ädam')->shouldEqual('ädam');
        $this::strtolower('FOO')->shouldEqual('foo');
    }

    function it_should_change_the_case_of_the_keys_for_an_array()
    {
        $this::array_change_key_case(['Ädam' => 'Foo', 'Böb' => 'foo', 'foO' => 'foo'])->shouldBeEqualTo(
           ['ädam' => 'Foo', 'böb' => 'foo', 'foo' => 'foo']
        );
    }

    function it_should_change_the_case_of_the_values_for_an_array()
    {
        $this::array_change_value_case(['Foo' => 'Ädam', 'bar' => 'Böb', 'foobar' => 'fOo'])->shouldBeEqualTo(
            ['Foo' => 'ädam', 'bar' => 'böb', 'foobar' => 'foo']
        );
    }

    function it_should_find_a_value_in_an_array_and_return_it_in_its_original_case()
    {
        $this::array_search_get_value('foo', ['FoO', 'bar'])->shouldBeEqualTo('FoO');
        $this::array_search_get_value('foo', ['foo', 'bar'])->shouldBeEqualTo('foo');
        $this::array_search_get_value('ädam', ['Ädam', 'Böb', 'foo'])->shouldBeEqualTo('Ädam');
        $this->shouldThrow('LdapTools\Exception\InvalidArgumentException')->during('array_search_get_value', ['foobar', ['foo', 'bar']]);
    }
}
