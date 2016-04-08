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

use LdapTools\Event\EventDispatcherInterface;
use LdapTools\Exception\LdapConnectionException;
use LdapTools\Factory\CacheFactory;
use LdapTools\Factory\LdapObjectSchemaFactory;
use LdapTools\Factory\SchemaParserFactory;
use LdapTools\Query\LdapQueryBuilder;

/**
 * Queries the RootDSE from the LDAP connection based off the state of the connection and returns the data in the form
 * of a standard LdapObject built from a defined schema file.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class RootDse
{
    /**
     * The folder location of the base schema files.
     */
    const SCHEMA_DIR = __DIR__.'/../../../resources/schema';

    /**
     * The schema name for the RootDSE.
     */
    const SCHEMA_ROOTDSE_NAME = 'rootdse';

    /**
     * @var LdapConnectionInterface
     */
    protected $connection;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var LdapObjectSchemaFactory
     */
    protected $schemaFactory;

    /**
     * @param LdapConnectionInterface $connection
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(LdapConnectionInterface $connection, EventDispatcherInterface $dispatcher)
    {
        $this->connection = $connection;
        $this->dispatcher = $dispatcher;

        $cache = CacheFactory::get(CacheFactory::TYPE_NONE, []);
        $parser = SchemaParserFactory::get(SchemaParserFactory::TYPE_YML, self::SCHEMA_DIR);
        $this->schemaFactory = new LdapObjectSchemaFactory($cache, $parser, $dispatcher);
    }

    /**
     * Get the RootDSE LdapObject while accounting for the state of the connection.
     *
     * @return \LdapTools\Object\LdapObject
     * @throws LdapConnectionException
     */
    public function get()
    {
        $anonymous = !$this->connection->isBound();

        try {
            $rootDse = self::doLdapQuery($anonymous);
        } catch (\Exception $e) {
            throw new LdapConnectionException(sprintf('Unable to query the RootDSE. %s', $e->getMessage()));
        } finally {
            // Make sure to set things back to how they were...
            if ($anonymous && $this->connection->isBound()) {
                $this->connection->close();
            }
        }

        return $rootDse;
    }

    /**
     * Do the LDAP query to get the LDAP object.
     *
     * @param bool $anonymous
     * @return \LdapTools\Object\LdapObject
     */
    protected function doLdapQuery($anonymous)
    {
        if ($anonymous) {
            $this->connection->connect('', '', true);
        }
        $schema = $this->schemaFactory->get(self::SCHEMA_ROOTDSE_NAME, $this->connection->getConfig()->getLdapType());
        
        return (new LdapQueryBuilder($this->connection))->from($schema)->select('*')->getLdapQuery()->getSingleResult();
    }
}
