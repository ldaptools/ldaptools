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
 * Represents an Event on an LDAP object.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapObjectEvent extends Event
{
    /**
     * @var LdapObject
     */
    protected $ldapObject;

    /**
     * @param string $name
     * @param LdapObject $ldapObject
     */
    public function __construct($name, LdapObject $ldapObject)
    {
        $this->ldapObject = $ldapObject;
        parent::__construct($name);
    }

    /**
     * @return LdapObject
     */
    public function getLdapObject()
    {
        return $this->ldapObject;
    }
}
