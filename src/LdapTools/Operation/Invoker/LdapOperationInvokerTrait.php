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
use LdapTools\Log\LogOperation;
use LdapTools\Operation\Handler\OperationHandlerInterface;

/**
 * Implements common functions/properties for the LdapOperationInvokerInterface.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
trait LdapOperationInvokerTrait
{
    /**
     * @var LdapConnectionInterface
     */
    protected $connection;

    /**
     * @var LdapLoggerInterface
     */
    protected $logger;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var OperationHandlerInterface[]
     */
    protected $handler = [];

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
     * @param LdapLoggerInterface $logger
     */
    public function setLogger(LdapLoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * @param CacheInterface $cache
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param OperationHandlerInterface $handler
     */
    public function addHandler(OperationHandlerInterface $handler)
    {
        array_unshift($this->handler, $handler);
    }

    /**
     * Handles exception error message logging if logging is enabled then re-throws the exception.
     *
     * @param LogOperation|null $log
     * @param \Throwable|\Exception $exception
     * @throws \LdapTools\Exception\LdapConnectionException
     * @throws null
     */
    protected function logExceptionAndThrow($exception, LogOperation $log = null)
    {
        if ($this->shouldLog($log) && is_null($log->getStartTime())) {
            $this->logStart($log);
        }
        if ($this->shouldLog($log)) {
            $log->setError($exception->getMessage());
        }

        throw $exception;
    }

    /**
     * Start a logging operation.
     *
     * @param LogOperation|null $log
     */
    protected function logStart(LogOperation $log = null)
    {
        if ($this->shouldLog($log)) {
            $this->logger->start($log->start());
        }
    }

    /**
     * End a logging operation.
     *
     * @param LogOperation|null $log
     */
    protected function logEnd(LogOperation $log = null)
    {
        if ($this->shouldLog($log)) {
            $this->logger->end($log->stop());
        }
    }

    /**
     * Determine whether logging should be used.
     *
     * @param LogOperation|null $log
     * @return bool
     */
    protected function shouldLog(LogOperation $log = null)
    {
        return !(is_null($log) || is_null($this->logger));
    }
}
