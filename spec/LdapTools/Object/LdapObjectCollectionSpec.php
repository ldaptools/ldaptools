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

class LdapObjectCollectionSpec extends ObjectBehavior
{
    protected $ldapObjects = [];

    function let()
    {
        $this->ldapObjects[] = new LdapObject(['firstName' => 'Natalie'],['user'],'user','user');
        $this->ldapObjects[] = new LdapObject(['firstName' => 'Scott'],['user'],'user','user');
        $this->ldapObjects[] = new LdapObject(['firstName' => 'Timmy'],['user'],'user','user');
        $this->ldapObjects[] = new LdapObject(['firstName' => 'Chad'],['user'],'user','user');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Object\LdapObjectCollection');
    }

    function it_should_implement_an_interator()
    {
        $this->shouldHaveType('\IteratorAggregate');
    }

    function it_should_implement_countable()
    {
        $this->shouldImplement('\Countable');
    }

    function it_should_expect_any_number_of_objects_to_the_constructor()
    {
        $this->beConstructedWith(...$this->ldapObjects);
        $this->count()->shouldBeEqualTo(4);
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

    function it_should_return_an_array_of_objects_when_calling_to_array()
    {
        $this->add(new LdapObject(['foo' => 'bar']));
        $this->toArray()->shouldBeArray();
        $this->toArray()->shouldHaveCount(1);
    }

    function it_should_get_the_last_item_of_the_collection()
    {
        $this->add(...$this->ldapObjects);
        $this->last()->getFirstName()->shouldBeEqualTo('Chad');
    }

    function it_should_get_the_first_item_of_the_collection()
    {
        $this->add(...$this->ldapObjects);
        $this->last()->getFirstName()->shouldBeEqualTo('Chad');
        $this->first()->getFirstName()->shouldBeEqualTo('Natalie');
    }

    function it_should_advance_to_the_next_item_when_calling_next()
    {
        $this->add(...$this->ldapObjects);
        $this->next()->getFirstName()->shouldBeEqualTo('Scott');
    }

    function it_should_go_back_to_the_previous_item_when_calling_previous()
    {
        $this->add(...$this->ldapObjects);
        $this->next()->getFirstName()->shouldBeEqualTo('Scott');
        $this->previous()->getFirstName()->shouldBeEqualTo('Natalie');
    }

    function it_should_get_the_current_object_when_calling_current()
    {
        $this->add(...$this->ldapObjects);
        $this->last();
        $this->previous();
        $this->current()->getFirstName()->shouldBeEqualTo('Timmy');
    }

    function it_should_get_the_current_index_when_calling_key()
    {
        $this->add(...$this->ldapObjects);
        $this->key()->shouldBeEqualTo(0);
    }
}
