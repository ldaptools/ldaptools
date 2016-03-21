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
        if ($this->getOperationType() == self::TYPE_MODIFY && $this->getBatch()->isTypeRemoveAll()) {
            $values = $this->getCurrentLdapAttributeValue($this->getOptionsArray()['from_attribute']);
            $values = is_null($values) ? [] : $values;
            $values = is_array($values)  ? $values : [$values];
        }

        foreach ($values as $value) {
            $this->addOperation($this->getDnFromValue($value));
        }
    }

    /**
     * Add the correct operation for the action as a post operation to the current operation.
     *
     * @param string $dn
     * @throws \LdapTools\Exception\AttributeConverterException
     */
    protected function addOperation($dn)
    {
        $collection = new BatchCollection($dn);
        $action = Batch::TYPE['ADD'];
        if ($this->getOperationType() == self::TYPE_MODIFY && ($this->getBatch()->isTypeRemove() || $this->getBatch()->isTypeRemoveAll())) {
            $action = Batch::TYPE['REMOVE'];
        }

        $valueDn = $this->getDn();
        // The DN is unknown in the case of an add, as value/parameter resolution most occur first. If there is a better
        // way to do this I'm not sure what it would be. The batch will resolve closures when producing an array to ldap.
        if ($this->getOperationType() == AttributeConverterInterface::TYPE_CREATE) {
            /** @var AddOperation $parentOp */
            $parentOp = $this->getOperation();
            $valueDn = function() use ($parentOp) {
                return $parentOp->getDn();
            };
        }

        $collection->add(new Batch($action, $this->getOptionsArray()['to_attribute'], $valueDn));
        $operation = new BatchModifyOperation($dn, $collection);
        $this->operation->addPostOperation($operation);
    }
}
