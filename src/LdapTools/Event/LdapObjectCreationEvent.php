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
 * An event for the creation of a LDAP object.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapObjectCreationEvent extends Event
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var null|string
     */
    protected $container;

    /**
     * @var null|string
     */
    protected $dn;

    /**
     * @var null|string
     */
    protected $type;

    /**
     * @param string $name
     * @param null|string $type
     */
    public function __construct($name, $type = null)
    {
        $this->type = $type;
        parent::__construct($name);
    }

    /**
     * @return null|string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the data to be sent to LDAP.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the data to be sent to LDAP. This is only relevant for the 'before create' event.
     *
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get the container/OU where the LDAP object will be placed.
     *
     * @return null|string
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Set the container/OU where the LDAP object will be placed. This is only relevant for the 'before create' event.
     *
     * @param string|null $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * Get the DN for the LDAP object.
     *
     * @return null|string
     */
    public function getDn()
    {
        return $this->dn;
    }

    /**
     * Set the DN to be used for the LDAP object. This is only relevant for the 'before create' event.
     * @param string|null $dn
     */
    public function setDn($dn)
    {
        $this->dn = $dn;
    }
}
