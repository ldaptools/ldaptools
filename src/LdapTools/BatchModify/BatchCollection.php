<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\BatchModify;

use LdapTools\Exception\InvalidArgumentException;

/**
 * Represents a collection of batch statements to be sent to LDAP.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class BatchCollection implements \IteratorAggregate
{
    /**
     * @var Batch[]
     */
    protected $batches = [];

    /**
     * @var string|null The distinguished name of the LDAP object these batches will target.
     */
    protected $dn;

    /**
     * @param string|null $dn
     */
    public function __construct($dn = null)
    {
        $this->dn = $dn;
    }

    /**
     * Allows this object to be iterated over.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->toArray());
    }

    /**
     * Add an individual batch action to the collection.
     *
     * @param Batch $batch
     */
    public function add(Batch $batch)
    {
        $this->batches[] = $batch;
    }

    /**
     * Get an array containing all the batch action objects.
     *
     * @return Batch[]
     */
    public function toArray()
    {
        return $this->batches;
    }

    /**
     * Get an array that represents the array form of each batch action to be sent to LDAP.
     *
     * @return array
     */
    public function getBatchArray()
    {
        $batchEntry = [];

        foreach ($this->batches as $batch) {
            $batchEntry[] = $batch->toArray();
        }

        return $batchEntry;
    }

    /**
     * Get a specific batch from the collection by its index number in the array.
     *
     * @param int $index
     * @return Batch
     */
    public function get($index)
    {
        $this->validateBatchIndexExists($index);

        return $this->batches[$index];
    }

    /**
     * Remove a specific batch from the collection by its index number in the array.
     *
     * @param int $index
     * @return Batch
     */
    public function remove($index)
    {
        $this->validateBatchIndexExists($index);

        unset($this->batches[$index]);
    }

    /**
     * Check if a specific batch index exists.
     *
     * @param int $index
     * @return bool
     */
    public function has($index)
    {
        return isset($this->batches[$index]);
    }

    /**
     * Get the distinguished name of the LDAP object this batch will target.
     *
     * @return string
     */
    public function getDn()
    {
        return $this->dn;
    }

    /**
     * Set the distinguished name of the LDAP object this batch will target.
     *
     * @param string $dn
     */
    public function setDn($dn)
    {
        $this->dn = $dn;
    }

    /**
     * When a batch collection is cloned, we want to make sure the batch objects are cloned as well.
     */
    public function __clone()
    {
        foreach ($this->batches as $i =>$batch) {
            $this->batches[$i] = clone $batch;
        }
    }

    /**
     * Checks to make sure that the index actually exists.
     *
     * @param int $index
     */
    protected function validateBatchIndexExists($index)
    {
        if (!isset($this->batches[$index])) {
            throw new InvalidArgumentException(sprintf('Batch index "%s" does not exist.', $index));
        }
    }
}
