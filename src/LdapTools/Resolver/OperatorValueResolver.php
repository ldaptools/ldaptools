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
    public function __construct(LdapObjectSchema $schema = null, OperatorCollection $operators, $type)
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
        /** @var LdapObjectSchema $schema */
        foreach ($this->operators->getAliases() as $alias => $schema) {
            $this->schema = $schema;
            $this->processOperator($schema->getFilter(), null);
            foreach ($this->operators as $operator) {
                $this->processOperator($operator, $alias);
            }
        }

        return $this->operators;
    }

    /**
     * @param BaseOperator $operator
     * @param string|null $alias
     * @oaran string $alias
     */
    protected function processOperator(BaseOperator $operator, $alias)
    {
        if ($operator instanceof ContainsOperatorsInterface) {
            foreach ($operator->getChildren() as $childOperator) {
                $this->processOperator($childOperator, $alias);
            }
        } elseif (!$operator->getWasConverterUsed($alias) && $this->schema->hasConverter($operator->getAttribute())) {
            $this->convertOperatorValues($operator, $alias);
        }
        $operator->setTranslatedAttribute($this->schema->getAttributeToLdap($operator->getAttribute()), $alias);
    }

    /**
     * @param BaseOperator $operator
     * @param string $alias
     */
    protected function convertOperatorValues(BaseOperator $operator, $alias)
    {
        if (!is_null($operator->getAlias()) && $operator->getAlias() !== $alias) {
            return;
        }
        $isValueArray = is_array($operator->getValue());
        $values = $isValueArray ? $operator->getValue() : [$operator->getValue()];
        $converter = $this->getConverterWithOptions($this->schema->getConverter($operator->getAttribute()));
        $values = $this->doConvertValues($operator->getAttribute(), $values, 'toLdap', $converter);
        if ($values instanceof BaseOperator) {
            $this->processOperator($values, $alias);
        }
        $operator->setConvertedValue($isValueArray || $converter->getIsMultiValuedConverter() ? $values : $values[0], $alias);
        $operator->setWasConverterUsed(true, $alias);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function iterateAggregates(array $toAggregate, $values,  AttributeConverterInterface $converter)
    {
    }
}
