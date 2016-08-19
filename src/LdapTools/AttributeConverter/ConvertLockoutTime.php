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

use LdapTools\Exception\AttributeConverterException;
use LdapTools\Query\Builder\FilterBuilder;
use LdapTools\Utilities\MBString;

/**
 * Converts the lockoutTime to either a bool or a DateTime object.
 *
 * @todo For this to be more accurate it needs to take into account the lockout duration.
 * @see https://msdn.microsoft.com/en-us/library/ms676843%28v=vs.85%29.aspx
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertLockoutTime implements AttributeConverterInterface
{
    use AttributeConverterTrait;

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
        if ($this->getOperationType() == AttributeConverterInterface::TYPE_SEARCH_TO && $this->expectsBool()) {
            $value = $this->getQueryValue($value);
        } elseif ($this->expectsBool()) {
            $value = $this->getUnlockValue($value);
        } else {
            $value = $this->getLockDateTime($value);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($value)
    {
        $fromLdap = ($value >= '1');
        if (!$this->expectsBool() && $fromLdap) {
            $fromLdap = (new ConvertWindowsTime())->fromLdap($value);
        }

        return $fromLdap;
    }

    /**
     * @param bool $value
     * @return \LdapTools\Query\Operator\bOr|\LdapTools\Query\Operator\Comparison
     */
    protected function getQueryValue($value)
    {
        $fb = new FilterBuilder();

        return $value ? $fb->gte('lockoutTime', '1') : $fb->bOr($fb->notPresent('lockoutTime'), $fb->eq('lockoutTime', '0'));
    }

    /**
     * @return bool
     */
    protected function expectsBool()
    {
        return MBString::strtolower($this->getOptions()['bool']) == MBString::strtolower($this->getAttribute());
    }

    /**
     * @param bool $value
     * @return string
     * @throws AttributeConverterException
     */
    protected function getUnlockValue($value)
    {
        if ($value) {
            throw new AttributeConverterException('An account can only be unlocked, not locked.');
        }

        return '0';
    }

    /**
     * @param \DateTime $value
     * @return string
     * @throws AttributeConverterException
     */
    protected function getLockDateTime($value)
    {
        if (!($value instanceof \DateTime)) {
            throw new AttributeConverterException(sprintf(
                'The value for %s is expected to be a \DateTime object.',
                $this->getAttribute()
            ));
        }

        return (new ConvertWindowsTime())->toLdap($value);
    }
}
