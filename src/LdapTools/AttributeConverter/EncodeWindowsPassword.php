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

use LdapTools\Exception\LdapConnectionException;
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
        $this->validateConfiguration();
        if (!is_null($this->getLdapConnection())) {
            $password = LdapUtilities::encode(
                $password,
                $this->getLdapConnection()->getConfig()->getEncoding()
            );
        }

        return iconv("UTF-8", "UTF-16LE", '"'.$password.'"');
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($password)
    {
    }

    /**
     * AD requires SSL/TLS by default to modify the unicodePwd attribute. This is probably the source of a lot of
     * confusion when trying to create/modify a user and sending a password across. The default error from LDAP is
     * not very helpful. This at least makes it clear what the problem is. However, it is possible for someone to disable
     * the requirement for AD to require SSL/TLS for password modifications. But I cannot imagine that being a common
     * change.
     *
     * @throws LdapConnectionException
     */
    protected function validateConfiguration()
    {
        if (!$this->getLdapConnection()) {
            return;
        }
        $config = $this->getLdapConnection()->getConfig();

        if (!($config->getUseTls() || $config->getUseSsl())) {
            throw new LdapConnectionException(
                'To send a password to AD you need to enable either TLS or SSL in your configuration.'
            );
        }
    }
}
