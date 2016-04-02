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

use LdapTools\Connection\AD\ADFunctionalLevelType;

/**
 * Converts the AD domain/forest functional level to a human readable form.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertFunctionalLevel implements AttributeConverterInterface
{
    use AttributeConverterTrait;

    /**
     * {@inheritdoc}
     */
    public function fromLdap($value)
    {
        if (array_key_exists($value, ADFunctionalLevelType::TYPES)) {
            $value = ADFunctionalLevelType::TYPES[$value];
        } else {
            $value = 'Unknown';
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
        return $value;
    }
}
