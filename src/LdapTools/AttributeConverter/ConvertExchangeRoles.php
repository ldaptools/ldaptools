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

use LdapTools\Enums\Exchange\ServerRole;

/**
 * Converts the current roles of an Exchange server using a bitwise and operation to get a readable name.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertExchangeRoles implements AttributeConverterInterface
{
    use AttributeConverterTrait;
    
    /**
     * {@inheritdoc}
     */
    public function fromLdap($value)
    {
        return (new ServerRole($value))->getNames();
    }

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
        return $value;
    }
}
