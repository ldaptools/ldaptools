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
use LdapTools\Security\SID;
use LdapTools\Utilities\LdapUtilities;

/**
 * Converts an objectSid between binary form and the friendly string form.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertWindowsSid implements AttributeConverterInterface
{
    use AttributeConverterTrait;

    /**
     * {@inheritdoc}
     */
    public function toLdap($sid)
    {
        if (!LdapUtilities::isValidSid($sid)) {
            throw new AttributeConverterException(sprintf(
                'Expected a string SID but got "%s".',
                $sid
            ));
        }
        $sid = (new SID($sid))->toBinary();

        if ($this->getOperationType() == self::TYPE_SEARCH_TO) {
            // All hex parts must have a leading backslash for the search.
            $sid = '\\'.implode('\\', str_split(bin2hex($sid), '2'));
        }

        return $sid;
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($value)
    {
        return (new SID($value))->toString();
    }
}
