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
class LdapObjectCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var LdapObject[]
     */
    protected $objects = [];

    /**
     * @param LdapObject[] ...$ldapObjects
     */
    public function __construct(LdapObject ...$ldapObjects)
    {
        $this->objects = $ldapObjects;
    }

    /**
     * Add LdapObjects to the collection.
     *
     * @param LdapObject[] ...$ldapObjects
     */
    public function add(LdapObject ...$ldapObjects)
    {
        foreach ($ldapObjects as $ldapObject) {
            $this->objects[] = $ldapObject;
        }
    }

    /**
     * Returns an array of LdapObjects. To get the results in a simple array for you should change the hydration when
     * executing the query.
     *
     * @return LdapObject[]
     */
    public function toArray()
    {
        return $this->objects;
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

    /**
     * Sets the collection array pointer to the first element and returns it.
     *
     * @return LdapObject|bool
     */
    public function first()
    {
        return reset($this->objects);
    }

    /**
     * Sets the collection array pointer to the last element and returns it.
     *
     * @return LdapObject|bool
     */
    public function last()
    {
        return end($this->objects);
    }

    /**
     * Gets the current element in the collection array.
     *
     * @return LdapObject|false
     */
    public function current()
    {
        return current($this->objects);
    }

    /**
     * Sets the collection array pointer to the next element and returns it.
     *
     * @return LdapObject|bool
     */
    public function next()
    {
        return next($this->objects);
    }

    /**
     * Sets the collection array pointer to the previous element and returns it.
     *
     * @return LdapObject|bool
     */
    public function previous()
    {
        return prev($this->objects);
    }

    /**
     * Gets the index of the current position in the collection.
     *
     * @return int
     */
    public function key()
    {
        return key($this->objects);
    }
}
