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

use Enums\SimpleEnumInterface;
use LdapTools\Exception\AttributeConverterException;
use LdapTools\Utilities\ConverterUtilitiesTrait;

/**
 * Converts simple enum names to/from LDAP values.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertEnum implements AttributeConverterInterface
{
    use AttributeConverterTrait,
        ConverterUtilitiesTrait;

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
        $enum = $this->getEnum();

        if (is_object($value) && is_subclass_of($value, $enum)) {
            /** @var SimpleEnumInterface $value */
            $value = $value->getValue();
        } else {
            $value = call_user_func($enum.'::getNameValue', $value);
        }

        return (string) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($value)
    {
        $enum = $this->getEnum();

        // Only if the value maps to a name. Lots of possible reasons something could go wrong here, so better safe
        // than throwing an exception on a query from LDAP.
        if (call_user_func($enum.'::isValidValue', $value)) {
            $value = call_user_func($enum.'::getValueName', $value);
        }

        return $value;
    }

    /**
     * @return string
     * @throws AttributeConverterException
     */
    protected function getEnum()
    {
        $this->validateCurrentAttribute($this->options);
        $opts = $this->getArrayValue($this->options, $this->getAttribute());

        if (!isset($opts['enum'])) {
            throw new AttributeConverterException(sprintf(
                'You must set the enum option for the "%s" attribute',
                $this->getAttribute()
            ));
        }
        if (!is_subclass_of($opts['enum'], SimpleEnumInterface::class)) {
            throw new AttributeConverterException(sprintf(
                'The enum class "%s" for "%s" must be an instance of "%s"',
                $opts['enum'],
                $this->getAttribute(),
                SimpleEnumInterface::class
            ));
        }

        return $opts['enum'];
    }
}
