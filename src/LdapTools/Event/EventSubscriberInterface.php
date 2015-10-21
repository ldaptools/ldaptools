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
 * Provides an Event Subscriber interface.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
interface EventSubscriberInterface
{
    /**
     * Get an array of subscribed events.
     *
     * @return array
     */
    public static function getSubscribedEvents();
}
