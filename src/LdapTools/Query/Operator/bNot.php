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

use LdapTools\Exception\LdapQueryException;

/**
 * Represents a NOT operator (!).
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class bNot extends BaseOperator implements ContainsOperatorsInterface
{
    use ContainsOperatorsTrait {
        add as addChildren;
    }

    const SYMBOL = '!';

    /**
     * @param BaseOperator $operator
     */
    public function __construct(BaseOperator $operator)
    {
        $this->add($operator);
        $this->validOperators = [ self::SYMBOL ];
        $this->operatorSymbol = self::SYMBOL;
    }

    /**
     * {@inheritdoc}
     */
    public function add(BaseOperator ...$operator)
    {
        $this->isOperatorAllowed(...$operator);
        $this->addChildren(...$operator);
    }

    /**
     * The 'Not' operator has a few specific requirements. Check these here.
     *
     * @param BaseOperator $operator
     * @throws LdapQueryException
     */
    protected function isOperatorAllowed(BaseOperator $operator)
    {
        if (!empty($this->children)) {
            throw new \RuntimeException('The "Not" operator can only have 1 child operator.');
        }
        if ($operator instanceof ContainsOperatorsInterface) {
            throw new LdapQueryException('Cannot add an operator to bNot that can contain other operators.');
        }
    }
}
