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
 * Represents an operation to batch modify attribute values on an existing LDAP object .
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class BatchModifyOperation implements LdapOperationInterface
{
    use LdapOperationTrait;

    /**
     * @var array
     */
    protected $properties = [
        'dn' => null,
        'batch' => null,
    ];

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
     * The batch modifications array for a modify operation.
     *
     * @return array|null
     */
    public function getBatch()
    {
        return $this->properties['batch'];
    }

    /**
     * Set the batch modifications array for the operation.
     *
     * @param array $batch
     * @return $this
     */
    public function setBatch(array $batch)
    {
        $this->properties['batch'] = $batch;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLdapFunction()
    {
        return 'ldap_modify_batch';
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments()
    {
        return [
            $this->properties['dn'],
            $this->properties['batch'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Batch Modify';
    }

    /**
     * {@inheritdoc}
     */
    public function getLogArray()
    {
        $batch = is_array($this->properties['batch']) ? LdapUtilities::maskBatchArray($this->properties['batch']) : $this->properties['batch'];

        return $this->mergeLogDefaults([
            'DN' => $this->properties['dn'],
            'Batch' => print_r($batch, true),
        ]);
    }
}
