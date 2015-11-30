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
 * The operation handler interface.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
interface OperationHandlerInterface
{
    /**
     * Set the LDAP connection to be used for the operation handler.
     *
     * @param LdapConnectionInterface $connection
     */
    public function setConnection(LdapConnectionInterface $connection);

    /**
     * Set the event dispatcher to be used for the operation handler.
     *
     * @param EventDispatcherInterface $dispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $dispatcher);

    /**
     * Used to set any operation default values before the operation is started/executed.
     *
     * @param LdapOperationInterface $operation
     */
    public function setOperationDefaults(LdapOperationInterface $operation);

    /**
     * Handle a given LDAP operation and return a response.
     *
     * @param LdapOperationInterface $operation
     * @return mixed
     */
    public function execute(LdapOperationInterface $operation);

    /**
     * Given a LDAP operation return whether or not it is supported by the handler.
     *
     * @param LdapOperationInterface $operation
     * @return bool
     */
    public function supports(LdapOperationInterface $operation);
}
