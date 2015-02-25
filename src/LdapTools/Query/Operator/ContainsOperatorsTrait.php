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
 * Common methods and properties needed to represent an Operator that can contain other Operators.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
trait ContainsOperatorsTrait
{
    /**
     * The operators within this operator.
     *
     * @var array
     */
    protected $children = [];

    public function getChildren()
    {
        return $this->children;
    }

    public function add(BaseOperator ...$operators)
    {
        $this->children = array_merge($this->children, $operators);
    }

    public function __toString()
    {
        return self::SEPARATOR_START.self::SYMBOL.implode($this->children).self::SEPARATOR_END;
    }
}
