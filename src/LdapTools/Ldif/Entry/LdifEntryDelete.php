<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Ldif\Entry;

use LdapTools\Operation\DeleteOperation;

/**
 * Represents a LDIF entry to delete an object from LDAP.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdifEntryDelete implements LdifEntryInterface
{
    use LdifEntryTrait;

    /**
     * @param string $dn
     */
    public function __construct($dn)
    {
        $this->dn = $dn;
        $this->changeType = self::TYPE_DELETE;
    }

    /**
     * {@inheritdoc}
     */
    public function toString()
    {
        return $this->getCommonString();
    }

    /**
     * {@inheritdoc}
     */
    public function toOperation()
    {
        return new DeleteOperation($this->dn);
    }
}
