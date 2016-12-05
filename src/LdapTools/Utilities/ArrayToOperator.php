<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Utilities;

use LdapTools\Exception\InvalidArgumentException;
use LdapTools\Query\Builder\FilterBuilder;
use LdapTools\Query\Operator\BaseOperator;
use LdapTools\Schema\LdapObjectSchema;

/**
 * Creates a LDAP operator based off an array
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ArrayToOperator
{
    /**
     * The attribute name used in the Comparison for the 'From' operators for an objectCategory.
     *
     * @internal
     */
    const ATTR_OBJECT_CATEGORY = 'objectCategory';

    /**
     * The attribute name used in the Comparison for the 'From' operators for an objectClass.
     *
     * @internal
     */
    const ATTR_OBJECT_CLASS = 'objectClass';
    
    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var LdapObjectSchema
     */
    protected $schema;

    /**
     * @var array Maps some common operator names to their function names.
     */
    protected $opMap = [
        'and' => 'bAnd',
        'or' => 'bOr',
        'not' => 'bNot',
    ];

    /**
     * @var array
     */
    protected $filterMethods = [];
    
    public function __construct()
    {
        $this->filterBuilder = new FilterBuilder();

        $builderRefl = new \ReflectionClass($this->filterBuilder);
        foreach ($builderRefl->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $this->filterMethods[] = $method->getName();
        }
    }

    /**
     * Given a schema object and a filter array, construct the operator to be used for the schema.
     *
     * @param LdapObjectSchema $schema
     * @param array $filter
     * @return BaseOperator
     */
    public function getOperatorForSchema(LdapObjectSchema $schema, array $filter)
    {
        $operator = null;
        
        $categoryOperator = $this->getCategoryOperator($schema->getObjectCategory());
        $classOperator = $this->getClassOperator($schema->getObjectClass());

        if ($classOperator && $categoryOperator) {
            $operator = $this->filterBuilder->bAnd($categoryOperator, $classOperator);
        } elseif ($categoryOperator) {
            $operator = $categoryOperator;
        } elseif ($classOperator) {
            $operator = $classOperator;
        }
        
        return $this->getOperatorForArray($filter, $operator);
    }

    /**
     * Given an array that represents different filter methods, return the operator representation of it.
     *
     * @param array $filter
     * @return BaseOperator
     */
    public function toOperator(array $filter)
    {
        if (empty($filter)) {
            throw new InvalidArgumentException('Cannot parse an empty array to an LDAP filter operator.');
        }

        return $this->getOperatorForArray($filter);
    }

    /**
     * @param array $objectClass
     * @return \LdapTools\Query\Operator\bAnd|null
     */
    protected function getClassOperator(array $objectClass)
    {
        $classOperator = null;
        
        if (count($objectClass) > 1) {
            $classOperator = $this->filterBuilder->bAnd();
            foreach ($objectClass as $class) {
                $classOperator->add($this->filterBuilder->eq(self::ATTR_OBJECT_CLASS, $class));
            }
        } elseif (count($objectClass) == 1) {
            $classOperator = $this->filterBuilder->eq(self::ATTR_OBJECT_CLASS, $objectClass[0]);
        }
        
        return $classOperator;
    }

    /**
     * @param $objectCategory
     * @return null|\LdapTools\Query\Operator\Comparison
     */
    protected function getCategoryOperator($objectCategory)
    {
        $categoryOperator = null;
        
        if ($objectCategory) {
            $categoryOperator = $this->filterBuilder->eq(self::ATTR_OBJECT_CATEGORY, $objectCategory);
        }
        
        return $categoryOperator;
    }

    /**
     * @param array $filter
     * @param BaseOperator|null $operator
     * @return BaseOperator
     */
    protected function getOperatorForArray(array $filter, BaseOperator $operator = null)
    {
        $filter = !empty($filter) ? $this->filterBuilder->bAnd(...$this->parseFilterToOperators($filter)) : null;
        
        if (!$filter && !$operator) {
            throw new InvalidArgumentException(sprintf(
                'Type "%s" for schema "%s" needs to have one of the following defined: objectClass, objectCategory, or filter.',
                $this->schema->getObjectType(),
                $this->schema->getSchemaName()
            ));
        } elseif ($filter && $operator) {
            $operator = $this->filterBuilder->bAnd($operator, $filter);
        } else {
            $operator = $operator ?: $filter;
        }
        
        return $operator;
    }

    /**
     * @param array $filter
     * @return BaseOperator[]
     */
    protected function parseFilterToOperators(array $filter)
    {
        $operators = [];
        
        foreach ($filter as $key => $value) {
            if (is_numeric($key) && is_array($value)) {
                foreach ($this->parseFilterToOperators($value) as $op) {
                    $operators[] = $op;
                }
            } else {
                $operators[] = $this->processOperatorAndArguments($key, $value);
            }
        }
        
        return $operators;
    }

    /**
     *
     * @param mixed $name
     * @param mixed $value
     * @return BaseOperator
     */
    protected function processOperatorAndArguments($name, $value)
    {
        $method = $this->getOperatorMethodName($name);
        
        if (in_array($method, $this->opMap)) {
            $operator = $this->filterBuilder->$method(...$this->parseFilterToOperators($value));
        } else {
            $value = is_array($value) ? $value : [$value];
            $operator = $this->filterBuilder->$method(...$value);
        }
        
        return $operator;
    }

    /**
     * @param $name
     * @return mixed|null
     */
    protected function getOperatorMethodName($name)
    {
        $method = null;
        $nameToCheck = str_replace('_', '', $name);
        
        foreach ($this->filterMethods as $methodName) {
            if (strtolower($methodName) == $nameToCheck) {
                $method = $methodName;
            }
        }
        if (!$method) {
            foreach ($this->opMap as $methodName => $mappedTo) {
                if ($methodName == $nameToCheck) {
                    $method = $mappedTo;
                }
            }
        }
        if (!$method) {
            throw new InvalidArgumentException(sprintf('Operator method "%s" is invalid', $name));
        }
        
        return $method;
    }
}
