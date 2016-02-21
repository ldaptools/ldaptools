<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Hydrator;

use LdapTools\AttributeConverter\AttributeConverterInterface;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Factory\AttributeConverterFactory;
use LdapTools\Query\Operator\bAnd;
use LdapTools\Query\Operator\BaseOperator;
use LdapTools\Query\Operator\ContainsOperatorsInterface;
use LdapTools\Query\OperatorCollection;
use LdapTools\Schema\LdapObjectSchema;

/**
 * Converts attributes and values for all query operators and retrieves the LDAP filter.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class OperatorCollectionHydrator
{
    /**
     * @var null|LdapConnectionInterface
     */
    protected $connection;

    /**
     * @var null|LdapObjectSchema
     */
    protected $schema;

    /**
     * @var array The converter options array.
     */
    protected $converterOpts = [];

    /**
     * @param null|LdapConnectionInterface $connection
     */
    public function __construct(LdapConnectionInterface $connection = null)
    {
        $this->connection = $connection;
    }

    /**
     * Apply any value or attribute conversion to the OperatorCollection and return the resulting LDAP Filter.
     *
     * @param OperatorCollection $collection
     * @return string
     */
    public function toLdapFilter(OperatorCollection $collection)
    {
        $this->addSchemaFromCollection($collection);

        if (!empty($this->schema)) {
            foreach ($collection as $operator) {
                $this->processOperator($operator);
            }
        }

        return $collection->toLdapFilter();
    }

    /**
     * Convert the values on an operator to what LDAP expects them to be.
     *
     * @param BaseOperator $operator
     */
    protected function convertValues(BaseOperator $operator)
    {
        if (!$this->schema->hasConverter($operator->getAttribute()) || !$operator->getUseConverter()) {
            return;
        }
        $converter = $this->getBuiltConverter($operator->getAttribute());
        $operator->setConvertedValue($converter->toLdap($operator->getValue()));
        $operator->setWasConverterUsed(true);
    }

    /**
     * Convert the attribute name to what LDAP expects it to be.
     *
     * @param BaseOperator $operator
     */
    protected function convertAttributeName(BaseOperator $operator)
    {
        $operator->setTranslatedAttribute($this->schema->getAttributeToLdap($operator->getAttribute()));
    }

    /**
     * Construct the attribute converter for this context.
     *
     * @param string $attribute
     * @return AttributeConverterInterface
     */
    protected function getBuiltConverter($attribute)
    {
        $name = $this->schema->getConverter($attribute);
        $converter = AttributeConverterFactory::get($name);

        if (isset($this->converterOpts[$name])) {
            $converter->setOptions($this->converterOpts[$name]);
        }
        if (!is_null($this->connection)) {
            $converter->setLdapConnection($this->connection);
        }
        $converter->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);
        $converter->setAttribute($attribute);

        return $converter;
    }

    /**
     * Checks the OperatorCollection for any schema objects and gets the converter options.
     *
     * @param OperatorCollection $collection
     */
    protected function addSchemaFromCollection(OperatorCollection $collection)
    {
        $schemas = $collection->getLdapObjectSchemas();

        if (!empty($schemas)) {
            $this->schema = reset($schemas);
            $this->converterOpts = $this->schema->getConverterOptions();
        }
    }

    /**
     * Processes an operator data conversion and loops through children operators if needed.
     *
     * @param BaseOperator $operator
     */
    protected function processOperator(BaseOperator $operator)
    {
        if ($operator instanceof ContainsOperatorsInterface) {
            foreach ($operator->getChildren() as $childOperator) {
                $this->processOperator($childOperator);
            }
        } else {
            $this->convertValues($operator);
            $this->convertAttributeName($operator);
        }
    }
}
