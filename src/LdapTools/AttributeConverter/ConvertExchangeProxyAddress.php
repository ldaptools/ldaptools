<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\AttributeConverter;

use LdapTools\BatchModify\Batch;
use LdapTools\Utilities\ConverterUtilitiesTrait;
use LdapTools\Utilities\MBString;

/**
 * Converts the Exchange proxyAddress attribute by keeping a type map and transforming the address with the needed
 * prefix.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertExchangeProxyAddress implements AttributeConverterInterface
{
    use ConverterUtilitiesTrait, AttributeConverterTrait;

    /**
     * @var array
     */
    protected $options = [
        # The address type to filter from the proxy addresses
        'address_type' => 'smtp',
        # Whether or not to the default/primary of the address type is being worked with
        'is_default' => false,
    ];

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
        $this->setDefaultLastValue('proxyAddresses', []);

        $this->modifyAddressArray($value);
        if ($this->getOperationType() == self::TYPE_MODIFY) {
            $this->getBatch()->setModType(Batch::TYPE['REPLACE']);
        }

        return $this->getLastValue();
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($value)
    {
        if ($this->options['is_default']) {
            return $this->getDefaultAddressByType($value);
        } else {
            return $this->getAddressesByType($value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isMultiValuedConverter()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getShouldAggregateValues()
    {
        return ($this->getOperationType() == self::TYPE_MODIFY || $this->getOperationType() == self::TYPE_CREATE);
    }

    /**
     * Get all the addresses of a specific type from a standard proxyAddress array.
     *
     * @param array $proxyAddresses
     * @return array
     */
    protected function getAddressesByType(array $proxyAddresses)
    {
        $addresses = [];

        foreach ($proxyAddresses as $address) {
            if (preg_match('/^' . $this->options['address_type'] . ':(.*)$/i', $address, $matches)) {
                $addresses[] = $matches[1];
            }
        }

        return $addresses;
    }

    /**
     * Get the default email for a specific address type.
     *
     * @param array $proxyAddresses
     * @return array
     */
    protected function getDefaultAddressByType(array $proxyAddresses)
    {
        $matches = preg_grep('/^'.strtoupper($this->options['address_type']).':(.*)$/', $proxyAddresses);

        $defaultAddress = '';
        if (count($matches) !== 0) {
            $defaultAddress = [substr_replace(
                reset($matches),
                '',
                0,
                (strlen($this->options['address_type']) + 1)
            )];
        }

        return $defaultAddress;
    }

    /**
     * Formats the addresses then modifies the last value based on the operation type.
     *
     * @param array $addresses
     */
    protected function modifyAddressArray(array $addresses)
    {
        $addresses = $this->formatAddresses($addresses);

        if ($this->options['is_default']) {
            $this->modifyDefaultAddress(reset($addresses));
        } else {
            $this->modifyAddresses($addresses);
        }
    }

    /**
     * Given an array of email address determine what type they should be and prefix the email addresses with it.
     *
     * @param array $emailAddresses
     * @return array
     */
    protected function formatAddresses(array $emailAddresses)
    {
        foreach ($emailAddresses as $index => $emailAddress) {
            $addressType = $this->options['address_type'];
            $addressPrefix = $this->options['is_default'] ? strtoupper($addressType) . ':' : strtolower($addressType) . ':';
            $emailAddresses[$index] = $addressPrefix . $emailAddress;
        }

        return $emailAddresses;
    }

    /**
     * Modifies an array of generic address types.
     *
     * @param array $addresses
     */
    protected function modifyAddresses(array $addresses)
    {
        $values = is_array($this->getLastValue()) ? $this->getLastValue() : [$this->getLastValue()];

        if ($this->getOperationType() == self::TYPE_CREATE || ($this->getBatch() && $this->getBatch()->isTypeAdd())) {
            $values = array_merge($values, $addresses);
        } elseif ($this->getBatch() && $this->getBatch()->isTypeReplace()) {
            $values = $this->replaceAddressesOfType($values, $addresses);
        } elseif ($this->getBatch() && $this->getBatch()->isTypeRemove()) {
            $values = array_diff($values, $addresses);
        }

        $this->setLastValue($values);
    }

    /**
     * Modifies the existing list of addresses to set the default for a specific address type.
     *
     * @param string $defaultAddress
     */
    protected function modifyDefaultAddress($defaultAddress)
    {
        $values = is_array($this->getLastValue()) ? $this->getLastValue() : [$this->getLastValue()];
        $isAddressInArray = in_array(MBString::strtolower($defaultAddress), MBString::array_change_value_case($values));

        $length = strlen($this->options['address_type']);
        foreach ($values as $index => $address) {
            // If another address is already the default then it must be changed.
            if ((substr($address, 0, $length) === strtoupper($this->options['address_type'])) && ($address !== $defaultAddress)) {
                $values[$index] = substr_replace($address, $this->options['address_type'], 0, $length);
            // If the address is the one we are looking for but is not the default, then make it the default.
            } elseif ($isAddressInArray && (MBString::strtolower($address) === MBString::strtolower($defaultAddress))) {
                $values[$index] = $defaultAddress;
            }
        }

        // It was not already an address in the array, and the other default would have been changed now.
        if (!in_array($defaultAddress, $values)) {
            $values[] = $defaultAddress;
        }

        $this->setLastValue($values);
    }

    /**
     * Remove all addresses of a specific type and replace them with a specific set of addresses.
     *
     * @param array $addresses
     * @param array $replaceWith
     * @return array
     */
    protected function replaceAddressesOfType(array $addresses, array $replaceWith)
    {
        $addresses = preg_grep('/^'.$this->options['address_type'].':(.*)$/i', $addresses, PREG_GREP_INVERT);

        return array_merge($addresses, $replaceWith);
    }
}
