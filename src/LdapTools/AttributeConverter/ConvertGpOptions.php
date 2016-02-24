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
 * Converts the gpOptions attribute value to a boolean value.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertGpOptions implements AttributeConverterInterface
{
    use AttributeConverterTrait;

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
        return (bool) $value ? '1' : '0';
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($value)
    {
        return $value == '1' ? true : false;
    }
}
