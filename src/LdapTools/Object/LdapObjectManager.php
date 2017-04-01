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
use LdapTools\Event\LdapObjectRestoreEvent;
use LdapTools\Exception\Exception;
use LdapTools\Exception\InvalidArgumentException;
use LdapTools\Factory\LdapObjectSchemaFactory;
use LdapTools\Hydrator\OperationHydrator;
use LdapTools\Operation\BatchModifyOperation;
use LdapTools\Operation\DeleteOperation;
use LdapTools\Operation\LdapOperationInterface;
use LdapTools\Operation\RenameOperation;
use LdapTools\Query\LdapQueryBuilder;
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
        $this->executeBatchOperation($ldapObject);

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
     * Restore a deleted LDAP object. Optionally pass the new location container/OU for the object. If a new location
     * is not provided it will use the lastKnownParent value to determine where it should go.
     *
     * This may require a strategy design at some point, as this is AD specific currently. Unsure as to how other
     * directory services handle deleted object restores. The basic logic for AD to do this is...
     *
     * 1. Reset the 'isDeleted' attribute.
     * 2. Set the DN so the object ends up in a location other than the "Deleted Objects" container.
     *
     * @param LdapObject $ldapObject
     * @param null|string $location The DN of a container/OU where the restored object should go.
     */
    public function restore(LdapObject $ldapObject, $location = null)
    {
        $event = new LdapObjectRestoreEvent(Event::LDAP_OBJECT_BEFORE_RESTORE, $ldapObject, $location);
        $this->dispatcher->dispatch($event);
        $location = $event->getContainer();

        $this->validateObject($ldapObject);
        $originalDn = $ldapObject->get('dn');
        $ldapObject->reset('isDeleted');
        // Some additional logic may be needed to get the actual restore location...
        $newLocation = $this->getObjectRestoreLocation($ldapObject, $location);
        // The DN contains the full RDN (including the preceding attribute name). The original RDN is before the \0A.
        $rdn = explode('\0A', $ldapObject->get('dn'), 2)[0];
        $ldapObject->set('dn', "$rdn,$newLocation");
        $this->executeBatchOperation($ldapObject, $originalDn);

        $this->dispatcher->dispatch(new LdapObjectRestoreEvent(Event::LDAP_OBJECT_AFTER_RESTORE, $ldapObject, $location));
    }

    /**
     * @param LdapObject $ldapObject
     * @param string|null $dn The DN to use for the batch operation to LDAP.
     */
    protected function executeBatchOperation(LdapObject $ldapObject, $dn = null)
    {
        $dn = $dn ?: $ldapObject->get('dn');
        
        $operation = new BatchModifyOperation($dn, $ldapObject->getBatchCollection());
        $this->hydrateOperation($operation, $ldapObject->getType());
        $this->connection->execute($operation);
        $this->refreshDnIfNeeded($operation, $ldapObject);

        $ldapObject->setBatchCollection(new BatchCollection($ldapObject->get('dn')));
    }
    
    /**
     * It's possible a new location was not explicitly given and the attribute that contains the last know location
     * was not queried for when the object was originally found. In that case attempt to retrieve the last known
     * location from a separate LDAP query.
     *
     * @param LdapObject $ldapObject
     * @param string|null $location
     * @return string
     */
    protected function getObjectRestoreLocation(LdapObject $ldapObject, $location)
    {
        // If a location was defined, use that.
        if ($location) {
            $newLocation = $location;
        // Check the attribute for the last known location first...
        } elseif ($ldapObject->has('lastKnownLocation')) {
            $newLocation = $ldapObject->get('lastKnownLocation');
        // All else failed, so query it from the DN...
        } else {
            try {
                $newLocation = (new LdapQueryBuilder($this->connection, $this->schemaFactory))
                    ->select('lastKnownParent')
                    ->from(LdapObjectType::DELETED)
                    ->where(['dn' => $ldapObject->get('dn')])
                    ->getLdapQuery()
                    ->getSingleScalarOrNullResult();
            } catch (Exception $e) {
                $newLocation = null;
            }
        }

        // Either this was not a deleted object or it no longer exists?
        if (is_null($newLocation)) {
            throw new InvalidArgumentException(sprintf(
                'No restore location specified and cannot find the last known location for "%s".',
                $ldapObject->get('dn')
            ));
        }

        return $newLocation;
    }

    /**
     * The DN attribute must be present to perform LDAP operations.
     *
     * @param LdapObject $ldapObject
     */
    protected function validateObject(LdapObject $ldapObject)
    {
        if (!$ldapObject->has('dn')) {
            throw new InvalidArgumentException('To persist/delete/move/restore a LDAP object it must have the DN attribute.');
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
        $this->hydrator->setLdapObjectSchema($type ? $this->schemaFactory->get($this->connection->getConfig()->getSchemaName(), $type) : null);
        $this->hydrator->hydrateToLdap($operation);
        $this->hydrator->setLdapObjectSchema(null);
    }

    /**
     * Based on the operation type, refresh the DN if needed. The order is important here. Older 'pre' operations are
     * refreshed first, then the operation itself, and finally any 'post' operations.
     *
     * @param LdapOperationInterface $operation
     * @param LdapObject $ldapObject
     */
    protected function refreshDnIfNeeded(LdapOperationInterface $operation, LdapObject $ldapObject)
    {
        foreach ($operation->getPreOperations() as $childOp) {
            $this->refreshDnIfNeeded($childOp, $ldapObject);
        }

        if ($operation instanceof RenameOperation) {
            $ldapObject->refresh(['dn' => $this->getNewDnFromOperation($operation, $ldapObject)]);
        }

        foreach ($operation->getPostOperations() as $childOp) {
            $this->refreshDnIfNeeded($childOp, $ldapObject);
        }
    }

    /**
     * @param RenameOperation $operation
     * @param LdapObject $ldapObject
     * @return string
     */
    protected function getNewDnFromOperation(RenameOperation $operation, LdapObject $ldapObject)
    {
        $rdn = $operation->getNewRdn() ?: LdapUtilities::getRdnFromDn($ldapObject->get('dn'));
        $parentDn = $operation->getNewLocation() ?: LdapUtilities::getParentDn($ldapObject->get('dn'));

        return $rdn.','.$parentDn;
    }
}
