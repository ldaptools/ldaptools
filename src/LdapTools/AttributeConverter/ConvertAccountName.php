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

/**
 * A sAMAccountName and Exchange Alias (mailNickname) have some specific character requirements. This attempts to deal
 * with it automatically when going to LDAP for a create/modify. Illegal characters are removed.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertAccountName implements AttributeConverterInterface
{
    use AttributeConverterTrait;

    /**
     * These characters cannot be used in sAMAccountNames or Exchange Aliases.
     */
    const ILLEGAL_CHARS = [
        '"',
        ',',
        '/',
        '\\',
        '[',
        ']',
        ':',
        '|',
        '<',
        '>',
        '+',
        '=',
        ';',
        '?',
        '*',
        ' ',
    ];

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
        if ($this->getOperationType() === self::TYPE_CREATE || $this->getOperationType() === self::TYPE_MODIFY) {
            // An ending period is also not allowed
            $value = rtrim(str_replace(self::ILLEGAL_CHARS, '', $value), '.');
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($value)
    {
        return $value;
    }
}
