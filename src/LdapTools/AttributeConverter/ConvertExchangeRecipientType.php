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

use LdapTools\Connection\AD\ExchangeRecipient;
use LdapTools\Exception\AttributeConverterException;
use LdapTools\Utilities\ConverterUtilitiesTrait;

/**
 * Converts the Exchange Recipient type data into a readable value.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertExchangeRecipientType implements AttributeConverterInterface
{
    use AttributeConverterTrait, ConverterUtilitiesTrait;

    protected $recipientType = [
        'type_details' => ExchangeRecipient::TYPE_DETAILS,
        'display_type' => ExchangeRecipient::DISPLAY_TYPE,
    ];

    public function __construct()
    {
        $this->setOptions([
            'recipientTypeDetails' => 'type_details',
            'recipientDisplayType' => 'display_type',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
        $recipientInfo = $this->getExchangeRecipientArray();
        $lcRecipientInfo = array_change_key_case($recipientInfo);

        if (!array_key_exists(strtolower($value), $lcRecipientInfo)) {
            throw new AttributeConverterException(sprintf(
                'Exchange Recipient value "%s" is not recognized for "%s"',
                $value,
                $this->getAttribute()
            ));
        }

        return (string) $lcRecipientInfo[strtolower($value)];
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($value)
    {
        $name = array_search($value, $this->getExchangeRecipientArray());

        return $name === false ? 'Unknown' : $name;
    }

    /**
     * @return array
     * @throws AttributeConverterException
     */
    protected function getExchangeRecipientArray()
    {
        $this->validateCurrentAttribute($this->getOptions());
        $type = $this->getArrayValue($this->getOptions(), $this->getAttribute());

        if (!array_key_exists(strtolower($type), $this->recipientType)) {
            throw new AttributeConverterException(sprintf(
                'Recipient display type "%s" for "%s" is not recognized.',
                $type,
                $this->getAttribute()
            ));
        }

        return $this->recipientType[strtolower($type)];
    }
}
