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
use Prophecy\Argument;

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
}
