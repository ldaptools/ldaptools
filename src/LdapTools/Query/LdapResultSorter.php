<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Query;

use LdapTools\Object\LdapObject;
use LdapTools\Object\LdapObjectCollection;
use LdapTools\Utilities\LdapUtilities;
use LdapTools\Utilities\MBString;

/**
 * Sorts LDAP results by specified attributes and direction (ASC, DESC).
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapResultSorter
{
    /**
     * @var array
     */
    protected $orderBy = [];

    /**
     * @var \LdapTools\Schema\LdapObjectSchema[]
     */
    protected $aliases = [];

    /**
     * @var bool
     */
    protected $caseSensitive = false;

    /**
     * @param array $orderBy
     * @param \LdapTools\Schema\LdapObjectSchema[] $aliases The aliases used, if any (in the form of ['alias' => LdapObjectSchema])
     */
    public function __construct(array $orderBy = [], array $aliases = [])
    {
        $this->orderBy = $orderBy;
        $this->aliases = $aliases;
    }

    /**
     * Set whether or not the results will be sorted case-sensitive.
     *
     * @param bool $caseSensitive
     * @return $this
     */
    public function setIsCaseSensitive($caseSensitive)
    {
        $this->caseSensitive = (bool) $caseSensitive;

        return $this;
    }

    /**
     * Get whether or not the results will be sorted case-sensitive.
     *
     * @return bool
     */
    public function getIsCaseSensitive()
    {
        return $this->caseSensitive;
    }

    /**
     * Reorganize the array to the desired orderBy methods passed to the class.
     *
     * @param array|LdapObjectCollection $results The unsorted result set.
     * @return array|LdapObjectCollection The sorted result set.
     */
    public function sort($results)
    {
        $isCollection = $results instanceof LdapObjectCollection;
        $results = $isCollection ? $results->toArray() : $results;

        // We need to track the index when sorting to ensure a stable sort. This is essentially how PHP 7+ handles
        // things, but not PHP 5.6. I think this would be the expected order, especially for tests/specs...
        foreach ($results as $index => &$item) {
            $item = [$index + 1, $item];
        }
        usort($results, [$this, 'resultSortCallback']);
        // Sort done, so remove the index tracking from the results...
        foreach ($results as &$item) {
            $item = $item[1];
        }

        return $isCollection ? new LdapObjectCollection(...$results) : $results;
    }

    /**
     * Goes through each orderBy value to run a comparison to determine the value to pass back to usort.
     *
     * @param mixed $a
     * @param mixed $b
     * @return int
     */
    protected function resultSortCallback($a, $b)
    {
        $retVal = 0;

        foreach ($this->orderBy as $attribute => $direction) {
            if ($retVal === 0) {
                $retVal = $this->getUsortReturnValue($attribute, $direction, $a[1], $b[1]);
            }
        }

        return $retVal === 0 ? $a[0] - $b[0] : $retVal;
    }

    /**
     * Based on the attribute and direction, compare the LDAP objects to get a return value for usort.
     *
     * @param string $attribute
     * @param string $direction
     * @param array|LdapObject $a
     * @param array|LdapObject $b
     * @return int
     */
    protected function getUsortReturnValue($attribute, $direction, $a, $b)
    {
        $compare = [
            $this->getComparisonValue($a, $attribute),
            $this->getComparisonValue($b, $attribute),
        ];
        // Depending on the direction of the sort, the order of the comparison may need to be reversed.
        $compare = ($direction == LdapQuery::ORDER['DESC']) ? array_reverse($compare) : $compare;

        // This makes sure that objects with non-existent/empty valued attributes go to the end.
        // I think this would probably be the desired behavior?
        if (empty($compare[0]) && !empty($compare[1])) {
            return $direction == LdapQuery::ORDER['ASC'] ? 1 : -1;
        } elseif (empty($compare[1]) && !empty($compare[0])) {
            return $direction == LdapQuery::ORDER['ASC'] ? -1 : 1;
        } else {
            return $this->compare(...$compare);
        }
    }

    /**
     * Taking into account case-sensitivity compare the 2 string values.
     *
     * @param string $value1
     * @param string $value2
     * @return int
     */
    protected function compare($value1, $value2)
    {
        if (!$this->caseSensitive) {
            $value1 = MBString::strtolower($value1);
            $value2 = MBString::strtolower($value2);
        }

        return MBString::compare($value1, $value2);
    }

    /**
     * Determine how to get the value for the attribute from the LDAP entry being compared, and return that value.
     *
     * @param array|LdapObject $entry
     * @param string $attribute
     * @return mixed
     */
    protected function getComparisonValue($entry, $attribute)
    {
        $alias = null;
        if (!empty($this->aliases)) {
            list($alias, $attribute) = LdapUtilities::getAliasAndAttribute($attribute);
        }

        $value = '';
        if (is_array($entry) && isset($entry[$attribute])) {
            $value = $entry[$attribute];
        // Be forgiving if they are hydrating to an array and the case of the attribute was not correct.
        } elseif (is_array($entry) && array_key_exists(MBString::strtolower($attribute), MBString::array_change_key_case($entry))) {
            $value = MBString::array_change_key_case($entry)[MBString::strtolower($attribute)];
        // Only get the value if there is no alias requested, or if an alias was requested the object type must match the alias.
        } elseif (($entry instanceof LdapObject) && (!$alias || $entry->isType($this->aliases[$alias]->getObjectType())) && $entry->has($attribute)) {
            $value = $entry->get($attribute);
        }
        // How to handle multi-valued attributes? This will at least prevent errors, but may not be accurate.
        $value = is_array($value) ? reset($value) : $value;

        return $this->convertValueToString($value);
    }

    /**
     * Certain cases may require the value to be specifically changed to a string, as not every object has default
     * string representation. The most common wanting to be sorted would likely be a DateTime object.
     *
     * @param mixed $value
     * @return string
     */
    protected function convertValueToString($value)
    {
        if ($value instanceof \DateTime) {
            $value = $value->getTimestamp();
        }

        return (string) $value;
    }
}
