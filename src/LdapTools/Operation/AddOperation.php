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

use LdapTools\Utilities\LdapUtilities;

/**
 * Represents an operation to add an object to LDAP.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class AddOperation implements LdapOperationInterface
{
    use LdapOperationTrait;

    /**
     * @var array
     */
    protected $properties = [
        'dn' => null,
        'attributes' => [],
    ];

    /**
     * Get either: The attributes selected for a query operation. The attributes to be set for an add operation.
     *
     * @return array|null
     */
    public function getAttributes()
    {
        return $this->properties['attributes'];
    }

    /**
     * Set the attributes selected or added to/from LDAP (add or select operation).
     *
     * @param array $attributes
     * @return $this;
     */
    public function setAttributes(array $attributes)
    {
        $this->properties['attributes'] = $attributes;

        return $this;
    }

    /**
     * The distinguished name for an add, delete, or move operation.
     *
     * @return null|string
     */
    public function getDn()
    {
        return $this->properties['dn'];
    }

    /**
     * Set the distinguished name that the operation is working on.
     *
     * @param string $dn
     * @return $this
     */
    public function setDn($dn)
    {
        $this->properties['dn'] = $dn;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments()
    {
        return [$this->properties['dn'], $this->properties['attributes']];
    }

    /**
     * {@inheritdoc}
     */
    public function getLdapFunction()
    {
        return 'ldap_add';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Add';
    }

    /**
     * {@inheritdoc}
     */
    public function getLogArray()
    {
        return $this->mergeLogDefaults([
            'DN' => $this->properties['dn'],
            'Attributes' => print_r(LdapUtilities::maskAttributeArray($this->properties['attributes']), true),
        ]);
    }
}
