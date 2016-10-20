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
     * @var array
     */
    protected $options = [];

    /**
     * @param array $options The TCP stream context options.
     */
    public function __construct($options = [])
    {
        $this->options = $options;
    }

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
        $this->socket = @stream_socket_client(
            "tcp://$host:$port",
            $errorNumber,
            $errorMessage,
            $timeout,
            STREAM_CLIENT_CONNECT,
            stream_context_create($this->options)
        );

        return (bool) $this->socket;
    }

    /**
     * Sets the timeout (in seconds) for TCP operations (not the initial connection attempt).
     *
     * @param int $timeout
     * @return bool
     */
    public function setOperationTimeout($timeout)
    {
        return @stream_set_timeout($this->socket, $timeout);
    }

    /**
     * Read data from the TCP socket.
     *
     * @param int $length
     */
    public function read($length)
    {
        @fread($this->socket, $length);
    }

    /**
     * Write data to the TCP socket.
     *
     * @param mixed $data
     * @return int
     */
    public function write($data)
    {
        return @fwrite($this->socket, $data);
    }

    /**
     * Enable encryption for the socket connection.
     *
     * @param int $cryptoMethod
     * @return bool
     */
    public function enableEncryption($cryptoMethod = null)
    {
        return @stream_socket_enable_crypto($this->socket, true, $cryptoMethod);
    }

    /**
     * Get parameter/option information from the TCP socket stream/context.
     *
     * @return array
     */
    public function getParams()
    {
        return @stream_context_get_params($this->socket);
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
