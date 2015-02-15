<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Object;

/**
 * Represents a collection of LdapObject classes. Allows for iteration, filtering, etc.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapObjectCollection implements \IteratorAggregate
{
    /**
     * @var LdapObject[]
     */
    protected $objects = [];

    /**
     * Add LdapObjects to the collection.
     *
     * @param LdapObject[] $ldapObjects
     */
    public function add(LdapObject ...$ldapObjects)
    {
        foreach ($ldapObjects as $ldapObject) {
            $this->objects[] = $ldapObject;
        }
    }

    /**
     * Allows this object to be iterated over.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->objects);
    }

    /**
     * The number of LdapObjects in the collection.
     *
     * @return int
     */
    public function count()
    {
        return count($this->objects);
    }
}
