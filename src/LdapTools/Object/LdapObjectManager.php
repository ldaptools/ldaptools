<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Object;

use LdapTools\AttributeConverter\AttributeConverterInterface;
use LdapTools\BatchModify\BatchCollection;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Connection\LdapControl;
use LdapTools\Connection\LdapControlType;
use LdapTools\Event\Event;
use LdapTools\Event\EventDispatcherInterface;
use LdapTools\Event\LdapObjectEvent;
use LdapTools\Event\LdapObjectMoveEvent;
use LdapTools\Exception\InvalidArgumentException;
use LdapTools\Factory\LdapObjectSchemaFactory;
use LdapTools\Hydrator\OperationHydrator;
use LdapTools\Operation\BatchModifyOperation;
use LdapTools\Operation\DeleteOperation;
use LdapTools\Operation\RenameOperation;
use LdapTools\Utilities\LdapUtilities;

/**
 * Handles updates and deletes to LDAP based off passed object data.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapObjectManager
{
    /**
     * @var LdapConnectionInterface
     */
    protected $connection;

    /**
     * @var LdapObjectSchemaFactory
     */
    protected $schemaFactory;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @param LdapConnectionInterface $connection
     * @param LdapObjectSchemaFactory $schemaFactory
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(LdapConnectionInterface $connection, LdapObjectSchemaFactory $schemaFactory, EventDispatcherInterface $dispatcher)
    {
        $this->hydrator = new OperationHydrator($connection);
        $this->schemaFactory = $schemaFactory;
        $this->connection = $connection;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Updates an object in LDAP. It will only update attributes that actually changed on the object.
     *
     * @param LdapObject $ldapObject
     */
    public function persist(LdapObject $ldapObject)
    {
        if (empty($ldapObject->getBatchCollection()->toArray())) {
            return;
        }
        $this->dispatcher->dispatch(new LdapObjectEvent(Event::LDAP_OBJECT_BEFORE_MODIFY, $ldapObject));

        $this->validateObject($ldapObject);
        $operation = new BatchModifyOperation($ldapObject->get('dn'), $ldapObject->getBatchCollection());
        $this->hydrateOperation($operation, $ldapObject->getType());
        $this->connection->execute($operation);
        $ldapObject->setBatchCollection(new BatchCollection($ldapObject->get('dn')));

        $this->dispatcher->dispatch(new LdapObjectEvent(Event::LDAP_OBJECT_AFTER_MODIFY, $ldapObject));
    }

    /**
     * Removes an object from LDAP.
     *
     * @param LdapObject $ldapObject
     * @param bool $recursively
     */
    public function delete(LdapObject $ldapObject, $recursively = false)
    {
        $this->dispatcher->dispatch(new LdapObjectEvent(Event::LDAP_OBJECT_BEFORE_DELETE, $ldapObject));
        $this->validateObject($ldapObject);

        $operation = new DeleteOperation($ldapObject->get('dn'));
        if ($recursively) {
            $operation->addControl((new LdapControl(LdapControlType::SUB_TREE_DELETE))->setCriticality(true));
        }

        $this->connection->execute($operation);
        $this->dispatcher->dispatch(new LdapObjectEvent(Event::LDAP_OBJECT_AFTER_DELETE, $ldapObject));
    }

    /**
     * Moves an object from one container/OU to another in LDAP.
     *
     * @param LdapObject $ldapObject
     * @param string $container
     */
    public function move(LdapObject $ldapObject, $container)
    {
        $event = new LdapObjectMoveEvent(Event::LDAP_OBJECT_BEFORE_MOVE, $ldapObject, $container);
        $this->dispatcher->dispatch($event);
        $container = $event->getContainer();

        $this->validateObject($ldapObject);
        $operation = new RenameOperation(
            $ldapObject->get('dn'),
            LdapUtilities::getRdnFromDn($ldapObject->get('dn')),
            $container,
            true
        );
        $this->connection->execute($operation);

        // Update the object to reference the new DN after the move...
        $newDn = LdapUtilities::getRdnFromDn($ldapObject->get('dn')).','.$container;
        $ldapObject->refresh(['dn' => $newDn]);
        $ldapObject->getBatchCollection()->setDn($newDn);

        $this->dispatcher->dispatch(new LdapObjectMoveEvent(Event::LDAP_OBJECT_AFTER_MOVE, $ldapObject, $container));
    }

    /**
     * The DN attribute must be present to perform LDAP operations.
     *
     * @param LdapObject $ldapObject
     */
    protected function validateObject(LdapObject $ldapObject)
    {
        if (!$ldapObject->has('dn')) {
            throw new InvalidArgumentException('To persist/delete/move a LDAP object it must have the DN attribute.');
        }
    }

    /**
     * Get the batch modification array that ldap_modify_batch expects.
     *
     * @param BatchModifyOperation $operation
     * @param string $type
     */
    protected function hydrateOperation(BatchModifyOperation $operation, $type)
    {
        $this->hydrator->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        if ($type) {
            $this->hydrator->setLdapObjectSchema($this->schemaFactory->get($this->connection->getConfig()->getSchemaName(), $type));
        }
        $this->hydrator->hydrateToLdap($operation);
        if ($type) {
            $this->hydrator->setLdapObjectSchema(null);
        }
    }
}
