<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Query\Builder;

use LdapTools\Connection\LdapConnection;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Query\Operator\BaseOperator;
use LdapTools\Query\Operator\bOr;
use LdapTools\Query\Operator\bAnd;
use LdapTools\Query\Operator\bNot;
use LdapTools\Query\Operator\Comparison;
use LdapTools\Query\Operator\MatchingRule;
use LdapTools\Query\Operator\Wildcard;
use LdapTools\Query\MatchingRuleOid;

/**
 * Used to help build-up the filter operators in a more fluid object-oriented method.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class FilterBuilder
{
    /**
     * Get a FilterBuilder instance based on the connection.
     *
     * @param LdapConnectionInterface|null $connection
     * @return ADFilterBuilder|FilterBuilder
     */
    public static function getInstance(LdapConnectionInterface $connection = null)
    {
        if ($connection && $connection->getConfig()->getLdapType() == LdapConnection::TYPE_AD) {
            $filterBuilder = new ADFilterBuilder();
        } else {
            $filterBuilder = new self();
        }
        
        return $filterBuilder;
    }

    /**
     * A logical AND operator.
     *
     * @param mixed ...$op
     * @return bAnd
     */
    public function bAnd(...$op)
    {
        return new bAnd(...$op);
    }

    /**
     * A logical OR operator.
     *
     * @param array ...$op
     * @return bOr
     */
    public function bOr(...$op)
    {
        return new bOr(...$op);
    }

    /**
     * A logical NOT operator.
     *
     * @param BaseOperator $op
     * @return bNot
     */
    public function bNot(BaseOperator $op)
    {
        return new bNot($op);
    }

    /**
     * Check if an attribute value matches any of the values in the list of values provided.
     *
     * @param string $attribute
     * @param array $values
     * @return bOr
     */
    public function in($attribute, array $values)
    {
        return new bOr(...array_map(function($v) use ($attribute) {
            return $this->eq($attribute, $v);
        }, $values));
    }

    /**
     * An equal-to comparison.
     *
     * @param $attribute
     * @param $value
     * @return Comparison
     */
    public function eq($attribute, $value)
    {
        return new Comparison($attribute, Comparison::EQ, $value);
    }

    /**
     * A convenience method for a not-equal-to comparison.
     *
     * @param $attribute
     * @param $value
     * @return bNot
     */
    public function neq($attribute, $value)
    {
        return new bNot($this->eq($attribute, $value));
    }

    /**
     * An approximately-equal-to comparison.
     *
     * @param $attribute
     * @param $value
     * @return Comparison
     */
    public function aeq($attribute, $value)
    {
        return new Comparison($attribute, Comparison::AEQ, $value);
    }

    /**
     * A less-than comparison. Since an actual '<' operator does not exist in LDAP, this is a combination of a
     * greater-than-or-equal-to operator along with a check if the attribute is set/present. This is encapsulated within
     * a logical 'AND' operator.
     *
     * @param string  $attribute
     * @param mixed $value
     * @return bAnd
     */
    public function lt($attribute, $value)
    {
        return new bAnd(new bNot($this->gte($attribute, $value)), $this->present($attribute));
    }

    /**
     * A less-than-or-equal-to comparison.
     *
     * @param $attribute
     * @param $value
     * @return Comparison
     */
    public function lte($attribute, $value)
    {
        return new Comparison($attribute, Comparison::LTE, $value);
    }

    /**
     * A greater-than comparison. Since an actual '>' operator does not exist in LDAP, this is a combination of a
     * less-than-or-equal-to operator along with a check if the attribute is set/present. This is encapsulated within a
     * logical 'AND' operator.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bAnd
     */
    public function gt($attribute, $value)
    {
        return new bAnd(new bNot($this->lte($attribute, $value)), $this->present($attribute));
    }

    /**
     * A greater-than-or-equal-to comparison.
     *
     * @param $attribute
     * @param $value
     * @return Comparison
     */
    public function gte($attribute, $value)
    {
        return new Comparison($attribute, Comparison::GTE, $value);
    }

    /**
     * Checks for the existence of an attribute.
     *
     * @param $attribute
     * @return Wildcard
     */
    public function present($attribute)
    {
        return new Wildcard($attribute, Wildcard::PRESENT);
    }

    /**
     * Convenience method to check for the non-existence of an attribute.
     *
     * @param $attribute
     * @return bNot
     */
    public function notPresent($attribute)
    {
        return new bNot($this->present($attribute));
    }

    /**
     * Encapsulates the search term with a wildcard on each end.
     *
     * @param $attribute
     * @param $value
     * @return Wildcard
     */
    public function contains($attribute, $value)
    {
        return new Wildcard($attribute, Wildcard::CONTAINS, $value);
    }

    /**
     * Places a wildcard at the end of the search term.
     *
     * @param $attribute
     * @param $value
     * @return Wildcard
     */
    public function startsWith($attribute, $value)
    {
        return new Wildcard($attribute, Wildcard::STARTS_WITH, $value);
    }

    /**
     * Places a wildcard at the beginning of the search term.
     *
     * @param $attribute
     * @param $value
     * @return Wildcard
     */
    public function endsWith($attribute, $value)
    {
        return new Wildcard($attribute, Wildcard::ENDS_WITH, $value);
    }

    /**
     * Do not escape wildcard characters in the value, but will escape all other characters. This allows for searches
     * That may need several arbitrarily placed wildcards (ie. sn=Th*m*s)
     *
     * @param $attribute
     * @param $value
     * @return Wildcard
     */
    public function like($attribute, $value)
    {
        return new Wildcard($attribute, Wildcard::LIKE, $value);
    }

    /**
     * Perform an extensible match.
     *
     * @param string|null $attribute
     * @param string|null $rule
     * @param mixed $value
     * @param bool $dnFlag
     * @return MatchingRule
     */
    public function match($attribute, $rule, $value, $dnFlag = false)
    {
        return new MatchingRule($attribute, $rule, $value, $dnFlag);
    }

    /**
     * Perform a match for a specific DN part and value (ie. 'ou' === 'sales').
     *
     * @param string $part
     * @param string $value
     * @return MatchingRule
     */
    public function matchDn($part, $value)
    {
        return new MatchingRule($part, null, $value, true);
    }

    /**
     * Perform a bitwise AND operation against an attribute.
     *
     * @param string $attribute
     * @param int $value
     * @return MatchingRule
     */
    public function bitwiseAnd($attribute, $value)
    {
        return $this->match($attribute, MatchingRuleOid::BIT_AND, $value);
    }

    /**
     * Perform a bitwise OR operation against an attribute.
     *
     * @param string $attribute
     * @param int $value
     * @return MatchingRule
     */
    public function bitwiseOr($attribute, $value)
    {
        return $this->match($attribute, MatchingRuleOid::BIT_OR, $value);
    }
}
