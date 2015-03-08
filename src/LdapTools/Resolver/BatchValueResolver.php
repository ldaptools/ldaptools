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
use LdapTools\BatchModify\Batch;
use LdapTools\BatchModify\BatchCollection;
use LdapTools\Schema\LdapObjectSchema;

/**
 * Parses through a BatchCollection to resolve, modify, and remove values based on the attributes and converters used.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class BatchValueResolver extends BaseValueResolver
{
    /**
     * @var BatchCollection that contains all the batches to process.
     */
    protected $batches;

    /**
     * @var int The current batch index number being processed.
     */
    protected $currentBatchIndex = 0;

    /**
     * @param LdapObjectSchema $schema
     * @param BatchCollection $batches
     * @param int $type The LDAP operation type. See AttributeConverterInterface::TYPE_*.
     */
    public function __construct(LdapObjectSchema $schema, BatchCollection $batches, $type)
    {
        parent::__construct($schema, $type);
        $this->batches = $batches;
    }

    /**
     * Convert the batch values to LDAP batch mod specifications array.
     *
     * @return BatchCollection
     */
    public function toLdap()
    {
        foreach ($this->batches as $index => $batch) {
            /** @var Batch $batch */
            if (!$this->batches->has($index) || $batch->isTypeRemoveAll()) {
                continue;
            } elseif (!$this->schema->hasConverter($batch->getAttribute())) {
                $batch->setValues($this->encodeValues($batch->getValues()));
            } else {
                $this->currentBatchIndex = $index;
                $batch->setValues($this->getConvertedValues($batch->getValues(), $batch->getAttribute(), 'toLdap'));

                if (in_array($batch->getAttribute(), $this->aggregated)) {
                    $batch->setAttribute($this->schema->getAttributeToLdap($batch->getAttribute()));
                }
            }
        }

        return $this->batches;
    }

    /**
     * Determine if a specific batch is correctly formatted and needs conversion.
     *
     * @param Batch $batch
     * @return bool
     */
    protected function batchCanConvert(Batch $batch)
    {
        return ($this->schema->hasConverter($batch->getAttribute()) && !$batch->isTypeRemoveAll());
    }

    /**
     * {@inheritdoc}
     */
    protected function iterateAggregates(array $toAggregate, $values, AttributeConverterInterface $converter)
    {
        $batches = $this->getBatchesForAttributes($toAggregate);

        foreach ($batches as $index => $batch) {
            /** @var Batch $batch */
            $this->validateBatchAggregate($batch, $converter);
            $converter->setBatch($batch);
            $values = $this->getConvertedValues($batch->getValues(), $batch->getAttribute(), 'toLdap', $converter);
            $converter->setLastValue($values);
            if ($index !== $this->currentBatchIndex) {
                $this->batches->remove($index);
            }
        }

        return $values;
    }

    /**
     * Given an array of attribute names, get all of the batches they have with their respective indexes
     *
     * @param array $attributes
     * @return array
     */
    protected function getBatchesForAttributes(array $attributes)
    {
        $batches = [];

        foreach ($this->batches as $index => $batch) {
            /** @var Batch $batch */
            if (in_array(strtolower($batch->getAttribute()), array_map('strtolower', $attributes))) {
                $batches[$index] = $batch;
            }
        }

        return $batches;
    }

    /**
     * When aggregating a set of values they need to be modified with 'set'. The other methods ('reset', 'add', or
     * 'remove') are not valid. Additionally, all batch modification array keys should be present.
     *
     * @param Batch $batch
     * @param AttributeConverterInterface $converter
     */
    protected function validateBatchAggregate(Batch $batch, AttributeConverterInterface $converter)
    {
        if (!$converter->isBatchSupported($batch)) {
            throw new \LogicException(sprintf(
                'Unable to modify "%s". The "%s" action is not allowed.',
                $batch->getAttribute(),
                array_search($batch->getModType(), Batch::TYPE)
            ));
        }
    }
}
