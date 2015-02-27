<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\AttributeConverter;

/**
 * Intended to be used with attribute converters that utilize options and current attributes to do some of their work.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
trait ConverterUtilitiesTrait
{
    /**
     * {@inheritdoc}
     */
    abstract public function getAttribute();

    /**
     * If the current attribute does not exist in the array, then throw an error.
     *
     * @param $options
     */
    protected function validateCurrentAttribute(array $options)
    {
        if (!array_key_exists(strtolower($this->getAttribute()), array_change_key_case($options))) {
            throw new \RuntimeException(
                sprintf('You must first define "%s" in the options for this converter.', $this->getAttribute())
            );
        }
    }

    /**
     * Get the value of an array key in a case-insensitive way.
     *
     * @param array $options
     * @param string $key
     */
    protected function getArrayValue(array $options, $key)
    {
        return array_change_key_case($options)[strtolower($key)];
    }
}
