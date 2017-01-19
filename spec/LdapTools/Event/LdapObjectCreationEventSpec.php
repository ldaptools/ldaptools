<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Event;

use PhpSpec\ObjectBehavior;

class LdapObjectCreationEventSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('foo');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Event\LdapObjectCreationEvent');
    }

    function it_should_have_type_Event()
    {
        $this->shouldHaveType('LdapTools\Event\Event');
    }

    function it_should_implement_EventInterface()
    {
        $this->shouldImplement('LdapTools\Event\EventInterface');
    }

    function it_should_get_the_event_name()
    {
        $this->getName()->shouldBeEqualTo('foo');
    }

    function it_should_set_data()
    {
        $data = ['foo'];
        $this->setData($data);
        $this->getData()->shouldBeEqualTo($data);
    }

    function it_should_set_the_container()
    {
        $container = 'dc=foo,dc=bar';
        $this->setContainer($container);
        $this->getContainer()->shouldBeEqualTo($container);
    }

    function it_should_set_the_dn()
    {
        $dn = 'cn=foobar,dc=foo,dc=bar';
        $this->setDn($dn);
        $this->getDn()->shouldBeEqualTo($dn);
    }

    function it_should_get_the_type()
    {
        $this->beConstructedWith('foo', 'bar');
        $this->getType()->shouldBeEqualTo('bar');
    }
}
