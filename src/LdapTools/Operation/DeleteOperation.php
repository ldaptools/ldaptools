<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Operation;

/**
 * Represents an operation to remove an existing LDAP object.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class DeleteOperation implements LdapOperationInterface
{
    /**
     * @var string The DN to remove.
     */
    protected $dn;

    /**
     * Get the distinguished name to be deleted by this operation.
     *
     * @return null|string
     */
    public function getDn()
    {
        return $this->dn;
    }

    /**
     * Set the distinguished name to be deleted by this operation.
     *
     * @param string $dn
     * @return $this
     */
    public function setDn($dn)
    {
        $this->dn = $dn;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments()
    {
        return [$this->dn];
    }

    /**
     * {@inheritdoc}
     */
    public function getLdapFunction()
    {
        return 'ldap_delete';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Delete';
    }

    /**
     * {@inheritdoc}
     */
    public function getLogArray()
    {
        return [
            'DN' => $this->dn,
        ];
    }
}
