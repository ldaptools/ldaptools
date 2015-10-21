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

use LdapTools\Event\SymfonyEventDispatcher;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcher;

class EventDispatcherFactorySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Factory\EventDispatcherFactory');
    }

    function it_should_get_a_symfony_event_dispatcher()
    {
        $this->get()->shouldReturnAnInstanceOf('LdapTools\Event\EventDispatcherInterface');
    }

    function it_should_set_an_event_dispatcher_that_can_be_returned()
    {
        $dispatcher = new SymfonyEventDispatcher(new EventDispatcher());
        $this->set($dispatcher);
        $this->get()->shouldBeEqualTo($dispatcher);
    }
}
