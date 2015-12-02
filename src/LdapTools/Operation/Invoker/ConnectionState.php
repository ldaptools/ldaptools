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

use LdapTools\Connection\LdapConnectionInterface;

/**
 * Represents the state of the connection upon construction or when update is called.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConnectionState
{
    /**
     * @var bool
     */
    protected $wasBound;

    /**
     * @var string
     */
    protected $lastServer;

    /**
     * @param LdapConnectionInterface $connection
     */
    public function __construct(LdapConnectionInterface $connection)
    {
        $this->update($connection);
    }

    /**
     * Update the connection state properties based on the connection passed to this method.
     *
     * @param LdapConnectionInterface $connection
     * @return $this
     */
    public function update(LdapConnectionInterface $connection)
    {
        $this->wasBound = $connection->isBound();
        $this->lastServer = $connection->getServer();

        return $this;
    }

    /**
     * Get whether the connection was bound.
     *
     * @return bool
     */
    public function getWasBound()
    {
        return $this->wasBound;
    }

    /**
     * Get the last server the connection was connected to.
     *
     * @return string
     */
    public function getLastServer()
    {
        return $this->lastServer;
    }
}
