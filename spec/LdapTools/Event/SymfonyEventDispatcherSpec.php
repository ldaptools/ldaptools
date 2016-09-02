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

use LdapTools\Event\EventSubscriberInterface;
use PhpSpec\ObjectBehavior;
use Symfony\Component\EventDispatcher\EventDispatcher;

class SymfonyEventDispatcherSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Event\SymfonyEventDispatcher');
    }

    function it_should_implement_EventDispatcherInterface()
    {
        $this->shouldImplement('LdapTools\Event\EventDispatcherInterface');
    }

    function it_should_allow_a_symfony_event_dispatcher_in_the_constructor()
    {
        $this->beConstructedWith(new EventDispatcher());
    }

    function it_should_add_a_listener()
    {
        $foo = function() { echo "bar"; };
        $this->addListener('foo', $foo);
        $this->getListeners('foo')->shouldHaveCount(1);
    }

    function it_should_remove_a_listener()
    {
        $foo = function() { echo "bar"; };
        $this->addListener('foo', $foo);
        $this->getListeners('foo')->shouldHaveCount(1);
        $this->removeListener('foo', $foo);
        $this->getListeners('foo')->shouldHaveCount(0);
    }

    function it_should_be_able_to_check_if_a_listener_for_a_name_exists()
    {
        $this->hasListeners('foo')->shouldBeEqualTo(false);
        $foo = function() { echo "bar"; };
        $this->addListener('foo', $foo);
        $this->hasListeners('foo')->shouldBeEqualTo(true);
    }

    function it_should_be_able_to_get_the_listeners_for_a_name()
    {
        $foo = function() { echo "bar"; };
        $this->addListener('foo', $foo);
        $this->getListeners('foo')->shouldBeEqualTo([$foo]);
    }

    function it_should_add_a_subscriber()
    {
        $subscriber = new FooSubscriber();
        $this->addSubscriber($subscriber);
        $this->getListeners('foo')->shouldHaveCount(1);
        $this->getListeners('bar')->shouldHaveCount(1);
    }
}

// Breaking PSR for specs...oh well. This is needed to test a subscriber in the specs.
class FooSubscriber implements EventSubscriberInterface {
    public static function getSubscribedEvents()
    {
        return [
            'foo' => 'onFoo',
            'bar' => 'onBar',
        ];
    }

    public function onFoo()
    {
        echo "foo";
    }

    public function onBar()
    {
        echo "bar";
    }
}
