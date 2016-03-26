<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Resolver;

use LdapTools\AttributeConverter\AttributeConverterInterface;
use LdapTools\Query\Operator\BaseOperator;
use LdapTools\Query\Operator\ContainsOperatorsInterface;
use LdapTools\Query\OperatorCollection;
use LdapTools\Schema\LdapObjectSchema;

/**
 * Parses through an OperatorCollection to convert values.
 * 
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class OperatorValueResolver extends BaseValueResolver
{
    /**
     * @var OperatorCollection
     */
    protected $operators;

    /**
     * @param LdapObjectSchema $schema
     * @param OperatorCollection $operators
     * @param int $type The LDAP operation type. See AttributeConverterInterface::TYPE_*.
     */
    public function __construct(LdapObjectSchema $schema, OperatorCollection $operators, $type)
    {
        parent::__construct($schema, $type);
        $this->operators = $operators;
    }

    /**
     * Convert the batch values to LDAP batch mod specifications array.
     *
     * @return OperatorCollection
     */
    public function toLdap()
    {
        foreach ($this->operators as $operator) {
            $this->processOperator($operator);
        }

        return $this->operators;
    }

    /**
     * @param BaseOperator $operator
     */
    protected function processOperator(BaseOperator $operator)
    {
        if ($operator instanceof ContainsOperatorsInterface) {
            foreach ($operator->getChildren() as $childOperator) {
                $this->processOperator($childOperator);
            }
        } elseif (!$operator->getWasConverterUsed() && $this->schema->hasConverter($operator->getAttribute())) {
            $this->convertOperatorValues($operator);
        }
        $operator->setTranslatedAttribute($this->schema->getAttributeToLdap($operator->getAttribute()));
    }

    /**
     * @param BaseOperator $operator
     */
    protected function convertOperatorValues(BaseOperator $operator)
    {
        $isValueArray = is_array($operator->getValue());
        $values = $isValueArray ? $operator->getValue() : [$operator->getValue()];
        $values = $this->doConvertValues($operator->getAttribute(), $values, 'toLdap');

        $operator->setConvertedValue($isValueArray ? $values : $values[0]);
        $operator->setWasConverterUsed(true);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function iterateAggregates(array $toAggregate, $values,  AttributeConverterInterface $converter)
    {
    }
}
