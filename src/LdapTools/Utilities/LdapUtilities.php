<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Utilities;

/**
 * Some common helper LDAP functions.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapUtilities
{
    /**
     * Regex to match a GUID.
     */
    const MATCH_GUID = '/^([0-9a-fA-F]){8}(-([0-9a-fA-F]){4}){3}-([0-9a-fA-F]){12}$/';

    /**
     * Regex to match a Windows SID.
     */
    const MATCH_SID = '/^S-\d-(\d+-){1,14}\d+$/i';

    /**
     * The prefix for a LDAP DNS SRV record.
     */
    const SRV_PREFIX = '_ldap._tcp.';

    /**
     * Escape any special characters for LDAP to their hexadecimal representation.
     *
     * @param mixed $value The value to escape.
     * @param null|string $ignore The characters to ignore.
     * @return string The escaped value.
     */
    public static function escapeValue($value, $ignore = null)
    {
        // If this is a hexadecimal escaped string, then do not escape it.
        return preg_match('/^(\\\[0-9a-fA-F]{2})+$/', (string) $value) ? $value : ldap_escape($value, $ignore);
    }

    /**
     * Un-escapes a value from its hexadecimal form back to its string representation.
     *
     * @param string $value
     * @return string
     */
    public static function unescapeValue($value)
    {
        $callback = function ($matches) {
            return chr(hexdec($matches[1]));
        };

        return preg_replace_callback('/\\\([0-9A-Fa-f]{2})/', $callback, $value);
    }

    /**
     * Converts a string distinguished name into its separate pieces.
     *
     * @param string $dn
     * @param int $withAttributes Set to 0 to get the attribute names along with the value.
     * @return array
     */
    public static function explodeDn($dn, $withAttributes = 1)
    {
        $pieces = ldap_explode_dn($dn, $withAttributes);

        if ($pieces === false || !isset($pieces['count']) || $pieces['count'] == 0) {
            throw new \InvalidArgumentException(sprintf('Unable to parse DN "%s".', $dn));
        }
        for ($i = 0; $i < $pieces['count']; $i++) {
            $pieces[$i] = self::unescapeValue($pieces[$i]);
        }
        unset($pieces['count']);

        return $pieces;
    }

    /**
     * Given a DN as an array in ['cn=Name', 'ou=Employees', 'dc=example', 'dc=com'] form, return it as its string
     * representation that is safe to pass back to a query or to save back to LDAP for a DN.
     *
     * @param array $dn
     * @return string
     */
    public static function implodeDn(array $dn)
    {
        foreach ($dn as $index => $piece) {
            $values = explode('=', $piece, 2);
            if (count($values) === 1) {
                throw new \InvalidArgumentException(sprintf('Unable to parse DN piece "%s".', $values[0]));
            }
            $dn[$index] = $values[0].'='.ldap_escape($values[1], null, LDAP_ESCAPE_DN);
        }

        return implode(',', $dn);
    }

    /**
     * Encode a string for LDAP with a specific encoding type.
     *
     * @param string $value The value to encode.
     * @param string $toEncoding The encoding type to use (ie. UTF-8)
     * @return string The encoded value.
     */
    public static function encode($value, $toEncoding)
    {
        // If the encoding is already UTF-8, and that's what was requested, then just send the value back.
        if ($toEncoding == 'UTF-8' && preg_match('//u', $value)) {
            return $value;
        }

        if (function_exists('mb_detect_encoding')) {
            $value = iconv(mb_detect_encoding($value, mb_detect_order(), true), $toEncoding, $value);
        } else {
            // How else to better handle if they don't have mb_* ? The below is definitely not an optimal solution.
            $value = utf8_encode($value);
        }

        return $value;
    }

    /**
     * Given a string, try to determine if it is a valid distinguished name for a LDAP object. This is a somewhat
     * unsophisticated approach. A regex might be a better solution, but would probably be rather difficult to get
     * right.
     *
     * @param string $dn
     * @return bool
     */
    public static function isValidLdapObjectDn($dn)
    {
        return (($pieces = ldap_explode_dn($dn, 1)) && isset($pieces['count']) && $pieces['count'] > 2);
    }

    /**
     * Get an array of all the LDAP servers for a domain by querying DNS.
     *
     * @param string $domain The domain name to query.
     * @return string[]
     */
    public static function getLdapServersForDomain($domain)
    {
        $hosts = (new Dns())->getRecord(self::SRV_PREFIX.$domain, DNS_SRV);

        return is_array($hosts) ? array_column($hosts, 'target') : [];
    }
}
