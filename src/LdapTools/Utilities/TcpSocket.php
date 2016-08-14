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
     * @var bool|resource
     */
    protected $socket = false;

    /**
     * Connect to the host on the port defined for this TCP socket.
     *
     * @param string $host
     * @param int $port
     * @param int $timeout
     * @return bool
     */
    public function connect($host, $port, $timeout = 1)
    {
        $this->socket = @fsockopen($host, $port, $errorNumber, $errorMessage, $timeout);

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
