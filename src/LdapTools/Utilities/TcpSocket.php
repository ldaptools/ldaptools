<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Utilities;

/**
 * Provides a wrapper around some PHP functions for TCP sockets.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class TcpSocket
{
    /**
     * @var int The TCP port number.
     */
    protected $port;

    /**
     * @var bool|resource
     */
    protected $socket = false;

    /**
     * @param int $port The TCP port number.
     */
    public function __construct($port)
    {
        $this->port = $port;
    }

    /**
     * Connect to the host on the port defined for this TCP socket.
     *
     * @param string $host
     * @return bool
     */
    public function connect($host)
    {
        $this->socket = @fsockopen($host, $this->port, $errorNumber, $errorMessage, 1);

        return (bool) $this->socket;
    }

    /**
     * Close the connection to the host.
     */
    public function close()
    {
        @fclose($this->socket);
        $this->socket = false;
    }
}
