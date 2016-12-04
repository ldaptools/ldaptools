<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Operation\Invoker;

use LdapTools\Cache\CacheInterface;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Event\EventDispatcherInterface;
use LdapTools\Log\LdapLoggerInterface;
use LdapTools\Operation\Handler\OperationHandlerInterface;
use LdapTools\Operation\LdapOperationInterface;

/**
 *
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
interface LdapOperationInvokerInterface
{
    /**
     * @param LdapConnectionInterface $connection
     */
    public function setConnection(LdapConnectionInterface $connection);

    /**
     * @param LdapLoggerInterface $logger
     */
    public function setLogger(LdapLoggerInterface $logger);

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $dispatcher);

    /**
     * @param CacheInterface $cache
     */
    public function setCache(CacheInterface $cache);

    /**
     * @param LdapOperationInterface $operation
     * @return mixed
     */
    public function execute(LdapOperationInterface $operation);

    /**
     * @param OperationHandlerInterface $handler
     */
    public function addHandler(OperationHandlerInterface $handler);
}
