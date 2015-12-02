<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Operation\Handler;

use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Operation\LdapOperationInterface;
use LdapTools\Event\EventDispatcherInterface;

/**
 * Common functions/properties for an operation handler.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
trait OperationHandlerTrait
{
    /**
     * @var LdapConnectionInterface
     */
    protected $connection;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @param LdapConnectionInterface $connection
     */
    public function setConnection(LdapConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param LdapOperationInterface $operation
     */
    public function setOperationDefaults(LdapOperationInterface $operation)
    {
        if (is_null($operation->getServer())) {
            $operation->setServer($this->connection->getServer());
        }
    }
}
