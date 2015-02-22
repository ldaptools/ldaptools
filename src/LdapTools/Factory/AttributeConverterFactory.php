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
        'bool' => '\LdapTools\AttributeConverter\ConvertBoolean',
        'generalized_time' => '\LdapTools\AttributeConverter\ConvertGeneralizedTime',
        'int' => '\LdapTools\AttributeConverter\ConvertInteger',
        'string_to_utf8' => '\LdapTools\AttributeConverter\ConvertStringToUtf8',
        'windows_guid' => '\LdapTools\AttributeConverter\ConvertWindowsGuid',
        'windows_sid' => '\LdapTools\AttributeConverter\ConvertWindowsSid',
        'windows_time' => '\LdapTools\AttributeConverter\ConvertWindowsTime',
        'windows_generalized_time' => '\LdapTools\AttributeConverter\ConvertWindowsGeneralizedTime',
        'encode_windows_password' => '\LdapTools\AttributeConverter\EncodeWindowsPassword',
    ];

    /**
     * Retrieve a registered attribute converter by name.
     *
     * @param $name
     * @return AttributeConverterInterface
     */
    public static function get($name)
    {
        if (!isset(self::$converterMap[$name])) {
            throw new \InvalidArgumentException(sprintf('Attribute converter "%s" is not valid.', $name));
        }

        return self::getInstanceOfConverter($name);
    }

    /**
     * Registers a converter so it can be retrieved by its name.
     *
     * @param string $name The actual name for the converter in the schema.
     * @param string $class The fully qualified class name (ie. '\Foo\Bar\Converter')
     */
    public static function register($name, $class)
    {
        if (isset(self::$converterMap[$name])) {
            throw new \InvalidArgumentException(
                sprintf('The attribute converter name "%s" is already registered.', $name)
            );
        }

        self::$converterMap[$name] = $class;
    }

    /**
     * Load a specific converter if needed and send it back.
     *
     * @param string $name
     * @return AttributeConverterInterface
     */
    protected static function getInstanceOfConverter($name)
    {
        try {
            $converter = new self::$converterMap[$name]();
        } catch (\Exception $e) {
            throw new \RuntimeException(
                sprintf('Unable to load attribute converter "%s": %s', $name, $e->getMessage())
            );
        }
        if (!($converter instanceof AttributeConverterInterface)) {
            throw new \RuntimeException(sprintf(
                'The attribute converter "%s" must implement \LdapTools\AttributeConverter\AttributeConverterInterface.',
                $name
            ));
        }

        return $converter;
    }
}
