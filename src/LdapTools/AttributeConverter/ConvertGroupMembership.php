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

use LdapTools\BatchModify\Batch;
use LdapTools\BatchModify\BatchCollection;
use LdapTools\Operation\AddOperation;
use LdapTools\Operation\BatchModifyOperation;

/**
 * Allows the back-linked group memberships for an LDAP entry to be modified in a more intuitive way by converting the
 * back-linked value changes to LDAP operations to add/remove them from the entry. This also extends the normal
 * value to DN converter to allow for proper conversion for searches.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertGroupMembership extends ConvertValueToDn implements OperationGeneratorInterface
{
    use OperationGeneratorTrait;

    /**
     * {@inheritdoc}
     */
    public function toLdap($values)
    {
        $this->validateCurrentAttribute($this->options);

        if ($this->getOperationType() == AttributeConverterInterface::TYPE_SEARCH_TO) {
            $values = $this->getDnFromValue($values);
        } else {
            $this->createOperationsFromValues($values);
        }
        
        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function getIsMultiValuedConverter()
    {
        return $this->getRemoveOriginalValue();
    }

    /**
     * {@inheritdoc}
     */
    public function getRemoveOriginalValue()
    {
        return $this->getOperationType() == AttributeConverterInterface::TYPE_CREATE
            || $this->getOperationType() == AttributeConverterInterface::TYPE_MODIFY;
    }
    
    /**
     * Given the set of array values create the correct operation.
     *
     * @param array $values
     * @throws \LdapTools\Exception\AttributeConverterException
     */
    protected function createOperationsFromValues(array $values)
    {
        // In the case of a 'set' or 'reset' operation all current group membership should be removed.
        if ($this->shouldRemoveCurrentGroups()) {
            $this->removeCurrentGroups();
        }
        // Only if this is not a reset operation, otherwise there is nothing left to do.
        if (!($this->getOperationType() == AttributeConverterInterface::TYPE_MODIFY && $this->getBatch()->isTypeRemoveAll())) {
            $batchType = $this->getBatchTypeForOperation();
            foreach ($values as $value) {
                $this->addOperation($this->getDnFromValue($value), $batchType);
            }
        }
    }

    /**
     * Add the correct operation for the action as a post operation to the current operation.
     *
     * @param string $dn
     * @param int $batchType
     * @throws \LdapTools\Exception\AttributeConverterException
     */
    protected function addOperation($dn, $batchType)
    {
        $collection = new BatchCollection($dn);

        $valueDn = $this->getDn();
        // The DN is unknown in the case of an add, as value/parameter resolution most occur first. If there is a better
        // way to do this I'm not sure what it would be. The batch will resolve closures when producing an array to ldap.
        if ($this->getOperationType() == AttributeConverterInterface::TYPE_CREATE) {
            /** @var AddOperation $parentOp */
            $parentOp = $this->getOperation();
            $valueDn = function () use ($parentOp) {
                return $parentOp->getDn();
            };
        }

        $collection->add(new Batch($batchType, $this->getOptionsArray()['to_attribute'], $valueDn));
        $operation = new BatchModifyOperation($dn, $collection);
        $this->operation->addPostOperation($operation);
    }

    /**
     * @return bool
     */
    protected function shouldRemoveCurrentGroups()
    {
        return $this->getOperationType() == self::TYPE_MODIFY
            && ($this->getBatch()->isTypeRemoveAll() || $this->getBatch()->isTypeReplace());
    }

    /**
     * Gets the current group membership and generates operations to remove them all.
     *
     * @throws \LdapTools\Exception\AttributeConverterException
     */
    protected function removeCurrentGroups()
    {
        $valuesToRemove = $this->getCurrentLdapAttributeValue($this->getOptionsArray()['from_attribute']);
        $valuesToRemove = is_null($valuesToRemove) ? [] : $valuesToRemove;
        $valuesToRemove = is_array($valuesToRemove)  ? $valuesToRemove : [$valuesToRemove];

        foreach ($valuesToRemove as $value) {
            $this->addOperation($this->getDnFromValue($value), Batch::TYPE['REMOVE']);
        }
    }

    /**
     * Get the batch type for the operation that was specified.
     *
     * @return int
     */
    protected function getBatchTypeForOperation()
    {
        // If it was a batch reset we wouldn't be this far. So only check for a remove. If it isn't a remove then the
        // only other action it could be is an add (creating a new LDAP entry, adding to existing, or setting existing)
        if ($this->getOperationType() == AttributeConverterInterface::TYPE_MODIFY && $this->getBatch()->isTypeRemove()) {
            $batchType = Batch::TYPE['REMOVE'];
        } else {
            $batchType = Batch::TYPE['ADD'];
        }
        
        return $batchType;
    }
}
