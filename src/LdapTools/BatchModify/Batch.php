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
 * Represents a batch action.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class Batch
{
    /**
     * An array of valid batch types.
     */
    const TYPE = [
        'ADD' => LDAP_MODIFY_BATCH_ADD,
        'REMOVE' => LDAP_MODIFY_BATCH_REMOVE,
        'REMOVE_ALL' => LDAP_MODIFY_BATCH_REMOVE_ALL,
        'REPLACE' => LDAP_MODIFY_BATCH_REPLACE,
    ];

    /**
     * @var string The attribute name the batch action will modify.
     */
    protected $attribute;

    /**
     * @var array The values for the batch action.
     */
    protected $values;

    /**
     * @var int The batch modification type to perform.
     */
    protected $modtype;

    /**
     * @param int $modtype
     * @param string $attribute
     * @param array $values
     */
    public function __construct($modtype, $attribute, $values = [])
    {
        $this->setModType($modtype);
        $this->setValues($values);
        $this->attribute = $attribute;
    }

    /**
     * Get the array representation of this batch action.
     *
     * @return array
     */
    public function toArray()
    {
        $batch = [
            'attrib' => $this->attribute,
            'modtype' => $this->modtype,
        ];
        if (!($this->modtype === LDAP_MODIFY_BATCH_REMOVE_ALL)) {
            $batch['values'] = $this->resolve($this->values);
        }

        return $batch;
    }

    /**
     * Get the values for this batch action.
     *
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * Set the values for this batch.
     *
     * @param $values
     */
    public function setValues($values)
    {
        $this->values = is_array($values) ? $values : [$values];
    }

    /**
     * Get the attribute for this batch.
     *
     * @return string
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * Set the attribute for this batch.
     *
     * @param string $attribute
     */
    public function setAttribute($attribute)
    {
        $this->attribute = $attribute;
    }

    /**
     * Get the modtype for this batch.
     *
     * @return int
     */
    public function getModType()
    {
        return $this->modtype;
    }

    /**
     * Set the modtype for this batch.
     *
     * @param int $modtype
     */
    public function setModType($modtype)
    {
        if (!in_array($modtype, self::TYPE)) {
            throw new InvalidArgumentException(sprintf('Invalid batch action type: %s', $modtype));
        }
        $this->modtype = $modtype;
    }

    /**
     * A convenience function to check if this batch type is a REMOVE action.
     *
     * @return bool
     */
    public function isTypeRemove()
    {
        return $this->isType('REMOVE');
    }

    /**
     * A convenience function to check if this batch type is a REMOVE_ALL action.
     *
     * @return bool
     */
    public function isTypeRemoveAll()
    {
        return $this->isType('REMOVE_ALL');
    }

    /**
     * A convenience function to check if this batch type is a REPLACE action.
     *
     * @return bool
     */
    public function isTypeReplace()
    {
        return $this->isType('REPLACE');
    }

    /**
     * A convenience function to check if this batch type is an ADD action.
     *
     * @return bool
     */
    public function isTypeAdd()
    {
        return $this->isType('ADD');
    }

    /**
     * Checks of this is a specific modtype.
     *
     * @param string $type
     * @return bool
     */
    protected function isType($type)
    {
        return ($this->modtype === self::TYPE[$type]);
    }

    /**
     * Allows for an anonymous function to produce the final value.
     *
     * @param array $values
     * @return array
     */
    protected function resolve(array $values)
    {
        foreach ($values as $i => $value) {
            if ($value instanceof \Closure) {
                $values[$i] = call_user_func($value);
            }
        }
        
        return $values;
    }
}
