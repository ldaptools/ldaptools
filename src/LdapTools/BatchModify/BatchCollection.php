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
     * @param Batch[] $batches
     */
    public function __construct($dn = null, Batch ...$batches)
    {
        $this->dn = $dn;
        $this->add(...$batches);
    }

    /**
     * Set the batches for the collecton.
     *
     * @param Batch[] ...$batches
     * @return $this
     */
    public function set(Batch ...$batches)
    {
        $this->batches = $batches;

        return $this;
    }

    /**
     * Add an individual batch action to the collection.
     *
     * @param Batch[] ...$batches
     * @return $this
     */
    public function add(Batch ...$batches)
    {
        foreach ($batches as $batch) {
            if (!$this->has($batch)) {
                $this->batches[] = $batch;
            }
        }

        return $this;
    }

    /**
     * Remove a specific batch from the collection.
     *
     * @param Batch[] ...$batches
     * @return $this
     */
    public function remove(Batch ...$batches)
    {
        foreach ($batches as $batch) {
            foreach ($this->batches as $i => $batchItem) {
                if ($batchItem === $batch) {
                    unset($this->batches[$i]);
                }
            }
        }

        return $this;
    }

    /**
     * Check if a specific batch index exists.
     *
     * @param Batch $batch
     * @return bool
     */
    public function has(Batch $batch)
    {
        foreach ($this->batches as $batchItem) {
            if ($batchItem === $batch) {
                return true;
            }
        }
        return false;
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
     * @return $this
     */
    public function setDn($dn)
    {
        $this->dn = $dn;

        return $this;
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
     * Allows this object to be iterated over.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->toArray());
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
}
