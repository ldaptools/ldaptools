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

/**
 * Provides an Event Dispatcher interface.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
interface EventDispatcherInterface
{
    /**
     * Dispatch an event.
     *
     * @param EventInterface $event
     */
    public function dispatch(EventInterface $event);

    /**
     * Add a listener to an event name.
     *
     * @param string $eventName
     * @param callable $listener
     */
    public function addListener($eventName, callable $listener);

    /**
     * Remove a listener from an event name.
     *
     * @param string $eventName
     * @param callable $listener
     */
    public function removeListener($eventName, callable $listener);

    /**
     * Check if an event name has any listeners associated with it.
     *
     * @param string $eventName
     * @return bool
     */
    public function hasListeners($eventName);

    /**
     * Get the listeners for an event name.
     *
     * @param string $eventName
     * @return array An array of event listeners.
     */
    public function getListeners($eventName);

    /**
     * Add an event subscriber.
     *
     * @param EventSubscriberInterface $subscriber
     */
    public function addSubscriber(EventSubscriberInterface $subscriber);
}
