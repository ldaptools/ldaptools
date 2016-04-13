<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Event;

use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Operation\LdapOperationInterface;

/**
 * Represents an Event for a LDAP operation.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapOperationEvent extends Event
{
    /**
     * @var LdapOperationInterface
     */
    protected $operation;

    /**
     * @var LdapConnectionInterface
     */
    protected $connection;

    /**
     * @param string $name
     * @param LdapOperationInterface $operation
     * @param LdapConnectionInterface $connection
     */
    public function __construct($name, LdapOperationInterface $operation, LdapConnectionInterface $connection)
    {
        $this->operation = $operation;
        $this->connection = $connection;
        parent::__construct($name);
    }

    /**
     * Get the LDAP operation being executed.
     *
     * @return LdapOperationInterface
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * Get the LDAP connection that is executing the operation.
     *
     * @return LdapConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }
}
