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

use LdapTools\Operation\LdapOperationInterface;

/**
 * Represents a LDAP operation type to be logged.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LogOperation
{
    /**
     * @var LdapOperationInterface The operation type.
     */
    protected $operation;

    /**
     * @var string The domain name that the log operation pertains to.
     */
    protected $domain;

    /**
     * @var bool Whether or not the operation used a result from the cache.
     */
    protected $usedCachedResult = false;

    /**
     * @var string The error/exception message if issues were encountered during the operation.
     */
    protected $error;

    /**
     * @var int The start of the operation.
     */
    protected $start;

    /**
     * @var int The end of the operation.
     */
    protected $stop;

    /**
     * @param LdapOperationInterface $operation
     */
    public function __construct(LdapOperationInterface $operation)
    {
        $this->operation = $operation;
    }

    /**
     * @return bool
     */
    public function getUsedCachedResult()
    {
        return $this->usedCachedResult;
    }

    /**
     * @param bool $usedCachedResult
     * @return $this
     */
    public function setUsedCachedResult($usedCachedResult)
    {
        $this->usedCachedResult = (bool) $usedCachedResult;

        return $this;
    }

    /**
     * Get the LDAP operation represented for this log.
     *
     * @return LdapOperationInterface
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * Get the domain that this log operation pertains to.
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Get the time that the operation started.
     *
     * @return int
     */
    public function getStartTime()
    {
        return $this->start;
    }

    /**
     * Get the time that the operation stopped.
     *
     * @return int
     */
    public function getStopTime()
    {
        return $this->stop;
    }

    /**
     * The error/exception message if the operation encountered issues.
     *
     * @return null|string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Set the domain name that this log operation pertains to.
     *
     * @param string $domain
     * @return $this
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Set the error/exception message if issues were encountered during the LDAP operation.
     *
     * @param string $error
     * @return $this
     */
    public function setError($error)
    {
        $this->error = $error;

        return $this;
    }

    /**
     * Set the LDAP operation represented for this log.
     *
     * @param LdapOperationInterface $operation
     * @return $this
     */
    public function setOperation(LdapOperationInterface $operation)
    {
        $this->operation = $operation;

        return $this;
    }

    /**
     * Signifies that the operation has started.
     *
     * @return $this
     */
    public function start()
    {
        $this->start = microtime(true);

        return $this;
    }

    /**
     * Signifies that the operation is over.
     *
     * @return $this
     */
    public function stop()
    {
        $this->stop = microtime(true);

        return $this;
    }
}
