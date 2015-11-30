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
     * Start a paging operation by setting up the cookie and the page size.
     *
     * @param int $pageSize
     * @throws LdapConnectionException
     */
    public function start($pageSize)
    {
        if ($this->isEnabled) {
            $this->cookie = '';
            $this->pageSize = $pageSize;
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
        if (!@ldap_control_paged_result($this->connection->getConnection(), $this->pageSize, false, $this->cookie)) {
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
        if (!@ldap_control_paged_result_response($this->connection->getConnection(), $result, $this->cookie)) {
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
        if (!@ldap_control_paged_result($this->connection->getConnection(), 0)) {
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
        return ($this->cookie !== null && $this->cookie != '');
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
