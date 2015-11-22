<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Log;

/**
 * Chains several loggers.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LoggerChain implements LdapLoggerInterface
{
    /**
     * @var LdapLoggerInterface[]
     */
    protected $loggers = [];

    /**
     * Add a logger to the chain to be executed.
     *
     * @param LdapLoggerInterface $logger
     */
    public function addLogger(LdapLoggerInterface $logger)
    {
        $this->loggers[] = $logger;
    }

    /**
     * The start of a logging operation. Initiated on each logger in the chain.
     *
     * @param LogOperation $operation
     */
    public function start(LogOperation $operation)
    {
        foreach ($this->loggers as $logger) {
            $logger->start($operation);
        }
    }

    /**
     * The end of a logging operation. Initiated on each logger in the chain.
     *
     * @param LogOperation $operation
     */
    public function end(LogOperation $operation)
    {
        foreach ($this->loggers as $logger) {
            $logger->end($operation);
        }
    }
}
