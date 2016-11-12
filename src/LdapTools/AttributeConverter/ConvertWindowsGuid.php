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
use LdapTools\Security\GUID;
use LdapTools\Utilities\LdapUtilities;

/**
 * Converts a binary objectGuid to a string representation and also from it's string representation back to hex/binary
 * for LDAP. The back to hex/binary structure is slightly unusual due to the endianness required.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertWindowsGuid implements AttributeConverterInterface
{
    use AttributeConverterTrait;

    /**
     * Used to specify that a GUID should be randomly generated and passed to LDAP.
     */
    const AUTO = 'auto';

    /**
     * {@inheritdoc}
     */
    public function toLdap($guid)
    {
        $guid = strtolower($guid) === self::AUTO ? LdapUtilities::uuid4() : $guid;
        if (!LdapUtilities::isValidGuid($guid)) {
            throw new AttributeConverterException(sprintf(
               'The value "%s" is not a valid GUID.',
                $guid
            ));
        }

        $guid = (new GUID($guid))->toBinary();
        if ($this->getOperationType() == self::TYPE_SEARCH_TO) {
            $guid = implode('', preg_filter('/^/', '\\', str_split(bin2hex($guid), 2)));
        }

        return $guid;
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($guid)
    {
        return (new GUID($guid))->toString();
    }
}
