<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Factory;

use LdapTools\Event\EventDispatcherInterface;
use LdapTools\Event\SymfonyEventDispatcher;

/**
 * A factory for retrieving the event dispatcher by its type.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class EventDispatcherFactory
{
    /**
     * @var EventDispatcherInterface
     */
    protected static $dispatcher;

    /**
     * Retrieve the Event Dispatcher instance
     *
     * @return EventDispatcherInterface
     */
    public static function get()
    {
        if (self::$dispatcher) {
            $dispatcher = self::$dispatcher;
        } else {
            self::$dispatcher = new SymfonyEventDispatcher();
            $dispatcher = self::$dispatcher;
        }

        return $dispatcher;
    }

    /**
     * Set the event dispatcher to use.
     *
     * @param EventDispatcherInterface $dispatcher
     */
    public static function set(EventDispatcherInterface $dispatcher)
    {
        self::$dispatcher = $dispatcher;
    }
}
