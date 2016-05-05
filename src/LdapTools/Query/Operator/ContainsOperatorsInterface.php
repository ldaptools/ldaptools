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
 * Operator types that may contain other Operators.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
interface ContainsOperatorsInterface
{
    /**
     * Get all the child operators of this Operator.
     *
     * @return array
     */
    public function getChildren();

    /**
     * Add another Operator to this Operator.
     *
     * @param BaseOperator[] ...$operators
     */
    public function add(BaseOperator ...$operators);
}
