<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Factory;

use LdapTools\AttributeConverter\AttributeConverterInterface;

/**
 * Registers and loads requested Attribute Converter objects.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class AttributeConverterFactory
{
    /**
     * @var array The default converter name to class mapping.
     */
    protected static $converterMap = [
        'convert_bool' => '\LdapTools\AttributeConverter\ConvertBoolean',
        'convert_generalized_time' => '\LdapTools\AttributeConverter\ConvertGeneralizedTime',
        'convert_int' => '\LdapTools\AttributeConverter\ConvertInteger',
        'convert_string_to_utf8' => '\LdapTools\AttributeConverter\ConvertStringToUtf8',
        'convert_windows_guid' => '\LdapTools\AttributeConverter\ConvertWindowsGuid',
        'convert_windows_sid' => '\LdapTools\AttributeConverter\ConvertWindowsSid',
        'convert_windows_time' => '\LdapTools\AttributeConverter\ConvertWindowsTime',
        'convert_windows_generalized_time' => '\LdapTools\AttributeConverter\ConvertWindowsGeneralizedTime',
        'encode_windows_password' => '\LdapTools\AttributeConverter\EncodeWindowsPassword',
    ];

    /**
     * @var array A set of converter names and objects that have already been instantiated.
     */
    protected static $converters = [];

    /**
     * Retrieve a registered attribute converter by name.
     *
     * @param $name
     * @return mixed
     */
    public static function get($name)
    {
        if (!isset(self::$converterMap[$name])) {
            throw new \InvalidArgumentException(sprintf('Attribute converter "%s" is not valid.', $name));
        }

        if (isset(self::$converters[$name])) {
            $converter = self::$converters[$name];
        } else {
            try {
                $converter = new self::$converterMap[$name]();
            } catch (\Exception $e) {
                throw new \RuntimeException(
                    sprintf('Unable to load attribute converter "%s": %s', $name, $e->getMessage())
                );
            }
            self::$converters[$name] = $converter;
        }

        return $converter;
    }

    /**
     * Registers a converter so it can be retrieved by its name.
     *
     * @param string $name
     * @param AttributeConverterInterface $converter
     */
    public static function register($name, AttributeConverterInterface $converter)
    {
        if (isset(self::$converters[$name])) {
            throw new \InvalidArgumentException(
                sprintf('The attribute converter name "%s" is already registered.', $name)
            );
        }

        self::$converterMap[$name] = get_class($converter);
        self::$converters[$name] = $converter;
    }
}
