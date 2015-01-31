<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Query\Operator;

/**
 * Represents a LDAP object type selection.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class From extends BaseOperator implements ContainsOperatorsInterface
{
    use ContainsOperatorsTrait {
        add as parentAdd;
        __toString as parentToString;
    }

    protected $objectTypes = [];

    /**
     * @param BaseOperator $operator
     */
    public function __construct(BaseOperator $operator)
    {
        $this->parentAdd($operator);
    }

    /**
     * {@inheritdoc}
     */
    public function add(BaseOperator ...$operators)
    {
        $this->operatorSymbol = '|';
        $this->parentAdd(...$operators);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $start = (1 == count($this->children)) ? '' : '(';
        $end = (1 == count($this->children)) ? '' : ')';

        return $start.$this->operatorSymbol.implode($this->children).$end;
    }
}
