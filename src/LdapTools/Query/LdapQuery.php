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
use LdapTools\Hydrator\OperationHydrator;
use LdapTools\Object\LdapObjectCollection;
use LdapTools\Operation\QueryOperation;
use LdapTools\Schema\LdapObjectSchema;

/**
 * Executes and hydrates a LDAP query.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapQuery
{
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
     * @param LdapConnectionInterface $ldap
     */
    public function __construct(LdapConnectionInterface $ldap)
    {
        $this->ldap = $ldap;
        $this->hydratorFactory = new HydratorFactory();
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
        $hydrator = $this->hydratorFactory->get($hydratorType);
        $operatorAttributes = $this->operation->getAttributes();
        $attributes = $this->getAttributesToLdap($this->getSelectedAttributes());

        $hydrator->setLdapObjectSchema(...$this->schemas);
        $hydrator->setSelectedAttributes($this->mergeOrderByAttributes($this->getSelectedAttributes()));
        $hydrator->setLdapConnection($this->ldap);
        $hydrator->setOperationType(AttributeConverterInterface::TYPE_SEARCH_FROM);
        $hydrator->setOrderBy($this->orderBy);

        $opHydrator = new OperationHydrator($this->ldap);
        $opHydrator->setLdapObjectSchema(...$this->schemas);
        $this->operation->setAttributes($attributes);
        $results = $hydrator->hydrateAllFromLdap($this->ldap->execute($opHydrator->hydrateToLdap($this->operation)));
        $this->operation->setAttributes($operatorAttributes);

        return $results;
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
     * Set the LDAP schema objects to be used for the results.
     *
     * @param LdapObjectSchema[] $schemas
     * @return $this
     */
    public function setLdapObjectSchemas(LdapObjectSchema ...$schemas)
    {
        $this->schemas = $schemas;

        return $this;
    }

    /**
     * Get the LdapObjectSchemas added to this query.
     *
     * @return LdapObjectSchema[] LdapObjectSchemas
     */
    public function getLdapObjectSchemas()
    {
        return $this->schemas;
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
     * If there are schemas present, then translate selected attributes to retrieve to their LDAP names.
     *
     * @param array $attributes
     * @return array
     */
    protected function getAttributesToLdap(array $attributes)
    {
        if (!empty($this->orderBy)) {
            $attributes = $this->mergeOrderByAttributes($attributes);
        }

        if (!empty($this->schemas)) {
            /** @var LdapObjectSchema $schema */
            $schema = reset($this->schemas);
            $newAttributes = [];
            foreach ($attributes as $attribute) {
                $newAttributes[] = $schema->getAttributeToLdap($attribute);
            }
            $attributes = $newAttributes;
        }

        return $attributes;
    }

    /**
     * If any attributes that were requested to be ordered by are not explicitly in the attribute selection, add them.
     *
     * @param array $attributes
     * @return array
     */
    protected function mergeOrderByAttributes(array $attributes)
    {
        if (!$this->isWildCardSelection()) {
            foreach (array_keys($this->orderBy) as $attribute) {
                if (!in_array(strtolower($attribute), array_map('strtolower', $attributes))) {
                    $attributes[] = $attribute;
                }
            }
        }

        return $attributes;
    }

    /**
     * Determine what attributes should be selected. This helps account for all attributes being selected both within
     * and out of the context of a schema.
     *
     * @return array
     */
    protected function getSelectedAttributes()
    {
        $attributes = $this->operation->getAttributes();

        // Interpret a single wildcard as only schema attributes.
        if (!empty($this->schemas) && !empty($attributes) && $attributes[0] == '*') {
            $attributes = array_keys($this->schemas[0]->getAttributeMap());
        // Interpret a double wildcard as all LDAP attributes even if they aren't in the schema file.
        } elseif (!empty($this->schemas) && !empty($attributes) && $attributes[0] == '**') {
            $attributes = ['*'];
        }

        return $attributes;
    }

    /**
     *
     * @return bool
     */
    protected function isWildCardSelection()
    {
        return (count($this->operation->getAttributes()) === 1 && ($this->operation->getAttributes()[0] == '*' || $this->operation->getAttributes()[0] == '**'));
    }
}
