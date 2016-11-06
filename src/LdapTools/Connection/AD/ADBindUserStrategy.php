<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Connection\AD;

use LdapTools\Connection\BindUserStrategy;
use LdapTools\Utilities\LdapUtilities;

/**
 * Account for some of the various user object strings accepted by AD for a bind while still allowing for a sensible
 * default value.
 *
 * @see https://msdn.microsoft.com/en-us/library/cc223499.aspx
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ADBindUserStrategy extends BindUserStrategy
{
    /**
     * @var string The default bind format for AD.
     */
    protected $bindFormat = '%username%@%domainname%';

    /**
     * {@inheritdoc}
     */
    public function getUsername($username)
    {
        if (LdapUtilities::isValidGuid($username)) {
            $username = '{'.$username.'}';
        } elseif (!(LdapUtilities::isValidSid($username) || $this->isValidUserDn($username) || $this->isInUpnForm($username))) {
            $username = parent::getUsername($username);
        }

        return $username;
    }

    protected function isValidUserDn($dn)
    {
        return (($pieces = ldap_explode_dn($dn, 1)) && isset($pieces['count']) && $pieces['count'] > 2);
    }

    protected function isInUpnForm($username)
    {
        return strpos($username, '@') !== false;
    }
}
