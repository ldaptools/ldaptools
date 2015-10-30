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

use LdapTools\Object\LdapObject;

/**
 * An event for when a LDAP object is moved.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapObjectMoveEvent extends LdapObjectEvent
{
    /**
     * @var string The location the LDAP object is moving/moved to.
     */
    protected $container;

    /**
     * @param string $eventName
     * @param LdapObject $object
     * @param string $container
     */
    function __construct($eventName, LdapObject $object, $container)
    {
        $this->container = $container;
        parent::__construct($eventName, $object);
    }

    /**
     * Get the location the LDAP object is moving/moved to.
     *
     * @return string
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Set the location the LDAP object should move to.
     *
     * @param string $container The OU/container in DN form.
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }
}
