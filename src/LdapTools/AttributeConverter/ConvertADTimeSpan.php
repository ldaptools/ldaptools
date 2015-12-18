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
use LdapTools\Utilities\ADTimeSpan;

/**
 * Converts an ADTimeSpan object between the I8 format AD uses for storing time spans.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertADTimeSpan implements AttributeConverterInterface
{
    use AttributeConverterTrait;

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
        if (!($value instanceof ADTimeSpan)) {
            throw new AttributeConverterException(sprintf(
                'The time span format for "%s" should be an instance of "\LdapTools\Utilities\ADTimeSpan"',
                $this->getAttribute()
            ));
        }

        return $value->getLdapValue();
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($value)
    {
        return new ADTimeSpan($value);
    }
}
