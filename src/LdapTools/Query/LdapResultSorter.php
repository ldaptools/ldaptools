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
     * @param array $orderBy
     */
    public function __construct(array $orderBy)
    {
        $this->orderBy = $orderBy;
    }

    /**
     * Reorganize the array to the desired orderBy methods passed to the class.
     *
     * @param array $results The unsorted result set.
     * @return array The sorted result set.
     */
    public function sort(array $results)
    {
        usort($results, array($this, 'resultSortCallback'));

        return $results;
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
            if ($retVal == 0) {
                $retVal = $this->getUsortReturnValue($attribute, $direction, $a, $b);
            }
        }

        return $retVal;
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
            return strnatcmp(...$compare);
        }
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
        $value = '';
        if (is_array($entry) && isset($entry[$attribute])) {
            $value = $entry[$attribute];
        // Be forgiving if they are hydrating to an array and the case of the attribute was not correct.
        } elseif (is_array($entry) && array_key_exists(strtolower($attribute), array_change_key_case($entry))) {
            $value = array_change_key_case($entry)[strtolower($attribute)];
        } elseif (($entry instanceof LdapObject) && $entry->has($attribute)) {
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
