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
 * Represents an AND operator (&).
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class bAnd extends BaseOperator implements ContainsOperatorsInterface
{
    use ContainsOperatorsTrait;

    const SYMBOL = '&';

    /**
     * @param BaseOperator[] ...$operators
     */
    public function __construct(BaseOperator ...$operators)
    {
        $this->children = $operators;
        $this->validOperators = [ self::SYMBOL ];
        $this->operatorSymbol = self::SYMBOL;
    }
}
