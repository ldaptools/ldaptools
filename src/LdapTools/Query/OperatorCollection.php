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

use LdapTools\Exception\LdapQueryException;
use LdapTools\Query\Operator\From;
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
     * @var array Contains the LdapObjectSchema objects
     */
    protected $schema = [];

    /**
     * @var array Contains the various Operator objects grouped by their type.
     */
    protected $operators = [
        'from' => [],
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
     * @param BaseOperator ...$operators
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
            } elseif ($operator instanceof From) {
                if (1 == count($this->operators['from'])) {
                    throw new LdapQueryException('You cannot add more than one "From" operator to a query');
                }
                $this->operators['from'][] = $operator;
            } else {
                throw new \InvalidArgumentException('Unknown operator type.');
            }
        }
    }

    /**
     * Add a LdapObjectSchema for a object type that will be selected for.
     *
     * @param LdapObjectSchema $schema
     */
    public function addLdapObjectSchema(LdapObjectSchema $schema)
    {
        $this->schema[$schema->getObjectType()] = $schema;
    }

    /**
     * Get all the LdapObjectSchemas loaded into the collection by 'type' => object.
     *
     * @return LdapObjectSchema[]
     */
    public function getLdapObjectSchemas()
    {
        return array_values($this->schema);
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
     * Get all the 'From' Operators.
     *
     * @return From[]
     */
    public function getFromOperators()
    {
        return $this->operators['from'];
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
     * Merges all the Operators into one large array in a specific order. Before doing so, it will apply any schemas
     * that exist.
     *
     * @return BaseOperator[]
     * @throws LdapQueryException
     */
    protected function sortOperatorsToArray()
    {
        return array_merge(
            $this->operators['from'],
            $this->operators['and'],
            $this->operators['or'],
            $this->operators['not'],
            $this->operators['comparison'],
            $this->operators['wildcard'],
            $this->operators['matchingrule']
        );
    }
}
