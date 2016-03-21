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
 * Used for a converter that generates additional operations based off the current attribute/value.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
interface OperationGeneratorInterface
{
    /**
     * Get the operation being executed.
     *
     * @return LdapOperationInterface|null
     */
    public function getOperation();
    
    /**
     * Set the current operation being executed.
     *
     * @param LdapOperationInterface $operation
     */
    public function setOperation(LdapOperationInterface  $operation = null);

    /**
     * Get whether the original value should be removed from the batch/array, leaving only the generated operation.
     *
     * @return bool
     */
    public function getRemoveOriginalValue();
}
