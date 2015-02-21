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
 * Converts Windows Generalized Time format to/from a \DateTime object.
 *
 * @see https://msdn.microsoft.com/en-us/library/aa772189%28v=vs.85%29.aspx
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertWindowsGeneralizedTime extends ConvertGeneralizedTime
{
    /**
     * {@inheritdoc}
     */
    protected function getTzOffsetForTimestamp($tzOffset)
    {
        return '.0'.parent::getTzOffsetForTimestamp($tzOffset);
    }
}
