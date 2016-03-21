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
use LdapTools\Schema\LdapObjectSchema;

/**
 * Resolves attribute values in ['attribute' => $value] form to the values LDAP/PHP expects.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class AttributeValueResolver extends BaseValueResolver
{
    /**
     * The LDAP entry in [ 'attribute' => 'value' ] form.
     *
     * @var array
     */
    protected $entry = [];

    /**
     * @var array If the attribute was converted using an aggregate it will be placed here so it can be skipped.
     */
    protected $converted = [];

    /**
     * @param LdapObjectSchema $schema
     * @param array $entry The [ attribute => value ] entries.
     * @param int $type The LDAP operation type. See AttributeConverterInterface::TYPE_*.
     */
    public function __construct(LdapObjectSchema $schema, array $entry, $type)
    {
        parent::__construct($schema, $type);
        $this->entry = $entry;
    }

    /**
     * Convert values from LDAP.
     *
     * @return array
     */
    public function fromLdap()
    {
        $entry = $this->convert($this->entry, false);

        foreach ($entry as $attribute => $value) {
            if ($this->schema->isMultivaluedAttribute($attribute) && !is_array($value)) {
                $entry[$attribute] = [$value];
            }
        }

        return $entry;
    }

    /**
     * Convert values to LDAP.
     *
     * @return array
     */
    public function toLdap()
    {
        return $this->convert($this->entry);
    }

    /**
     * Perform the attribute conversion process.
     *
     * @param array $attributes
     * @param bool $toLdap
     * @return array
     */
    protected function convert(array $attributes, $toLdap = true)
    {
        $direction = $toLdap ? 'toLdap' : 'fromLdap';

        foreach ($attributes as $attribute => $values) {
            // No converter, but the value should still be encoded.
            if (!$this->schema->hasConverter($attribute) && !isset($this->converted[$attribute])) {
                $attributes[$attribute] = $this->encodeValues($values);
            // Only continue if it has a converter and has not already been converted.
            } elseif ($this->schema->hasConverter($attribute) && !isset($this->converted[$attribute])) {
                $values = $this->getConvertedValues($values, $attribute, $direction);
                if (in_array($attribute, $this->aggregated)) {
                    $attribute = $this->schema->getAttributeToLdap($attribute);
                }
                $attributes[$attribute] = (count($values) == 1) ? reset($values) : $values;
            }
        }

        return $this->removeValuesFromEntry($attributes, array_merge($this->aggregated, $this->remove));
    }

    /**
     * Cleans up the entry/batch array by removing any values that are specified.
     *
     * @param array $entry
     * @param array $values
     * @return array
     */
    protected function removeValuesFromEntry(array $entry, $values)
    {
        foreach ($values as $value) {
            if (isset($entry[$value])) {
                unset($entry[$value]);
            }
        }

        return $entry;
    }

    /**
     * {@inheritdoc}
     */
    protected function iterateAggregates(array $toAggregate, $values, AttributeConverterInterface $converter)
    {
        foreach ($toAggregate as $aggregate) {
            if (isset($this->entry[$aggregate])) {
                $values = $this->getConvertedValues($this->entry[$aggregate], $aggregate, 'toLdap', $converter);
                $converter->setLastValue($values);
                $this->converted[] = $aggregate;
            }
        }

        return $values;
    }
}
