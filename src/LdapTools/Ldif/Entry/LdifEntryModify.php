<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Ldif\Entry;

use LdapTools\AttributeConverter\AttributeConverterInterface;
use LdapTools\BatchModify\Batch;
use LdapTools\BatchModify\BatchCollection;
use LdapTools\Connection\LdapAwareInterface;
use LdapTools\Connection\LdapAwareTrait;
use LdapTools\Hydrator\OperationHydrator;
use LdapTools\Ldif\Ldif;
use LdapTools\Operation\BatchModifyOperation;
use LdapTools\Schema\SchemaAwareInterface;
use LdapTools\Schema\SchemaAwareTrait;

/**
 * Represents a LDIF entry to modify an existing LDAP object.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdifEntryModify implements LdifEntryInterface, SchemaAwareInterface, LdapAwareInterface
{
    use LdifEntryTrait,
        SchemaAwareTrait,
        LdapAwareTrait;

    /**
     * Directive to delete an attribute's value.
     */
    const DIRECTIVE_DELETE = 'delete';

    /**
     * Directive to add a new value for an attribute.
     */
    const DIRECTIVE_ADD = 'add';

    /**
     * Directive to replace the existing value of an attribute.
     */
    const DIRECTIVE_REPLACE ='replace';

    /**
     * Indicates the end of a specific modification action.
     */
    const SEPARATOR = '-';

    /**
     * @var BatchCollection
     */
    protected $batches;

    /**
     * @var array Map the batch object types to the directives they relate to as a LDIF entry.
     */
    protected $batchMap = [
        Batch::TYPE['ADD'] => self::DIRECTIVE_ADD,
        Batch::TYPE['REMOVE_ALL'] => self::DIRECTIVE_DELETE,
        Batch::TYPE['REMOVE'] => self::DIRECTIVE_DELETE,
        Batch::TYPE['REPLACE'] => self::DIRECTIVE_REPLACE,
    ];

    /**
     * @param string $dn
     */
    public function __construct($dn)
    {
        $this->dn = $dn;
        $this->changeType = self::TYPE_MODIFY;
        $this->batches = new BatchCollection($this->dn);
    }

    /**
     * Add a value to the attribute for the LDAP object.
     *
     * @param $attribute
     * @param $value
     * @return $this
     */
    public function add($attribute, $value)
    {
        $this->batches->add(new Batch(Batch::TYPE['ADD'], $attribute, $value));

        return $this;
    }

    /**
     * Delete a specific attribute value.
     *
     * @param string $attribute
     * @param string $value
     * @return $this
     */
    public function delete($attribute, $value)
    {
        $this->batches->add(new Batch(Batch::TYPE['REMOVE'], $attribute, $value));

        return $this;
    }

    /**
     * Replace the current attribute value.
     *
     * @param string $attribute
     * @param string|array $value
     * @return $this
     */
    public function replace($attribute, $value)
    {
        $this->batches->add(new Batch(Batch::TYPE['REPLACE'], $attribute, $value));

        return $this;
    }

    /**
     * Reset a specific attribute. This removes any value(s) it might have.
     *
     * @param string $attribute
     * @return $this
     */
    public function reset($attribute)
    {
        $this->batches->add(new Batch(Batch::TYPE['REMOVE_ALL'], $attribute));

        return $this;
    }

    /**
     * Get the BatchCollection containing all the batches represented by this entry.
     *
     * @return BatchCollection
     */
    public function getBatchCollection()
    {
        return $this->batches;
    }

    /**
     * {@inheritdoc}
     */
    public function toOperation()
    {
        $hydrator = new OperationHydrator();
        $hydrator->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $operation = new BatchModifyOperation($this->dn, clone $this->batches);

        return $this->hydrateOperation($hydrator, $operation);
    }

    /**
     * {@inheritdoc}
     */
    public function toString()
    {
        /** @var BatchModifyOperation $operation */
        $operation = $this->toOperation();
        $ldif = $this->getCommonString();

        $this->unicodePwdHack($operation);
        /** @var Batch $batch */
        foreach ($operation->getBatchCollection() as $batch) {
            $ldif .= $this->getLdifLine($this->batchMap[$batch->getModType()], $batch->getAttribute());
            // Nothing else to do in this case, as a reset directive is a delete with no attributes/values afterward.
            if (!$batch->isTypeRemoveAll()) {
                foreach ($batch->getValues() as $value) {
                    $ldif .= $this->getLdifLine($batch->getAttribute(), $value);
                }
            }
            $ldif .= self::SEPARATOR.Ldif::ENTRY_SEPARATOR;
        }

        return $ldif;
    }

    /**
     * Workaround AD special cases with the unicodePwd attribute...
     *
     * @link https://support.microsoft.com/en-us/kb/263991
     * @param BatchModifyOperation $operation
     */
    protected function unicodePwdHack(BatchModifyOperation $operation)
    {
        if (!$this->isUnicodePwdHackNeeded()) {
            return;
        }

        foreach ($operation->getBatchCollection() as $batch) {
            if (strtolower($batch->getAttribute()) !== 'unicodepwd') {
                continue;
            }
            $values = $batch->getValues();
            $batch->setValues(base64_encode(reset($values)));
        }
    }
}
