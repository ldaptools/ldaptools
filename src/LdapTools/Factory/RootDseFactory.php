<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Factory;

use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Exception\LdapConnectionException;
use LdapTools\Query\LdapQueryBuilder;

/**
 * Get the RootDSE as a LdapObject for the domain of the LdapConnection based off of a schema file.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class RootDseFactory
{
    /**
     * @var LdapObjectSchemaFactory
     */
    protected static $schemaFactory;

    /**
     * @var \LdapTools\Object\LdapObject[]
     */
    protected static $rootDse = [];

    /**
     * Get the RootDSE as a LdapObject for the domain of the connection object.
     *
     * @param LdapConnectionInterface $connection
     * @return \LdapTools\Object\LdapObject
     * @throws LdapConnectionException
     */
    public static function get(LdapConnectionInterface $connection)
    {
        self::setup();
        if (!isset(self::$rootDse[(string) $connection])) {
            self::$rootDse[(string) $connection] = self::getRootDseFromQuery($connection);
        }

        return self::$rootDse[(string) $connection];
    }

    /**
     * Configure the LdapObjectSchemaFactory if not already done.
     */
    protected static function setup()
    {
        if (!self::$schemaFactory) {
            $cache = CacheFactory::get(CacheFactory::TYPE_NONE,[]);
            $parser = SchemaParserFactory::get(SchemaParserFactory::TYPE_YML,  __DIR__.'/../../../resources/schema');
            self::$schemaFactory = new LdapObjectSchemaFactory($cache, $parser);
        }
    }

    /**
     * Get the actual LdapObject while accounting for the state of the connection.
     *
     * @param LdapConnectionInterface $connection
     * @return \LdapTools\Object\LdapObject
     * @throws LdapConnectionException
     */
    protected static function getRootDseFromQuery(LdapConnectionInterface $connection)
    {
        $pagedResults = $connection->getPagedResults();
        $anonymous = !$connection->isBound();

        try {
            $rootDse = self::doLdapQuery($connection, $anonymous);
        } catch (\Exception $e) {
            throw new LdapConnectionException(sprintf('Unable to query the RootDSE. %s', $e->getMessage()));
        } finally {
            // Make sure to set things back to how they were...
            $connection->setPagedResults($pagedResults);
            if ($anonymous && $connection->isBound()) {
                $connection->close();
            }
        }

        return $rootDse;
    }

    /**
     * Do the LDAP query to get the LDAP object.
     *
     * @param LdapConnectionInterface $connection
     * @param $anonymous
     * @return \LdapTools\Object\LdapObject
     */
    protected static function doLdapQuery(LdapConnectionInterface $connection, $anonymous)
    {
        $connection->setPagedResults(false);
        if ($anonymous) {
            $connection->connect('', '', true);
        }

        $schema = self::$schemaFactory->get('rootdse', $connection->getLdapType());
        $lqb = new LdapQueryBuilder($connection);
        $query = $lqb->select('*')
            ->where($lqb->filter()->present('objectClass'))
            ->setBaseDn('')
            ->setScopeBase()
            ->getLdapQuery();

        return $query->setLdapObjectSchemas($schema)->getSingleResult();
    }
}
