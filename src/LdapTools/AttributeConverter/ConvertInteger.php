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
 * Converts an integer into its LDAP string representation, and back into an integer for PHP.
 *
 * @link https://tools.ietf.org/html/rfc4517#section-3.3.16
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertInteger implements AttributeConverterInterface
{
    use AttributeConverterTrait;

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
        return (string) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($value)
    {
        return (int) $value;
    }
}
