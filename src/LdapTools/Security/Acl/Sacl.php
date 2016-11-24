<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Security\Acl;

/**
 * Represents a SACL structure.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class Sacl extends Acl
{
    /**
     * The character that represents this ACL type in the SDDL string.
     */
    const SDDL_CHAR = 'S';

    /**
     * The allowed ACE type.
     */
    const ALLOWED_TYPE = 'SYSTEM';

    /**
     * {@inheritdoc}
     */
    public function getSddlIdentifier()
    {
        return self::SDDL_CHAR;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAllowedAceType()
    {
        return self::ALLOWED_TYPE;
    }
}
