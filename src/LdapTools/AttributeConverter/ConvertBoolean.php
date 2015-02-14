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
 * Converts a boolean for an attribute into the LDAP defined 'TRUE' / 'FALSE', and into the PHP true and false.
 *
 * @link https://tools.ietf.org/html/rfc4517#section-3.3.3
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertBoolean implements AttributeConverterInterface
{
    use AttributeConverterTrait;

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
        return (bool) $value ? 'TRUE' : 'FALSE';
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($value)
    {
        return 'true' == strtolower($value) ? true : false;
    }
}
