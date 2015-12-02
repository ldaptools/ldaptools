<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Operation;

/**
 * Common LDAP operation functions.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
trait LdapOperationTrait
{
    /**
     * @var null|string
     */
    protected $server;

    /**
     * Set the LDAP server that should be used for the operation.
     *
     * @param string|null $server
     * @return $this
     */
    public function setServer($server)
    {
        $this->server = $server;

        return $this;
    }

    /**
     * Get the server that should be used for the operation.
     *
     * @return null|string
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * Merges the log array with common log properties.
     *
     * @param array $log
     * @return array
     */
    protected function mergeLogDefaults(array $log)
    {
        return array_merge($log, ['Server' => $this->server]);
    }
}
