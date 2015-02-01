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
 * Retrieves an available LDAP server from an array using the provided method.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapServerPool
{
    /**
     * Randomize the server array before attempting to find a server to connect to.
     */
    const SELECT_RANDOM = 'random';

    /**
     * Attempt a connection to each server in the order they appear.
     */
    const SELECT_ORDER = 'order';

    /**
     * @var array The LDAP servers.
     */
    protected $servers = [];

    /**
     * @var string The method to use when ordering the servers array.
     */
    protected $selectionMethod = self::SELECT_ORDER;

    /**
     * @var int The LDAP port number.
     */
    protected $port = 389;

    public function __construct(array $servers, $port)
    {
        $this->servers = $servers;
        $this->port = $port;
    }

    /**
     * Retrieve the first available LDAP server.
     *
     * @return string
     * @throws LdapConnectionException
     */
    public function getServer()
    {
        $servers = $this->getSortedServersArray($this->selectionMethod);

        foreach ($servers as $server) {
            if ($this->isServerAvailable($server)) {
                return $server;
            }
        }

        throw new LdapConnectionException('No LDAP server is available.');
    }

    /**
     * Set the selection method for checking servers (ie. 'random', 'order').
     *
     * @param string $method
     */
    public function setSelectionMethod($method)
    {
        if (!defined('self::SELECT_'.strtoupper($method))) {
            throw new \InvalidArgumentException(sprintf('Selection method "%s" is unknown.', $method));
        }

        $this->selectionMethod = strtolower($method);
    }

    /**
     * Get the selection method for checking servers.
     *
     * @return string
     */
    public function getSelectionMethod()
    {
        return $this->selectionMethod;
    }

    /**
     * Uses the selected method to decide how to return the server array for the check.
     *
     * @return array
     */
    public function getSortedServersArray()
    {
        if (self::SELECT_ORDER == $this->selectionMethod) {
            $servers = $this->servers;
        } else {
            $servers = $this->shuffleServers($this->servers);
        }

        return $servers;
    }

    /**
     * Check if a LDAP server is up and available.
     *
     * @param string $server
     * @return bool
     */
    protected function isServerAvailable($server)
    {
        $fp = @fsockopen($server, $this->port, $errorNumber, $errorMessage, 1);
        $result = (bool) $fp;

        if ($fp) {
            fclose($fp);
        }

        return $result;
    }

    /**
     * Returns a randomized array of the servers.
     *
     * @param array $servers
     * @return array
     */
    protected function shuffleServers(array $servers)
    {
        shuffle($servers);

        return $servers;
    }
}
