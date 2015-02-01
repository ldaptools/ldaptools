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

use LdapTools\Query\Hydrator\ArrayHydrator;

class RootDse
{
    /**
     * This RootDSE attribute contains the BaseDN information.
     */
    const BASE_DN = 'defaultnamingcontext';

    /**
     * This RootDSE attribute contains OIDs for supported controls.
     */
    const SUPPORTED_CONTROL = 'supportedcontrol';

    /**
     * The LDAP filter used to query the RootDSE.
     */
    const FILTER = '(objectclass=*)';

    /**
     * @var LdapConnectionInterface
     */
    protected $connection;

    /**
     * @var ArrayHydrator
     */
    protected $hydrator;

    /**
     * @var array The RootDSE as an associative array.
     */
    protected $rootDse = [];

    function __construct(LdapConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->hydrator = new ArrayHydrator();

        $this->queryRootDse();
    }

    /**
     * Check if a specific server control OID is supported.
     *
     * @param string @oid
     * @return bool
     */
    public function isControlSupported($oid)
    {
        return (isset($this->rootDse[self::SUPPORTED_CONTROL]) && in_array($oid, $this->rootDse[self::SUPPORTED_CONTROL]));
    }

    /**
     * Get the default naming context (ie. base DN) for the domain.
     *
     * @return string
     */
    public function getDefaultNamingContext()
    {
        return isset($this->rootDse[self::BASE_DN]) ? $this->rootDse[self::BASE_DN] : '';
    }

    /**
     * Get all RootDSE entries as an associative array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->rootDse;
    }

    /**
     * Retrieve the RootDSE for the connection.
     *
     * An anonymous bind should be sufficient to query the RootDSE. This is true in Active Directory
     * by default, but not OpenLDAP in all cases. OpenLDAP requires slight modification to bind anonymously
     * when installed on some distributions.
     */
    protected function queryRootDse()
    {
        $pagedResults = $this->connection->getPagedResults();
        $wasAnonymousBind = false;

        // If the connection is not bound, then we need to bind anonymously and turn of paging.
        if (!$this->connection->isBound()) {
            $this->connection->connect('','', true);
            $this->connection->setPagedResults(false);
            $wasAnonymousBind = true;
        }
        $entry = $this->connection->search(self::FILTER, [], '', 'base');

        // Make sure to set things back to how they were...
        if ($wasAnonymousBind) {
            $this->connection->setPagedResults($pagedResults);
            $this->connection->close();
        }
        $this->rootDse = $this->hydrator->hydrateEntry($entry[0]);
    }
}