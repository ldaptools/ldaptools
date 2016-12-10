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
use LdapTools\Security\SddlParser;
use LdapTools\Security\SecurityDescriptor;

/**
 * Converts SDDL or a Security Descriptor object to binary, and from binary to a Security Descriptor object.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertWindowsSecurity implements AttributeConverterInterface
{
    use AttributeConverterTrait;

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
        if ($value instanceof SecurityDescriptor) {
            $value = $value->toBinary();
        } elseif (is_string($value) && preg_match(SddlParser::MATCH_SDDL, $value)) {
            $value = (new SddlParser())->parse($value)->toBinary();
        } else {
            throw new AttributeConverterException(sprintf(
                'You must provide either a SDDL string or SecurityDescriptor for %s',
                $this->getAttribute()
            ));
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($value)
    {
        return new SecurityDescriptor($value);
    }
}
