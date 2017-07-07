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

use LdapTools\Exception\AttributeConverterException;

/**
 * Based off the objectClass of an object, determine what LDAP Object schema type it is.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertLdapObjectType implements AttributeConverterInterface
{
    use AttributeConverterTrait;

    /**
     * @var array
     */
    protected $options = [
        'user' => [],
        'group' => [],
        'computer' => [],
        'contact' => [],
        'ou' => [],
    ];

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
        throw new AttributeConverterException('Converting the LDAP object to LDAP is not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($value)
    {
        $ldapType = ['Unknown'];
        $value = array_map('strtolower', $value);

        foreach ($this->options as $type => $classes) {
            if (array_map('strtolower', $classes) == $value) {
                $ldapType = [$type];
                break;
            }
        }

        return $ldapType;
    }
    
    public function isMultiValuedConverter()
    {
        return true;
    }
}
