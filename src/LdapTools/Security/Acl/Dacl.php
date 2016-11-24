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

use LdapTools\Security\Ace\Ace;

/**
 * Represents a DACL structure.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class Dacl extends Acl
{
    /**
     * The character that represents this ACL type in the SDDL string.
     */
    const SDDL_CHAR = 'D';

    /**
     * The allowed ACE type.
     */
    const ALLOWED_TYPE = 'ACCESS';

    /**
     * {@inheritdoc}
     */
    public function getSddlIdentifier()
    {
        return self::SDDL_CHAR;
    }

    /**
     * Check if the ACL is in canonical form.
     *
     * @return bool
     */
    public function isCanonical()
    {
        return $this->aces === $this->orderAcesCanonically();
    }

    /**
     * Forces the ACEs into canonical form. You can check if it is not in canonical form with the isCanonical method.
     *
     * @return $this
     */
    public function canonicalize()
    {
        $this->aces = $this->orderAcesCanonically();

        return $this;
    }

    /**
     * Get the binary string representation of the ACL.
     *
     * @param bool $canonicalize
     * @return string
     */
    public function toBinary($canonicalize = true)
    {
        if ($canonicalize) {
            $this->canonicalize();
        }

        return parent::toBinary();
    }

    /**
     * Get the SDDL string representation of the ACL.
     *
     * @param bool $canonicalize Whether or not to re-order the ACEs to be canonical before the operation.
     * @return string
     */
    public function toSddl($canonicalize = true)
    {
        if ($canonicalize) {
            $this->canonicalize();
        }

        return parent::toSddl();
    }

    /**
     * {@inheritdoc}
     */
    protected function getAllowedAceType()
    {
        return self::ALLOWED_TYPE;
    }

    /**
     * Returns all ACEs in canonical order as expected by AD.
     *
     * @return Ace[]
     */
    protected function orderAcesCanonically()
    {
        $explicitDeny = [];
        $explicitAllow = [];
        $objectDeny = [];
        $objectAllow = [];
        $inherited = [];

        foreach ($this->aces as $ace) {
            $isDenied = $ace->isDenyAce();
            $isObject = $ace->isObjectAce();

            if ($ace->getFlags()->isInherited()) {
                $inherited[] = $ace;
            } elseif ($isDenied && $isObject) {
                $objectDeny[] = $ace;
            } elseif ($isDenied) {
                $explicitDeny[] = $ace;
            } elseif ($isObject) {
                $objectAllow[] = $ace;
            } else {
                $explicitAllow[] = $ace;
            }
        }

        return array_merge(
            $objectDeny,
            $explicitDeny,
            $objectAllow,
            $explicitAllow,
            $inherited
        );
    }
}
