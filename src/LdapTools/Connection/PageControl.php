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
            // Update the LDAP server controls with the pagination options.
            $this->setLdapServerControls();
        }
    }

    /**
     * Build ldap server controls options, and more specifically the pagination options.
     * @return array[]
     */
    public function buildLdapServerControls()
    {
        return [
            LDAP_CONTROL_PAGEDRESULTS => [
                'oid'        => LDAP_CONTROL_PAGEDRESULTS,
                'isCritical' => false,
                'value'      => [
                    'size'   => $this->pageSize,
                    'cookie' => $this->cookie,
                ],
            ],
        ];
    }

    /**
     * Set LDAP server controls options.
     */
    public function setLdapServerControls()
    {
        @ldap_set_option($this->connection->getResource(), LDAP_OPT_SERVER_CONTROLS, $this->buildLdapServerControls());
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
     * @param LDAP\Result|resource $result
     */
    public function next($result)
    {
        if (!$this->isEnabled) {
            return;
        }
        $this->resultNumber += $this->pageSize;

        $errorCode = $dn = $errorMessage = $referrals = null;
        $controls = $this->buildLdapServerControls();
        @ldap_parse_result($this->connection->getResource(), $result, $errorCode, $dn, $errorMessage, $referrals, $controls);

        // If the size limit exceeds the page size, and the next page would exceed the limit, reduce the page size...
        if ($this->sizeLimit && ($this->resultNumber + $this->pageSize) > $this->sizeLimit) {
            $this->pageSize = $this->sizeLimit - $this->resultNumber;
        }

        // Extract the cookie from the parsed result controls.
        $this->cookie = $controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'];
        // Update the LDAP server options with the new pagination options.
        $this->setLdapServerControls();
    }

    /**
     * Resets the paging control so that read operations work after a paging operation is used.
     *
     * @throws LdapConnectionException
     */
    public function resetPagingControl()
    {
        if (!$this->isEnabled) {
            return;
        }

        $controls = [LDAP_CONTROL_PAGEDRESULTS => [
            'oid' => LDAP_CONTROL_PAGEDRESULTS
        ]];
        @ldap_set_option($this->connection->getResource(), LDAP_OPT_SERVER_CONTROLS, $controls);
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
