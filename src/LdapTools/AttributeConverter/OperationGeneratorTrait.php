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

use LdapTools\Operation\LdapOperationInterface;

/**
 * Contains the common methods and properties needed for the OperationGeneratorInterface.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
trait OperationGeneratorTrait
{
    /**
     * @var LdapOperationInterface|null
     */
    protected $operation;

    /**
     * @param LdapOperationInterface $operation
     */
    public function setOperation(LdapOperationInterface $operation = null)
    {
        $this->operation = $operation;
    }

    /**
     * @return LdapOperationInterface
     */
    public function getOperation()
    {
        return $this->operation;
    }
    
    /**
     * @return bool
     */
    public function getRemoveOriginalValue()
    {
        return true;
    }
}
