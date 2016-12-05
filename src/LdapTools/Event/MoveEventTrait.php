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
 * Helps reduce some duplication between a move and a restore event.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
trait MoveEventTrait
{
    /**
     * @var string The location the LDAP object is moving to.
     */
    protected $container;

    /**
     * Get the location the LDAP object is moving to.
     *
     * @return string
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Set the location the LDAP object is moving to.
     *
     * @param string $container The OU/container in DN form.
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }
}
