<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Utilities;

use LdapTools\Exception\InvalidArgumentException;

/**
 * Some utility functions to handle multi-byte strings properly, as support is lacking/inconsistent for most PHP string
 * functions. This provides a wrapper for various workarounds and falls back to normal functions if needed.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class MBString
{
    /**
     * @var null|bool
     */
    protected static $isMbStringLoaded;

    /**
     * @var null|bool
     */
    protected static $isIntlLoaded;

    /**
     * @var null|\Collator
     */
    protected static $collator;

    /**
     * Get the integer value of a specific character.
     *
     * @param $string
     * @return int
     */
    public static function ord($string)
    {
        if (self::isMbstringLoaded()) {
            $result = unpack('N', mb_convert_encoding($string, 'UCS-4BE', 'UTF-8'));
            if (is_array($result) === true) {
                return $result[1];
            }
        }

        return ord($string);
    }

    /**
     * Get the character for a specific integer value.
     *
     * @param $int
     * @return string
     */
    public static function chr($int)
    {
        if (self::isMbstringLoaded()) {
            return mb_convert_encoding(pack('n', $int), 'UTF-8', 'UTF-16BE');
        }

        return chr($int);
    }

    /**
     * Split a string into its individual characters and return it as an array.
     *
     * @param string $value
     * @return string[]
     */
    public static function str_split($value)
    {
        return preg_split('/(?<!^)(?!$)/u', $value);
    }

    /**
     * Performs a comparison between two values and returns an integer result, like strnatcmp.
     *
     * @param string $value1
     * @param string $value2
     * @return int
     */
    public static function compare($value1, $value2)
    {
        if (self::isIntlLoaded()) {
            return self::getCollator()->compare($value1, $value2);
        }
        
        return strnatcmp($value1, $value2);
    }

    /**
     * Make a string lower case.
     *
     * @param string $value
     * @return string
     */
    public static function strtolower($value)
    {
        if (self::isMbstringLoaded()) {
            return mb_strtolower($value, 'UTF-8');
        }

        return strtolower($value);
    }

    /**
     * Change the keys in an array to their lower-case values.
     *
     * @param array $values
     * @return array
     */
    public static function array_change_key_case(array $values)
    {
        if (self::isMbstringLoaded()) {
            $newValues = [];

            foreach ($values as $key => $value) {
                $newValues[self::strtolower($key)] = $value;
            }

            return $newValues;
        }

        return array_change_key_case($values);
    }

    /**
     * Change the values in an array to lower-case values.
     *
     * @param array $values
     * @return array
     */
    public static function array_change_value_case(array $values)
    {
        $newValues = [];

        foreach ($values as $key => $value) {
            $newValues[$key] = self::strtolower($value);
        }

        return $newValues;
    }

    /**
     * Given an array value determine if it exists in the array and return the value as it is in the array.
     *
     * @param string $needle
     * @param array $haystack
     * @return string
     */
    public static function array_search_get_value($needle, array $haystack)
    {
        $lcNeedle = self::strtolower($needle);

        foreach ($haystack as $value) {
            if ($lcNeedle == self::strtolower($value)) {
                return $value;
            }
        }

        throw new InvalidArgumentException(sprintf('Value "%s" not found in array.', $needle));
    }

    /**
     * Simple check for the mbstring extension.
     *
     * @return bool
     */
    protected static function isMbstringLoaded()
    {
        if (self::$isMbStringLoaded === null) {
            self::$isMbStringLoaded = extension_loaded('mbstring');
        }

        return self::$isMbStringLoaded;
    }

    /**
     * Simple check for the intl extension.
     *
     * @return bool
     */
    protected static function isIntlLoaded()
    {
        if (self::$isIntlLoaded === null) {
            self::$isIntlLoaded = extension_loaded('intl');
        }

        return self::$isIntlLoaded;
    }

    /**
     * Load and return a collator instance.
     *
     * @return \Collator
     */
    protected static function getCollator()
    {
        if (!self::$collator) {
            self::$collator = collator_create('root');
        }
        
        return self::$collator;
    }
}
