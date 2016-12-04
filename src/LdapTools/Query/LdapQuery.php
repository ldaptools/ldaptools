<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Query;

use LdapTools\AttributeConverter\AttributeConverterInterface;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Exception\AttributeNotFoundException;
use LdapTools\Exception\EmptyResultException;
use LdapTools\Exception\LdapQueryException;
use LdapTools\Exception\MultiResultException;
use LdapTools\Factory\HydratorFactory;
use LdapTools\Hydrator\HydrateQueryTrait;
use LdapTools\Hydrator\OperationHydrator;
use LdapTools\Object\LdapObjectCollection;
use LdapTools\Operation\QueryOperation;
use LdapTools\Schema\LdapObjectSchema;
use LdapTools\Utilities\LdapUtilities;
use LdapTools\Utilities\MBString;

/**
 * Executes and hydrates a LDAP query.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapQuery
{
    use HydrateQueryTrait;

    /**
     * The valid ordering types for data hydrated from LDAP.
     */
    const ORDER = [
        'ASC' => 'ASC',
        'DESC' => 'DESC',
    ];

    /**
     * @var LdapConnectionInterface
     */
    protected $ldap;

    /**
     * @var HydratorFactory
     */
    protected $hydratorFactory;

    /**
     * @var LdapObjectSchema[]
     */
    protected $schemas = [];

    /**
     * @var QueryOperation|null
     */
    protected $operation;

    /**
     * @var array The attributes to order by, if any. They will be in ['attribute' => 'ASC'] form.
     */
    protected $orderBy = [];

    /**
     * @var bool
     */
    protected $caseSensitive = false;

    /**
     * @param LdapConnectionInterface $ldap
     */
    public function __construct(LdapConnectionInterface $ldap)
    {
        $this->ldap = $ldap;
        $this->hydratorFactory = new HydratorFactory();
    }

    /**
     * Whether the cache should be used for this query.
     *
     * @param $useCache
     * @return $this
     */
    public function useCache($useCache)
    {
        $this->operation->setUseCache($useCache);

        return $this;
    }

    /**
     * Expire the cache at a specific time.
     *
     * @param $expireCacheAt
     * @return $this
     */
    public function expireCacheAt($expireCacheAt)
    {
        $this->operation->setExpireCacheAt($expireCacheAt);

        return $this;
    }

    /**
     * Whether or not the query should execute on a cache miss (ie. Not in the cache yet)
     *
     * @param $executeOnCacheMiss
     * @return $this
     */
    public function executeOnCacheMiss($executeOnCacheMiss)
    {
        $this->operation->setExecuteOnCacheMiss($executeOnCacheMiss);

        return $this;
    }

    /**
     * Invalidate the cache if it exists prior to running the query.
     *
     * @param $invalidate
     * @return $this
     */
    public function invalidateCache($invalidate)
    {
        $this->operation->setInvalidateCache($invalidate);

        return $this;
    }

    /**
     * This behaves very similar to getSingleResult(), only if no results are found it will return null instead of
     * throwing an exception.
     *
     * @param string $hydratorType
     * @return array|\LdapTools\Object\LdapObject|null
     * @throws MultiResultException
     */
    public function getOneOrNullResult($hydratorType = HydratorFactory::TO_OBJECT)
    {
        try {
            return $this->getSingleResult($hydratorType);
        } catch (EmptyResultException $e) {
            return null;
        }
    }

    /**
     * Retrieve a single unique result from LDAP. If the result is empty or contains more than one entry, an exception
     * is thrown.
     *
     * @param string $hydratorType A hyrdrator type constant from the factory.
     * @return array|\LdapTools\Object\LdapObject
     * @throws EmptyResultException
     * @throws MultiResultException
     */
    public function getSingleResult($hydratorType = HydratorFactory::TO_OBJECT)
    {
        $result = $this->execute($hydratorType);
        $count = ($result instanceof LdapObjectCollection) ? $result->count() : count($result);

        if ($count === 0) {
            throw new EmptyResultException('LDAP returned no results.');
        } elseif ($count > 1) {
            throw new MultiResultException(sprintf('Expected a single result but LDAP returned %s result(s).', $count));
        }

        return ($result instanceof LdapObjectCollection) ? $result->first() : reset($result);
    }

    /**
     * Retrieve a single selected attribute value from LDAP.
     *
     * @return mixed
     * @throws LdapQueryException
     * @throws AttributeNotFoundException
     * @throws EmptyResultException
     * @throws MultiResultException
     */
    public function getSingleScalarResult()
    {
        if (count($this->operation->getAttributes()) !== 1 || $this->isWildCardSelection()) {
            $selected = $this->isWildCardSelection() ? 'All' : count($this->operation->getAttributes());
            throw new LdapQueryException(sprintf(
                'When retrieving a single value you should only select a single attribute. %s are selected.',
                $selected
            ));
        }
        $attribute = $this->operation->getAttributes();
        $attribute = reset($attribute);
        $result = $this->getSingleResult();

        if (!$result->has($attribute)) {
            throw new AttributeNotFoundException(sprintf('Attribute "%s" not found for this LDAP object.', $attribute));
        }

        return $result->get($attribute);
    }

    /**
     * This behaves very similar to getSingleScalarResult(), only if the attribute is not found it will return null
     * instead of throwing an exception.
     *
     * @return mixed
     * @throws LdapQueryException
     * @throws EmptyResultException
     * @throws MultiResultException
     */
    public function getSingleScalarOrNullResult()
    {
        try {
            return $this->getSingleScalarResult();
        } catch (AttributeNotFoundException $e) {
            return null;
        }
    }

    /**
     * This is an alias for the execute() method with an implied array hydration type. This executes the query against
     * LDAP and returns the results as an array instead of objects.
     *
     * @return array
     */
    public function getArrayResult()
    {
        return $this->execute(HydratorFactory::TO_ARRAY);
    }

    /**
     * This is an alias for the execute() method. This executes the query against LDAP and returns the result.
     *
     * @param string $hydratorType A hyrdrator type constant from the factory.
     * @return mixed
     */
    public function getResult($hydratorType = HydratorFactory::TO_OBJECT)
    {
        return $this->execute($hydratorType);
    }

    /**
     * Execute a query based on the set parameters. Optionally choose a mode to hydrate the results in.
     *
     * @param string $hydratorType A hyrdrator type constant from the factory.
     * @return mixed
     */
    public function execute($hydratorType = HydratorFactory::TO_OBJECT)
    {
        if (is_string($this->operation->getFilter()) || empty($this->operation->getFilter()->getAliases())) {
            $results = $this->getResultsFromLdap(clone $this->operation, $hydratorType);
        } else {
            $results = $this->getResultsForAliases($hydratorType);
        }

        return $this->sortResults($results);
    }

    /**
     * Set the query operation to run against LDAP.
     *
     * @param QueryOperation $operation
     * @return $this
     */
    public function setQueryOperation(QueryOperation $operation)
    {
        $this->operation = $operation;

        return $this;
    }

    /**
     * Get the query operation that will run against LDAP.
     *
     * @return QueryOperation|null
     */
    public function getQueryOperation()
    {
        return $this->operation;
    }

    /**
     * Set the attributes to order the results by.
     *
     * @param array $orderBy In the form of ['attribute' => 'ASC', ...]
     * @return $this
     */
    public function setOrderBy(array $orderBy)
    {
        // Validate and force the case for the direction.
        foreach ($orderBy as $attribute => $direction) {
            if (!in_array(strtoupper($direction), self::ORDER)) {
                throw new \InvalidArgumentException(sprintf(
                    'Order direction "%s" is invalid. Valid values are ASC and DESC',
                    $direction
                ));
            }
            $orderBy[$attribute] = strtoupper($direction);
        }
        $this->orderBy = $orderBy;

        return $this;
    }

    /**
     * Get the attributes to order the results by.
     *
     * @return array
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * Set whether or not the results should be sorted in a case-sensitive way.
     *
     * @param bool $caseSensitive
     * @return $this
     */
    public function setIsCaseSensitiveSort($caseSensitive)
    {
        $this->caseSensitive = (bool) $caseSensitive;

        return $this;
    }

    /**
     * Get whether or not the results should be sorted in a case-sensitive way.
     *
     * @return bool
     */
    public function getIsCaseSensitiveSort()
    {
        return $this->caseSensitive;
    }

    /**
     * @param mixed $results
     * @return mixed $results
     */
    protected function sortResults($results)
    {
        if (empty($this->orderBy)) {
            return $results;
        }
        $aliases = [];
        if (!is_string($this->operation->getFilter()) && !empty($this->operation->getFilter()->getAliases())) {
            $aliases = $this->operation->getFilter()->getAliases();
        }
        $selected = $this->getSelectedForAllAliases($aliases);
        $orderBy = $this->getFormattedOrderBy($selected, $aliases);

        return (new LdapResultSorter($orderBy, $aliases))
            ->setIsCaseSensitive($this->caseSensitive)
            ->sort($results);
    }

    /**
     * @param QueryOperation $operation
     * @param string $hydratorType
     * @param null|LdapObjectSchema $schema
     * @param null|string $alias
     * @return mixed
     */
    protected function getResultsFromLdap(QueryOperation $operation, $hydratorType, $schema = null, $alias = null)
    {
        $hydrator = $this->hydratorFactory->get($hydratorType);
        $hydrator->setLdapConnection($this->ldap);
        $hydrator->setOperationType(AttributeConverterInterface::TYPE_SEARCH_FROM);
        $hydrator->setLdapObjectSchema($schema);
        $hydrator->setSelectedAttributes($this->getAttributesToLdap($operation->getAttributes(), false, $schema, $alias));

        $opHydrator = new OperationHydrator($this->ldap);
        $opHydrator->setAlias($alias);
        $opHydrator->setOrderBy($this->orderBy);
        $opHydrator->setLdapObjectSchema($schema);
        $opHydrator->hydrateToLdap($operation);

        return $hydrator->hydrateAllFromLdap($this->ldap->execute($operation));
    }

    /**
     * Goes through each alias for the operation to get results only for that specific type, then combine and return
     * them all.
     *
     * @param string $hydratorType
     * @return array|LdapObjectCollection|mixed
     */
    protected function getResultsForAliases($hydratorType)
    {
        /** @var LdapObjectCollection|array $results */
        $results = [];

        foreach ($this->operation->getFilter()->getAliases() as $alias => $schema) {
            $operation = clone $this->operation;

            /**
             * If we received the partial limit of results, re-adjust the next operations limit so we don't go over.
             *
             * @todo This is getting difficult due to multiple operations needed to select all schema types. If this was
             *       a single operation the issue would not exist. But with a single query and multiple types I cannot
             *       easily determine which result is what type. Unsure of the best way to fix this at the moment.
             */
            if ($operation->getSizeLimit() && count($results) < $operation->getSizeLimit()) {
                $operation->setSizeLimit($operation->getSizeLimit() - count($results));
            }

            $objects = $this->getResultsFromLdap($operation, $hydratorType, $schema, $alias);
            if ($objects instanceof LdapObjectCollection && $results) {
                $results->add(...$objects->toArray());
            } elseif ($objects instanceof LdapObjectCollection) {
                $results = $objects;
            } else {
                $results = array_merge($results, $objects);
            }

            // If the results have reached the expected size limit then end the loop.
            if ($this->operation->getSizeLimit() && count($results) == $operation->getSizeLimit()) {
                break;
            }
        }

        return $results;
    }

    /**
     * Get all the attributes that were selected for the query taking into account all of the aliases used.
     *
     * @param array $aliases
     * @return array
     */
    protected function getSelectedForAllAliases(array $aliases)
    {
        if (empty($aliases)) {
            $selected = $this->mergeOrderByAttributes($this->getSelectedQueryAttributes($this->operation->getAttributes()));
        } else {
            // If there are aliases, then we need to loop through each one to determine was was actually selected for each.
            $selected = [];
            foreach ($aliases as $alias => $schema) {
                $selected = array_replace(
                    $selected,
                    $this->mergeOrderByAttributes($this->getSelectedQueryAttributes($this->operation->getAttributes(), $schema), $alias)
                );
            }
        }

        return $selected;
    }

    /**
     * This formats the orderBy array to ignore case differences between the orderBy name and the actually selected name,
     * such as for sorting arrays.
     *
     * @param $selected
     * @param $aliases
     * @return array
     */
    protected function getFormattedOrderBy($selected, $aliases)
    {
        if (!empty($aliases) && !$this->isWildCardSelection()) {
            $orderBy = [];
            foreach ($this->orderBy as $attribute => $direction) {
                list($alias, $attr) = LdapUtilities::getAliasAndAttribute($attribute);
                $orderAttr = MBString::array_search_get_value($attr, $selected);
                $orderAttr = $alias ? "$alias.$orderAttr" : $orderAttr;
                $orderBy[$orderAttr] = $direction;
            }
        } else {
            $orderBy = $this->orderBy;
        }

        return $orderBy;
    }
}
