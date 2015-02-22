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

use LdapTools\Connection\LdapConnection;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Object\LdapObjectType;
use LdapTools\Query\Builder\ADFilterBuilder;
use LdapTools\Query\Builder\FilterBuilder;
use LdapTools\Query\Operator\BaseOperator;
use LdapTools\Query\Operator\bOr;
use LdapTools\Query\Operator\bAnd;
use LdapTools\Query\Operator\Comparison;
use LdapTools\Query\Operator\From;
use LdapTools\Schema\LdapObjectSchema;
use LdapTools\Factory\LdapObjectSchemaFactory;

/**
 * Used to generate and run LDAP queries.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapQueryBuilder
{
    /**
     * The attribute name used in the Comparison for the 'From' operators for an objectCategory.
     */
    const ATTR_OBJECT_CATEGORY = 'objectCategory';

    /**
     * The attribute name used in the Comparison for the 'From' operators for an objectClass.
     */
    const ATTR_OBJECT_CLASS = 'objectClass';

    /**
     * @var LdapConnectionInterface The LDAP connection.
     */
    protected $connection;

    /**
     * @var string The base DN for the query.
     */
    protected $baseDn = null;

    /**
     * @var int The page size for the search.
     */
    protected $pageSize = null;

    /**
     * @var string The LDAP query filter string.
     */
    protected $filter = '';

    /**
     * @var array The LDAP attributes for the operation explicitly called for selection.
     */
    protected $attributes = [];

    /**
     * @var array The combined default attributes to select from each schema.
     */
    protected $defaultAttributes = [];

    /**
     * @var OperatorCollection
     */
    protected $operators;

    /**
     * @var null|bAnd The base 'And' operator when the method 'where' or 'andWhere' is used.
     */
    protected $baseAnd;

    /**
     * @var null|bOr The base 'Or' operator when the method 'orWhere' is used.
     */
    protected $baseOr;

    /**
     * @var null|From The base 'From' operator used for object selection.
     */
    protected $baseFrom;

    /**
     * @var LdapObjectSchemaFactory
     */
    protected $schemaFactory;

    /**
     * @var Builder\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var string The LDAP scope of the query (ie. subtree, onelevel, base).
     */
    protected $scope = LdapQuery::SCOPE_SUBTREE;

    /**
     * @param LdapConnectionInterface $connection
     * @param LdapObjectSchemaFactory $schemaFactory
     */
    public function __construct(LdapConnectionInterface $connection = null, LdapObjectSchemaFactory $schemaFactory = null)
    {
        $this->connection = $connection;
        $this->schemaFactory = $schemaFactory;

        if ($connection && $connection->getLdapType() == LdapConnection::TYPE_AD) {
            $this->filterBuilder = new ADFilterBuilder();
        } else {
            $this->filterBuilder = new FilterBuilder();
        }

        $this->operators = new OperatorCollection();
    }

    /**
     * Sets the base DN for the query.
     *
     * @param $baseDn
     * @return $this
     */
    public function setBaseDn($baseDn)
    {
        $this->baseDn = $baseDn;

        return $this;
    }

    /**
     * @return string The base DN for the query.
     */
    public function getBaseDn()
    {
        return $this->baseDn;
    }

    /**
     * @return int
     */
    public function getPageSize()
    {
        return $this->pageSize;
    }

    /**
     * @param $pageSize
     */
    public function setPageSize($pageSize)
    {
        $this->pageSize = $pageSize;
    }

    /**
     * Set the attributes to select from the object. Either specify a single attribute as a string or an array of
     * attribute names.
     *
     * @param string|array $attributes
     * @return $this
     */
    public function select($attributes = [])
    {
        if (!(is_array($attributes) || is_string($attributes))) {
            throw new \InvalidArgumentException('The attributes to select should either be a string or an array');
        }
        $this->attributes = is_array($attributes) ? $attributes : [ $attributes ];

        return $this;
    }

    /**
     * Add object types used to narrow the query. This can either be a string name representing the object type from the
     * schema, such as 'user' or 'group' or you can pass the LdapObjectSchema for the type. If you are using this class
     * without a schema then construct the query manually by just using the "where" and "andWhere" methods.
     *
     * @param mixed $type
     * @return $this
     */
    public function from($type)
    {
        if (!is_string($type) && !($type instanceof LdapObjectSchema)) {
            throw new \RuntimeException(
                'You must either pass the schema object type as a string to this method, or pass the schema types '
                .'LdapObjectSchema to this method.'
            );
        } elseif (is_string($type) && !$this->schemaFactory) {
            throw new \RuntimeException(
                'To build a filter with schema types you must pass a SchemaFactory to the constructor'
            );
        }
        $schema = $this->addLdapObjectSchema($type);
        $this->addOrUpdateFrom($this->getObjectFilterFromObjectSchema($schema));

        return $this;
    }

    /**
     * A convenience method to select from user object types.
     *
     * @return $this
     */
    public function fromUsers()
    {
        $this->from(LdapObjectType::USER);

        return $this;
    }

    /**
     * A convenience method to select from group object types.
     *
     * @return $this
     */
    public function fromGroups()
    {
        $this->from(LdapObjectType::GROUP);

        return $this;
    }

    /**
     * Create a logical 'and' from the passed statements. Either pass a key => value array with attribute names and
     * expected values (which will be compared in terms of equality) or pass arbitrary Operator objects using the
     * 'filter' method shortcuts or some other way.
     *
     * @param mixed ...$whereStatements Either a key => value array or an Operator type objects.
     * @return $this
     */
    public function where(...$whereStatements)
    {
        $this->addBaseAndIfNotExists();

        if (1 == count($whereStatements) && is_array($whereStatements[0])) {
            foreach ($whereStatements[0] as $attribute => $value) {
                $this->baseAnd->add(new Comparison($attribute, Comparison::EQ, $value));
            }
        } else {
            $this->baseAnd->add(...$whereStatements);
        }

        return $this;
    }

    /**
     * Adds additional operators or equality comparisons to the 'and' statement.
     * @see where
     *
     * @param mixed ...$whereStatements Either a key => value array or an Operator type objects.
     * @return $this
     */
    public function andWhere(...$whereStatements)
    {
        return $this->where(...$whereStatements);
    }

    /**
     * Create a logical 'or' from the passed arguments. Either pass a key => value array with attribute names and
     * expected values (which will be compared in terms of equality) or pass arbitrary Operator objects using the
     * 'filter' method shortcuts or some other way.
     *
     * @param mixed ...$whereStatements Either a key => value array or an Operator type objects.
     * @return $this
     */
    public function orWhere(...$whereStatements)
    {
        $this->addBaseOrIfNotExists();

        if (1 == count($whereStatements) && is_array($whereStatements[0])) {
            foreach ($whereStatements[0] as $attribute => $value) {
                $this->baseOr->add(new Comparison($attribute, Comparison::EQ, $value));
            }
        } else {
            $this->baseOr->add(...$whereStatements);
        }

        return $this;
    }

    /**
     * Add an operator object to the query.
     *
     * @param BaseOperator ...$operators
     * @return $this
     */
    public function add(BaseOperator ...$operators)
    {
        $this->operators->add(...$operators);

        return $this;
    }

    /**
     * Call this to help build additional query statements in an object-oriented fashion.
     *
     * @return FilterBuilder
     */
    public function filter()
    {
        return $this->filterBuilder;
    }

    /**
     * Set the scope of the query to search the complete the baseDn and all children.
     */
    public function setScopeSubTree()
    {
        $this->scope = LdapQuery::SCOPE_SUBTREE;

        return $this;
    }

    /**
     * Set the scope of the query to search only the entries within the baseDn but none of its children.
     */
    public function setScopeOneLevel()
    {
        $this->scope = LdapQuery::SCOPE_ONELEVEL;

        return $this;
    }

    /**
     * Set the scope of the query to only the entry defined by the baseDn.
     */
    public function setScopeBase()
    {
        $this->scope = LdapQuery::SCOPE_BASE;

        return $this;
    }

    /**
     * Get the scope for the query (ie. subtree, onelevel, base).
     *
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * Get all the attributes that will be returned from this query.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->getAttributesToSelect();
    }

    /**
     * Get the LdapQuery object based on the constructed filter and parameters in this builder.
     *
     * @return LdapQuery
     */
    public function getLdapQuery()
    {
        if (!$this->connection) {
            throw new \RuntimeException(
                'To get a LdapQuery instance you must pass a LdapConnection to the constructor'
            );
        }
        $ldapQuery =  new LdapQuery($this->connection);

        return $ldapQuery
            ->setBaseDn($this->baseDn)
            ->setPageSize($this->pageSize)
            ->setScope($this->scope)
            ->setAttributes($this->getAttributesToSelect())
            ->setLdapFilter($this->getLdapFilter())
            ->setLdapObjectSchemas(...$this->operators->getLdapObjectSchemas());
    }

    /**
     * Get the LDAP filter formed by this query.
     *
     * @return string
     */
    public function getLdapFilter()
    {
        $operators = $this->operators->toArray();
        $filter = implode('', $operators);

        if (1 < count($operators)) {
            $filter = bAnd::SEPARATOR_START.bAnd::SYMBOL.$filter.bAnd::SEPARATOR_END;
        }

        return $filter;
    }

    /**
     * @see getLdapFilter
     * @return string
     */
    public function __toString()
    {
        return $this->getLdapFilter();
    }

    /**
     * When a 'From' operator is added for a specific object type, this will be called to load its corresponding
     * schema definition object and automatically update the "From" object for the query.
     *
     * @param mixed $schema
     * @return LdapObjectSchema
     */
    protected function addLdapObjectSchema($schema)
    {
        if (!($schema instanceof LdapObjectSchema)) {
            $schema = $this->schemaFactory->get($this->connection->getSchemaName(), $schema);
        }
        $this->defaultAttributes = array_filter(
            array_merge($this->defaultAttributes, $schema->getAttributesToSelect())
        );
        $this->operators->addLdapObjectSchema($schema);

        return $schema;
    }

    /**
     * Given a schema object type, construct the filter that should be added to the "From" object. This requires some
     * extra logic as a definition can have either both a class and a category, or just one of them. If both, then it
     * should be wrapped in a "bAnd" other a simple "Comparison" will do.
     *
     * @param LdapObjectSchema $schema
     * @return bAnd|Comparison
     */
    protected function getObjectFilterFromObjectSchema(LdapObjectSchema $schema)
    {
        if ($schema->getObjectClass() && $schema->getObjectCategory()) {
            $operator = new bAnd(
                $this->filter()->eq(self::ATTR_OBJECT_CATEGORY, $schema->getObjectCategory()),
                $this->filter()->eq(self::ATTR_OBJECT_CLASS, $schema->getObjectClass())
            );
        } elseif ($schema->getObjectCategory()) {
            $operator = $this->filter()->eq(self::ATTR_OBJECT_CATEGORY, $schema->getObjectCategory());
        } else {
            $operator = $this->filter()->eq(self::ATTR_OBJECT_CLASS, $schema->getObjectClass());
        }

        return $operator;
    }


    /**
     * Adds a base 'bAnd' operator for the convenience 'where', 'andWhere' methods only if it does not already exist.
     *
     * @throws \LdapTools\Exception\LdapQueryException
     */
    protected function addBaseAndIfNotExists()
    {
        if (!$this->baseAnd) {
            $this->baseAnd = new bAnd();
            $this->operators->add($this->baseAnd);
        }
    }

    /**
     * Adds a base 'bOr' operator for the convenience 'orWhere' method only if it does not already exist.
     *
     * @throws \LdapTools\Exception\LdapQueryException
     */
    protected function addBaseOrIfNotExists()
    {
        if (!$this->baseOr) {
            $this->baseOr = new bOr();
            $this->operators->add($this->baseOr);
        }
    }

    /**
     * Adds a base 'From' operator for the convenience 'from', 'fromUsers', etc, methods only if it does not already
     * exist. If it already exists then the supplied operator is added to it.
     *
     * @param BaseOperator $operator
     * @throws \LdapTools\Exception\LdapQueryException
     */
    protected function addOrUpdateFrom(BaseOperator $operator)
    {
        if (!$this->baseFrom) {
            $this->baseFrom = new From($operator);
            $this->operators->add($this->baseFrom);
        } else {
            $this->baseFrom->add($operator);
        }
    }

    protected function getAttributesToSelect()
    {
        return !empty($this->attributes) ? $this->attributes : $this->defaultAttributes;
    }
}
