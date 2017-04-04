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

use LdapTools\Exception\InvalidArgumentException;
use Ramsey\Uuid\Uuid;

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
    const MATCH_SID = '/^S-\d(-\d{1,10}){1,16}$/i';

    /**
     * Regex to match an OID.
     */
    const MATCH_OID = '/^[0-9]+(\.[0-9]+?)*?$/';

    /**
     * Regex to match an attribute descriptor.
     */
    const MATCH_DESCRIPTOR = '/^\pL([\pL\pN-]+)?$/iu';

    /**
     * Regex to match an Exchange Legacy DN.
     */
    const MATCH_LEGACY_DN = '/^(\/(o|cn|ou)=(.*?))\/?(\/(o|cn|ou)=(.*?)){0,}$/i';

    /**
     * The prefix for a LDAP DNS SRV record.
     */
    const SRV_PREFIX = '_ldap._tcp.';

    /**
     * The mask to use when sanitizing arrays with LDAP password information.
     */
    const MASK_PASSWORD = '******';

    /**
     * The mask to use when sanitizing values for LDAP that have binary data that cannot display properly.
     */
    const MASK_BINARY = '(Binary Data)';

    /**
     * The password attributes to mask in a batch/attribute array.
     */
    const MASK_ATTRIBUTES = [
        'unicodepwd',
        'userpassword',
    ];

    /**
     * Escape any special characters for LDAP to their hexadecimal representation.
     *
     * @param mixed $value The value to escape.
     * @param null|string $ignore The characters to ignore.
     * @param null|int $flags The context for the escaped string. LDAP_ESCAPE_FILTER or LDAP_ESCAPE_DN.
     * @return string The escaped value.
     */
    public static function escapeValue($value, $ignore = null, $flags = null)
    {
        // If this is a hexadecimal escaped string, then do not escape it.
        $value = preg_match('/^(\\\[0-9a-fA-F]{2})+$/', (string) $value) ? $value : ldap_escape($value, $ignore, $flags);

        // Per RFC 4514, leading/trailing spaces should be encoded in DNs, as well as carriage returns.
        if ((int)$flags & LDAP_ESCAPE_DN) {
            if (!empty($value) && $value[0] === ' ') {
                $value = '\\20' . substr($value, 1);
            }
            if (!empty($value) && $value[strlen($value) - 1] === ' ') {
                $value = substr($value, 0, -1) . '\\20';
            }
            // Only carriage returns seem to be valid, not line feeds (per testing of AD anyway).
            $value = str_replace("\r", '\0d', $value);
        }

        return $value;
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
            throw new InvalidArgumentException(sprintf('Unable to parse DN "%s".', $dn));
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
                throw new InvalidArgumentException(sprintf('Unable to parse DN piece "%s".', $values[0]));
            }
            $dn[$index] = $values[0].'='.self::escapeValue($values[1], null, LDAP_ESCAPE_DN);
        }

        return implode(',', $dn);
    }

    /**
     * Converts an Exchange Legacy DN into its separate pieces.
     *
     * @param string $dn
     * @param bool $withAttributes
     * @return array
     */
    public static function explodeExchangeLegacyDn($dn, $withAttributes = false)
    {
        preg_match(self::MATCH_LEGACY_DN, $dn, $matches);

        if (!isset($matches[2])) {
            throw new InvalidArgumentException(sprintf('Unable to parse legacy exchange dn "%s".', $dn));
        }
        $pieces = [];
        for ($i = 3; $i < count($matches); $i += 3) {
            $pieces[] = $withAttributes ? $matches[$i - 1].'='.$matches[$i] : $matches[$i];
        }

        return $pieces;
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
        if ($toEncoding == 'UTF-8' && self::isBinary($value)) {
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
        return (($pieces = ldap_explode_dn($dn, 1)) && isset($pieces['count']) && $pieces['count'] >= 2);
    }

    /**
     * Determine whether a value is a valid attribute name or OID. The name should meet the format described in RFC 2252.
     * However, the regex is fairly forgiving for each.
     *
     * @param string $value
     * @return bool
     */
    public static function isValidAttributeFormat($value)
    {
        return (preg_match(self::MATCH_DESCRIPTOR, $value) || preg_match(self::MATCH_OID, $value));
    }

    /**
     * Determine whether a value is in SID format.
     *
     * @param string $value
     * @return bool
     */
    public static function isValidSid($value)
    {
        return (bool) preg_match(self::MATCH_SID, $value);
    }

    /**
     * Determine whether a value is in valid GUID format.
     *
     * @param string $value
     * @return bool
     */
    public static function isValidGuid($value)
    {
        return (bool) preg_match(self::MATCH_GUID, $value);
    }

    /**
     * Sanitizes certain values in a batch array to make them safe for logging (ie. mask passwords, replace binary data).
     *
     * @param array $batches
     * @return array
     */
    public static function sanitizeBatchArray(array $batches)
    {
        foreach ($batches as $bI => $batch) {
            if (!isset($batch['values'])) {
                continue;
            }
            foreach ($batch['values'] as $vI => $value) {
                if (is_string($value) && self::isBinary($value)) {
                    $batches[$bI]['values'][$vI] = LdapUtilities::MASK_BINARY;
                }
            }
            if (isset($batch['attrib']) && in_array(strtolower($batch['attrib']), self::MASK_ATTRIBUTES)) {
                $batches[$bI]['values'] = [self::MASK_PASSWORD];
            }
        }

        return $batches;
    }

    /**
     * Sanitizes certain values in an attribute key => value array to make them safe for logging (ie. mask passwords,
     * replace binary data).
     *
     * @param array $attributes
     * @return array
     */
    public static function sanitizeAttributeArray(array $attributes)
    {
        foreach ($attributes as $name => $values) {
            if (in_array(strtolower($name), self::MASK_ATTRIBUTES)) {
                $attributes[$name] = self::MASK_PASSWORD;
            } else {
                $replaced = false;
                $newValues = is_array($values) ? $values : [$values];
                foreach ($newValues as $i => $v) {
                    if (is_string($v) && self::isBinary($v)) {
                        $newValues[$i] = LdapUtilities::MASK_BINARY;
                        $replaced = true;
                    }
                }
                if ($replaced) {
                    $attributes[$name] = is_array($values) ? $newValues : reset($newValues);
                }
            }
        }

        return $attributes;
    }

    /**
     * Check if a string contains non-printable, and likely binary, data. There is no easy way to do this, as there can
     * really only be a best effort attempt to detect it.
     *
     * @param string $value
     * @return bool
     */
    public static function isBinary($value)
    {
        return !preg_match('//u', $value);
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

    /**
     * Get an array containing the SSL certificates of the LDAP server. This runs over the standard LDAP port and
     * initiates a TlsStart operation.
     *
     * @param string $server The server name to connect to
     * @param int $port The standard LDAP port
     * @return array In the form of ['peer_certificate' => '', 'peer_certificate_chain' => []]
     */
    public static function getLdapSslCertificates($server, $port = 389)
    {
        // This is the hex encoded extendedRequest for the STARTTLS operation...
        $startTls = hex2bin("301d02010177188016312e332e362e312e342e312e313436362e3230303337");
        $certificates = [
            'peer_certificate' => '',
            'peer_certificate_chain' => [],
        ];

        $tcpSocket = new TcpSocket([
            'ssl' => [
                'capture_peer_cert' => true,
                'capture_peer_cert_chain' => true,
                'allow_self_signed' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);
        $tcpSocket->connect($server, $port, 5);
        $tcpSocket->setOperationTimeout(2);
        $tcpSocket->write($startTls);
        $tcpSocket->read(10240);
        $tcpSocket->enableEncryption(STREAM_CRYPTO_METHOD_TLS_CLIENT);

        $info = $tcpSocket->getParams();
        if (!$info) {
            return $certificates;
        }
        openssl_x509_export($info['options']['ssl']['peer_certificate'], $certificates['peer_certificate']);

        foreach ($info['options']['ssl']['peer_certificate_chain'] as $cert) {
            $certChain = '';
            openssl_x509_export($cert, $certChain);
            $certificates['peer_certificate_chain'][] = $certChain;
        }
        $tcpSocket->close();

        return $certificates;
    }

    /**
     * Given a full escaped DN return the RDN in escaped form.
     *
     * @param string $dn
     * @return string
     */
    public static function getRdnFromDn($dn)
    {
        $rdn = self::explodeDn($dn, 0)[0];
        $rdn = explode('=', $rdn, 2);

        return $rdn[0].'='.self::escapeValue($rdn[1], null, LDAP_ESCAPE_DN);
    }

    /**
     * Return the parent of a given DN.
     *
     * @param string $dn
     * @return string
     */
    public static function getParentDn($dn)
    {
        $parts = self::explodeDn($dn, 0);
        if (count($parts) === 1) {
            throw new InvalidArgumentException(sprintf('DN "%s" has no parent.', $dn));
        }
        array_shift($parts);
    
        return self::implodeDn($parts);
    }
    
    /**
     * Generate a UUIDv4 string.
     *
     * @return string
     */
    public static function uuid4()
    {
        return Uuid::uuid4()->toString();
    }

    /**
     * Given an attribute, split it between its alias and attribute. This will return an array where the first value
     * is the alias and the second is the attribute name. If there is no alias then the first value will be null.
     *
     * ie. list($alias, $attribute) = LdapUtilities::getAliasAndAttribute($attribute);
     *
     * @param string $attribute
     * @return array
     */
    public static function getAliasAndAttribute($attribute)
    {
        $alias = null;

        if (strpos($attribute, '.') !== false) {
            $pieces = explode('.', $attribute, 2);
            $alias = $pieces[0];
            $attribute = $pieces[1];
        }
        
        return [$alias, $attribute];
    }
}
