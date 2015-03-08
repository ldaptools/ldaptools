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

use LdapTools\Utilities\LdapUtilities;

/**
 * Takes a plain-string password and converts it to a UTF-16 encoded unicode string containing the password surrounded
 * by quotation marks. Additionally, this is only ever going to be a toLdap() conversion as AD will never return the
 * unicodePwd attribute from a search. The fromLdap() is here simply to conform to the interface.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class EncodeWindowsPassword implements AttributeConverterInterface
{
    use AttributeConverterTrait;

    /**
     * {@inheritdoc}
     */
    public function toLdap($password)
    {
        $password = is_null($this->getLdapConnection()) ? $password : LdapUtilities::encode($password, $this->getLdapConnection()->getEncoding());

        return iconv("UTF-8", "UTF-16LE", '"'.$password.'"');
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($password)
    {
    }
}
