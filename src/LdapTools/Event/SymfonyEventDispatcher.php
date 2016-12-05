<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Event;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface as SymfonyEventDispatcherInterface;

/**
 * Provides a wrapper around the Symfony Event Dispatcher.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class SymfonyEventDispatcher implements EventDispatcherInterface
{
    /**
     * @var SymfonyEventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @param SymfonyEventDispatcherInterface|null $dispatcher
     */
    public function __construct(SymfonyEventDispatcherInterface $dispatcher = null)
    {
        $this->dispatcher = $dispatcher ?: new EventDispatcher();
    }

    /**
     * @inheritdoc
     */
    public function dispatch(EventInterface $event)
    {
        foreach ($this->dispatcher->getListeners($event->getName()) as $listener) {
            call_user_func($listener, $event);
        }
    }

    /**
     * @inheritdoc
     */
    public function addListener($eventName, callable $listener)
    {
        $this->dispatcher->addListener($eventName, $listener);
    }

    /**
     * @inheritdoc
     */
    public function hasListeners($eventName)
    {
        return $this->dispatcher->hasListeners($eventName);
    }

    /**
     * @inheritdoc
     */
    public function getListeners($eventName)
    {
        return $this->dispatcher->getListeners($eventName);
    }

    /**
     * @inheritdoc
     */
    public function removeListener($eventName, callable $listener)
    {
        $this->dispatcher->removeListener($eventName, $listener);
    }

    /**
     * @inheritdoc
     */
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        // This is what the actual Symfony Event Dispatcher does. Replicate it to remove the dependency on its interface.
        foreach ($subscriber->getSubscribedEvents() as $eventName => $params) {
            if (is_string($params)) {
                $this->dispatcher->addListener($eventName, array($subscriber, $params));
            } elseif (is_string($params[0])) {
                $this->dispatcher->addListener($eventName, array($subscriber, $params[0]), isset($params[1]) ? $params[1] : 0);
            } else {
                foreach ($params as $listener) {
                    $this->dispatcher->addListener($eventName, array($subscriber, $listener[0]), isset($listener[1]) ? $listener[1] : 0);
                }
            }
        }
    }
}
