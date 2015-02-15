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

use LdapTools\Object\LdapObject;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LdapObjectCollectionSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Object\LdapObjectCollection');
    }

    function it_should_implement_an_interator()
    {
        $this->shouldHaveType('\IteratorAggregate');
    }

    function it_should_add_objects_to_the_collection()
    {
        $this->add(new LdapObject(['foo' => 'bar']));
    }

    function it_should_return_a_count_of_the_objects_it_contains()
    {
        $this->count()->shouldBeEqualTo(0);
        $this->add(new LdapObject(['foo' => 'bar']));
        $this->count()->shouldBeEqualTo(1);
    }
}
