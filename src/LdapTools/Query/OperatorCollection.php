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

use LdapTools\Exception\InvalidArgumentException;
use LdapTools\Exception\LdapQueryException;
use LdapTools\Query\Operator\MatchingRule;
use LdapTools\Schema\LdapObjectSchema;
use LdapTools\Query\Operator\BaseOperator;
use LdapTools\Query\Operator\bNot;
use LdapTools\Query\Operator\bAnd;
use LdapTools\Query\Operator\bOr;
use LdapTools\Query\Operator\Comparison;
use LdapTools\Query\Operator\Wildcard;

/**
 * Used to store and iterate on the operators used to build a LDAP query.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class OperatorCollection implements \IteratorAggregate
{
    /**
     * Validates the characters in an alias.
     */
    const ALIAS_REGEX = '/^[a-zA-Z0-9_]+$/';

    /**
     * @var array Contains the LdapObjectSchema objects
     */
    protected $schema = [];

    /**
     * @var LdapObjectSchema[] An array mapping of alias names to schema objects.
     */
    protected $aliases = [];

    /**
     * @var array Contains the various Operator objects grouped by their type.
     */
    protected $operators = [
        'and' => [],
        'or' => [],
        'not' => [],
        'comparison' => [],
        'wildcard' => [],
        'matchingrule' => [],
    ];

    /**
     * Add an Operator to the collection.
     *
     * @param BaseOperator[] ...$operators
     * @return $this
     * @throws LdapQueryException
     */
    public function add(BaseOperator ...$operators)
    {
        foreach ($operators as $operator) {
            if ($operator instanceof bAnd) {
                $this->operators['and'][] = $operator;
            } elseif ($operator instanceof bOr) {
                $this->operators['or'][] = $operator;
            } elseif ($operator instanceof bNot) {
                $this->operators['not'][] = $operator;
            } elseif ($operator instanceof Wildcard) {
                $this->operators['wildcard'][] = $operator;
            } elseif ($operator instanceof MatchingRule) {
                $this->operators['matchingrule'][] = $operator;
            } elseif ($operator instanceof Comparison) {
                $this->operators['comparison'][] = $operator;
            } else {
                throw new InvalidArgumentException('Unknown operator type.');
            }
        }
        
        return $this;
    }

    /**
     * Add a LdapObjectSchema for a object type that will be selected for. Optionally specify a specific alias that is
     * used to reference it. If no alias is specified, then it uses the object type name for the schema.
     *
     * @param LdapObjectSchema $schema
     * @param null|string $alias
     */
    public function addLdapObjectSchema(LdapObjectSchema $schema, $alias = null)
    {
        if (!is_null($alias) && !is_string($alias)) {
            throw new InvalidArgumentException(sprintf(
                'The alias for type "%s" must be a string, but "%s" was given.',
                $schema->getObjectType(),
                is_string($alias) ? $alias : gettype($alias)
            ));
        }
        $alias = $alias ?: $schema->getObjectType();
        if (!preg_match(self::ALIAS_REGEX, $alias)) {
            throw new InvalidArgumentException(sprintf(
                'The alias "%s" for type "%s" is invalid. Allowed characters are: A-Z, a-z, 0-9, -, _',
                $alias,
                $schema->getObjectType()
            ));
        }
        
        $this->aliases[$alias] = $schema;
    }

    /**
     * Get the aliases in the form of ['alias' => LdapObjectSchema]
     *
     * @return string[]
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * Allows this object to be iterated over.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->sortOperatorsToArray());
    }

    /**
     * Get all the 'bAnd' Operators.
     *
     * @return bAnd[]
     */
    public function getAndOperators()
    {
        return $this->operators['and'];
    }

    /**
     * Get all the 'bOr' Operators.
     *
     * @return bOr[]
     */
    public function getOrOperators()
    {
        return $this->operators['or'];
    }

    /**
     * Get all the 'bNot' Operators.
     *
     * @return bNot[]
     */
    public function getNotOperators()
    {
        return $this->operators['not'];
    }

    /**
     * Get all the 'Comparison' Operators.
     *
     * @return Comparison[]
     */
    public function getComparisonOperators()
    {
        return $this->operators['comparison'];
    }

    /**
     * Get all the 'Wildcard' Operators.
     *
     * @return Wildcard[]
     */
    public function getWildcardOperators()
    {
        return $this->operators['wildcard'];
    }

    /**
     * Get all the 'MatchingRule' Operators.
     *
     * @return MatchingRule[]
     */
    public function getMatchingRuleOperators()
    {
        return $this->operators['matchingrule'];
    }

    /**
     * Get all the Operators sorted into a single array.
     *
     * @return BaseOperator[]
     */
    public function toArray()
    {
        return $this->sortOperatorsToArray();
    }

    /**
     * Get the LDAP filter string representation of all the operators in the collection.
     *
     * @param string|null $alias The alias to narrow the filter to.
     * @return string
     */
    public function toLdapFilter($alias = null)
    {
        if (is_null($alias) && !empty($this->aliases)) {
            $filter = $this->getLdapFilterForAliases();
        } else {
            $filter = $this->getLdapFilter($alias);
        }

        return $filter;
    }

    /**
     * When an operation collection is cloned, we want to make sure the operator objects are cloned as well.
     */
    public function __clone()
    {
        foreach ($this->operators as $type => $operators) {
            foreach ($operators as $i => $operator) {
                $this->operators[$type][$i] = clone $operator;
            }
        }
    }

    /**
     * Merges all the Operators into one large array in a specific order. Before doing so, it will apply any schemas
     * that exist.
     *
     * @return BaseOperator[]
     * @throws LdapQueryException
     */
    protected function sortOperatorsToArray()
    {
        return array_merge(
            $this->operators['and'],
            $this->operators['or'],
            $this->operators['not'],
            $this->operators['comparison'],
            $this->operators['wildcard'],
            $this->operators['matchingrule']
        );
    }

    protected function getLdapFilter($alias)
    {
        $filters = [];

        if (!is_null($alias) && !array_key_exists($alias, $this->aliases)) {
            throw new InvalidArgumentException(sprintf(
                'Alias "%s" is not valid. Valid aliases are: %s',
                $alias,
                empty($this->aliases) ? '(none defined)' : implode(', ', array_keys($this->aliases))
            ));
        }

        if (!is_null($alias)) {
            $filters[] = $this->aliases[$alias]->getFilter()->toLdapFilter();
        }
        foreach ($this->toArray() as $operator) {
            $filters[] = $operator->toLdapFilter($alias);
        }
        $filter = implode('', $filters);

        if (1 < count($filters)) {
            $filter = bAnd::SEPARATOR_START.bAnd::SYMBOL.$filter.bAnd::SEPARATOR_END;
        }

        return $filter;
    }

    /**
     * Constructs a filter for multiple aliases that would return the requested LDAP objects in a single query.
     *
     * @return string
     */
    protected function getLdapFilterForAliases()
    {
        $filters = [];

        foreach (array_keys($this->aliases) as $alias) {
            $filters[] = $this->getLdapFilter($alias);
        }

        if (count($filters) == 1) {
            return $filters[0];
        } else {
            return bOr::SEPARATOR_START.bOr::SYMBOL.implode('', $filters).bOr::SEPARATOR_END;
        }
    }
}
