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
 * Converts a string to UTF-8 for LDAP. Requires the mbstring and iconv extensions, or falls back to the utf8_encode
 * function. Perhaps there is a better way to handle this?
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertStringToUtf8 implements AttributeConverterInterface
{
    use AttributeConverterTrait;

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
        // First test if it is not UTF-8.
        if (!preg_match('//u', $value)) {
            if (function_exists('mb_detect_encoding')) {
                $value = iconv(mb_detect_encoding($value, mb_detect_order(), true), "UTF-8", $value);
            } else {
                // How else to better handle if they don't have mb_* ? The below is definitely not an optimal solution.
                $value = utf8_encode($value);
            }
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
