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

use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Factory\HydratorFactory;
use LdapTools\Factory\LdapObjectSchemaFactory;

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
     * @var HydratorFactory
     */
    protected $hydratorFactory;

    /**
     * @param LdapConnectionInterface $connection
     * @param LdapObjectSchemaFactory $schemaFactory
     */
    public function __construct(LdapConnectionInterface $connection, LdapObjectSchemaFactory $schemaFactory)
    {
        $this->hydratorFactory = new HydratorFactory();
        $this->schemaFactory = $schemaFactory;
        $this->connection = $connection;
    }

    /**
     * Updates an object in LDAP. It will only update attributes that actually changed on the object.
     *
     * @param LdapObject $ldapObject
     */
    public function persist(LdapObject $ldapObject)
    {
        $this->validateObject($ldapObject);

        $batch = $ldapObject->getBatchModifications();
        $hydrator = $this->hydratorFactory->get(HydratorFactory::TO_OBJECT);
        $schema = $ldapObject->getType();

        if ($schema) {
            $schema = $this->schemaFactory->get($this->connection->getSchemaName(), $schema);
            $hydrator->setLdapObjectSchemas($schema);
            $batch = $hydrator->hydrateBatchToLdap($ldapObject);
        }

        $this->connection->modifyBatch($ldapObject->get('dn'), $batch);
        $ldapObject->clearBatchModifications();
    }

    /**
     * Removes an object from LDAP.
     *
     * @param LdapObject $ldapObject
     */
    public function delete(LdapObject $ldapObject)
    {
        $this->validateObject($ldapObject);
        $this->connection->delete($ldapObject->get('dn'));
    }

    /**
     * The DN attribute must be present to perform LDAP operations.
     *
     * @param LdapObject $ldapObject
     */
    protected function validateObject(LdapObject $ldapObject)
    {
        if (!$ldapObject->hasAttribute('dn')) {
            throw new \InvalidArgumentException('To persist/delete a LDAP object it must have the DN attribute.');
        }
    }
}
