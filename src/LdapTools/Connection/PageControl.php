<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Connection;

use LdapTools\Exception\LdapConnectionException;

/**
 * Handles paging control for the LDAP connection.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class PageControl
{
    /**
     * @var LdapConnectionInterface
     */
    protected $connection;

    /**
     * @var null|string
     */
    protected $cookie = null;

    /**
     * @var int The page size for the paging operation.
     */
    protected $pageSize = 0;

    /**
     * @var int The size limit for the paging operation.
     */
    protected $sizeLimit = 0;

    /**
     * @var int The result set number the paging operation is currently on.
     */
    protected $resultNumber = 0;

    /**
     * @var bool Whether or not paging is actually active for the connection.
     */
    protected $isEnabled = true;

    /**
     * @param LdapConnectionInterface $connection
     */
    public function __construct(LdapConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Start a paging operation by setting up the cookie and the page size. Optionally set a size limit.
     *
     * @param int $pageSize
     * @param int $sizeLimit
     * @throws LdapConnectionException
     */
    public function start($pageSize, $sizeLimit = 0)
    {
        if ($this->isEnabled) {
            $this->cookie = '';
            $this->pageSize = ($sizeLimit && $sizeLimit < $pageSize) ? $sizeLimit : $pageSize;
            $this->sizeLimit = $sizeLimit;
        }
    }

    /**
     * End a paging operation.
     *
     * @throws LdapConnectionException
     */
    public function end()
    {
        if ($this->isEnabled) {
            $this->resetPagingControl();
            $this->cookie = null;
            $this->resultNumber = 0;
        }
    }

    /**
     * Signifies to the connection to expect the next paged result with the current cookie and page size.
     *
     * @throws LdapConnectionException
     */
    public function next()
    {
        if (!$this->isEnabled) {
            return;
        }
        // If the size limit exceeds the page size, and the next page would exceed the limit, reduce the page size...
        if ($this->sizeLimit && ($this->resultNumber + $this->pageSize) > $this->sizeLimit) {
            $this->pageSize = $this->sizeLimit - $this->resultNumber;
        }
        if (!@ldap_control_paged_result($this->connection->getResource(), $this->pageSize, false, $this->cookie)) {
            throw new LdapConnectionException(sprintf(
                'Unable to enable paged results: %s',
                $this->connection->getLastError()
            ));
        }
    }

    /**
     * Updating the paging operation based on the result resource returned from a query.
     *
     * @param resource $result
     * @throws LdapConnectionException
     */
    public function update($result)
    {
        if (!$this->isEnabled) {
            return;
        }
        $this->resultNumber += $this->pageSize;
        if (!@ldap_control_paged_result_response($this->connection->getResource(), $result, $this->cookie)) {
            throw new LdapConnectionException(
                sprintf('Unable to set paged results response: %s', $this->connection->getLastError())
            );
        }
    }

    /**
     * Resets the paging control so that read operations work after a paging operation is used.
     *
     * @throws LdapConnectionException
     */
    public function resetPagingControl()
    {
        // Per RFC 2696, to abandon a paged search you should send a size of 0 along with the cookie used in the search.
        // However, testing this it doesn't seem to completely work. Perhaps a PHP bug?
        if (!@ldap_control_paged_result($this->connection->getResource(), 0, false, $this->cookie)) {
            throw new LdapConnectionException(sprintf(
                'Unable to reset paged results control for read operation: %s',
                $this->connection->getLastError()
            ));
        }
    }

    /**
     * Returns whether or not a paging operation is active based on the status of the paging cookie (omnomnom).
     *
     * @return bool
     */
    public function isActive()
    {
        $active = ($this->cookie !== null && $this->cookie != '');
        
        if ($this->sizeLimit && $this->sizeLimit === $this->pageSize) {
            $active = false;
        }
        
        return $active;
    }

    /**
     * Check whether or not paging is active. If it is not active, the controls methods will not actually do anything.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->isEnabled;
    }

    /**
     * Set whether or not paging is active and should be used.
     *
     * @param bool $enabled
     */
    public function setIsEnabled($enabled)
    {
        $this->isEnabled = (bool) $enabled;
    }
}
