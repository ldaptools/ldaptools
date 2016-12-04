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

use LdapTools\Cache\CacheItem;
use LdapTools\Event\Event;
use LdapTools\Event\LdapOperationEvent;
use LdapTools\Exception\CacheMissException;
use LdapTools\Exception\LdapConnectionException;
use LdapTools\Log\LogOperation;
use LdapTools\Operation\AuthenticationOperation;
use LdapTools\Operation\BatchModifyOperation;
use LdapTools\Operation\CacheableOperationInterface;
use LdapTools\Operation\Handler\AuthenticationOperationHandler;
use LdapTools\Operation\Handler\OperationHandler;
use LdapTools\Operation\Handler\OperationHandlerInterface;
use LdapTools\Operation\Handler\QueryOperationHandler;
use LdapTools\Operation\LdapOperationInterface;
use LdapTools\Utilities\MBString;

/**
 * Invokes the correct handler for a given operation, as well as handling logging.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapOperationInvoker implements LdapOperationInvokerInterface
{
    use LdapOperationInvokerTrait;

    public function __construct()
    {
        $this->addHandler(new OperationHandler());
        $this->addHandler(new QueryOperationHandler());
        $this->addHandler(new AuthenticationOperationHandler());
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LdapOperationInterface $operation)
    {
        $result = true;

        foreach ($operation->getPreOperations() as $preOperation) {
            $this->execute($preOperation);
        }

        if (!$this->shouldSkipOperation($operation)) {
            $this->dispatcher->dispatch(new LdapOperationEvent(Event::LDAP_OPERATION_EXECUTE_BEFORE, $operation, $this->connection));
            $result = $this->executeOperation($operation, $this->getLogObject($operation));
        }

        foreach ($operation->getPostOperations() as $postOperation) {
            $this->execute($postOperation);
        }

        return $result;
    }

    /**
     * Execute a given operation with an operation handler.
     *
     * @param LdapOperationInterface $operation
     * @param LogOperation|null $log
     * @return mixed
     * @throws \Throwable
     */
    protected function executeOperation(LdapOperationInterface $operation, LogOperation $log = null)
    {
        $lastServer = $this->connection->getServer();
        try {
            $this->connectIfNotBound($operation);
            $lastServer = $this->connection->getServer();
            $handler = $this->getOperationHandler($operation);
            $handler->setOperationDefaults($operation);
            $this->logStart($log);
            $this->invalidateCacheIfNeeded($operation);
            list($result, $usedCache, $cacheKey) = $this->getCacheOrHandlerResult($operation, $handler, $log);
            $this->cacheResultIfNeeded($operation, $result, $usedCache, $cacheKey);

            return $result;
        } catch (\Throwable $e) {
            $this->logExceptionAndThrow($e, $log);
        } catch (\Exception $e) {
            $this->logExceptionAndThrow($e, $log);
        } finally {
            $this->logEnd($log);
            // It would not have set controls or switched servers if the cache was used...
            if (isset($usedCache) && $usedCache === false) {
                $this->resetLdapControls($operation);
                $this->switchServerIfNeeded($this->connection->getServer(), $lastServer, $operation);
            }
            $this->dispatcher->dispatch(new LdapOperationEvent(Event::LDAP_OPERATION_EXECUTE_AFTER, $operation, $this->connection));
        }
    }

    /**
     * Construct the LogOperation object for the operation.
     *
     * @param LdapOperationInterface $operation
     * @return LogOperation|null
     */
    protected function getLogObject(LdapOperationInterface $operation)
    {
        if (!$this->logger) {
            return null;
        }

        return (new LogOperation($operation))->setDomain($this->connection->getConfig()->getDomainName());
    }

    /**
     * Find and return a supported handler for the operation.
     *
     * @param LdapOperationInterface $operation
     * @return \LdapTools\Operation\Handler\OperationHandlerInterface
     * @throws LdapConnectionException
     */
    protected function getOperationHandler(LdapOperationInterface $operation)
    {
        foreach ($this->handler as $handler) {
            if ($handler->supports($operation)) {
                $handler->setConnection($this->connection);
                $handler->setEventDispatcher($this->dispatcher);

                return $handler;
            }
        }

        throw new LdapConnectionException(sprintf(
            'Operation "%s" with a class name "%s" does not have a supported operation handler.',
            $operation->getName(),
            get_class($operation)
        ));
    }

    /**
     * @param LdapOperationInterface $operation
     * @param OperationHandlerInterface $handler
     * @param LogOperation $log
     * @return array
     * @throws CacheMissException
     */
    protected function getCacheOrHandlerResult(LdapOperationInterface $operation, OperationHandlerInterface $handler, LogOperation $log = null)
    {
        /** @var CacheableOperationInterface $operation */
        $cacheKey = $this->shouldUseCache($operation) ? $this->getCacheKey($operation) : null;
        $usedCache = false;

        if (!is_null($cacheKey) && $this->cache->contains($cacheKey)) {
            $result = $this->cache->get($cacheKey)->getValue();
            $usedCache = true;
        } elseif (!is_null($cacheKey) && !$operation->getExecuteOnCacheMiss()) {
            throw new CacheMissException(sprintf('The %s Operation does not exist in the cache.', $operation->getName()));
        } else {
            $this->switchServerIfNeeded($this->connection->getServer(), $operation->getServer(), $operation);
            $this->idleReconnectIfNeeded($operation);
            $this->setLdapControls($operation);
            $result = $handler->execute($operation);
        }
        if ($log) {
            $log->setUsedCachedResult($usedCache);
        }

        return [$result, $usedCache, $cacheKey];
    }

    /**
     * @param LdapOperationInterface $operation
     */
    protected function invalidateCacheIfNeeded(LdapOperationInterface $operation)
    {
        if (!($this->cache && $operation instanceof CacheableOperationInterface && $operation->getInvalidateCache())) {
            return;
        }
        $cacheKey = $this->getCacheKey($operation);

        if ($this->cache->contains($cacheKey)) {
            $this->cache->delete($cacheKey);
        }
    }

    /**
     * Cache the result of the operation if needed.
     *
     * @param LdapOperationInterface $operation
     * @param mixed $result
     * @param bool $usedCache
     * @param string|null $cacheKey
     * @return mixed
     */
    protected function cacheResultIfNeeded(LdapOperationInterface $operation, $result, $usedCache, $cacheKey)
    {
        if (!is_null($cacheKey) && !$usedCache) {
            /** @var CacheableOperationInterface $operation */
            $this->cache->set(new CacheItem($cacheKey, $result, $operation->getExpireCacheAt()));
        }

        return $result;
    }

    /**
     * @param CacheableOperationInterface $operation
     * @return string
     */
    protected function getCacheKey(CacheableOperationInterface $operation)
    {
        return $this->connection->getConfig()->getDomainName().$operation->getCacheKey();
    }

    /**
     * Performs the logic for switching the LDAP server connection.
     *
     * @param string|null $currentServer The server we are currently on.
     * @param string|null $wantedServer The server we want the connection to be on.
     * @param LdapOperationInterface $operation
     */
    protected function switchServerIfNeeded($currentServer, $wantedServer, LdapOperationInterface $operation)
    {
        if ($operation instanceof AuthenticationOperation || MBString::strtolower($currentServer) == MBString::strtolower($wantedServer)) {
            return;
        }
        if ($this->connection->isBound()) {
            $this->connection->close();
        }
        $this->connection->connect(null, null, false, $wantedServer);
    }

    /**
     * If the connection has been open as long as, or longer than, the configured idle reconnect time, then close and
     * reconnect the LDAP connection.
     *
     * @param LdapOperationInterface $operation
     */
    protected function idleReconnectIfNeeded(LdapOperationInterface $operation)
    {
        // An auth operation will force a reconnect anyways, so avoid extra work
        if (!$this->connection->getConfig()->getIdleReconnect() || $operation instanceof AuthenticationOperation) {
            return;
        }

        if ($this->connection->getIdleTime() >= $this->connection->getConfig()->getIdleReconnect()) {
            $this->connection->close()->connect();
        }
    }

    /**
     * If a connection is not bound (such as a lazy bind config) we need to force a connection.
     *
     * @param LdapOperationInterface $operation
     */
    protected function connectIfNotBound(LdapOperationInterface $operation)
    {
        if (!$this->connection->isBound() && !($operation instanceof AuthenticationOperation)) {
            $this->connection->connect();
        }
    }

    /**
     * Check whether the cache should be attempted for this operation.
     *
     * @param LdapOperationInterface $operation
     * @return bool
     */
    protected function shouldUseCache($operation)
    {
        return $operation instanceof CacheableOperationInterface && $operation->getUseCache() && $this->cache;
    }

    /**
     * Set any specific LDAP controls for this operation.
     *
     * @param LdapOperationInterface $operation
     */
    protected function setLdapControls(LdapOperationInterface $operation)
    {
        foreach ($operation->getControls() as $control) {
            $this->connection->setControl($control);
        }
    }

    /**
     * Reset any specific LDAP controls used with this operation. This is to make sure they are not accidentally used in
     * future operations when it is not expected.
     *
     * @param LdapOperationInterface $operation
     */
    protected function resetLdapControls(LdapOperationInterface $operation)
    {
        foreach ($operation->getControls() as $control) {
            $value = $control->getValue();
            $control->setValue($control->getResetValue());
            $this->connection->setControl($control);
            $control->setValue($value);
        }
    }

    /**
     * It's possible we need to skip an operation. For example, if a batch operation was only for attribute values that
     * were converted into other operations (such as a modification where only operation generator converters are used).
     * In that case the resulting batch operation will be empty but will have generated post/pre operations for it still.
     * The most common scenario is group membership only changes.
     *
     * @param LdapOperationInterface $operation
     * @return bool
     */
    protected function shouldSkipOperation(LdapOperationInterface $operation)
    {
        return $operation instanceof BatchModifyOperation && empty($operation->getBatchCollection()->toArray());
    }
}
