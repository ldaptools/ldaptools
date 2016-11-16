<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Security;

use LdapTools\Utilities\LdapUtilities;

/**
 * Represents a GUID structure.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class GUID
{
    /**
     * @var string The string representation of the GUID.
     */
    protected $guid;

    /**
     * @var array The guid structure in order by section to parse using substr().
     */
    protected $guidSections = [
        [[-26, 2], [-28, 2], [-30, 2], [-32, 2]],
        [[-22, 2], [-24, 2]],
        [[-18, 2], [-20, 2]],
        [[-16, 4]],
        [[-12, 12]],
    ];

    /**
     * @var array The hexadecimal octet order based on string position.
     */
    protected $octetSections = [
        [6, 4, 2 ,0],
        [10, 8],
        [14, 12],
        [16, 18, 20, 22, 24, 26, 28, 30]
    ];

    /**
     * @param string $guid
     */
    public function __construct($guid)
    {
        if (LdapUtilities::isValidGuid($guid)) {
            $this->guid = $guid;
        } else {
            $this->decodeFromBinary($guid);
        }
    }

    /**
     * Get the friend string form of the GUID.
     *
     * @return string
     */
    public function toString()
    {
        return $this->guid;
    }

    /**
     * Get the binary representation of the GUID string.
     *
     * @return string
     */
    public function toBinary()
    {
        $data = '';

        $guid = str_replace('-', '', $this->guid);
        foreach ($this->octetSections as $section) {
            $data .= $this->parseSection($guid, $section, true);
        }

        return hex2bin($data);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->guid;
    }

    /**
     * @param string $guid
     */
    protected function decodeFromBinary($guid)
    {
        $hex = unpack('H*hex', $guid)['hex'];

        $guidStrings = [];
        foreach ($this->guidSections as $section) {
            $guidStrings[] = $this->parseSection($hex, $section);
        }
        $guid = implode('-', $guidStrings);

        if (!LdapUtilities::isValidGuid($guid)) {
            throw new \UnexpectedValueException(sprintf(
                'The GUID with value "%s" is not valid.',
                $guid
            ));
        }

        $this->guid = $guid;
    }

    /**
     * Return the specified section of the hexadecimal string.
     *
     * @param $hex string The full hex string.
     * @param array $sections An array of start and length (unless octet is true, then length is always 2).
     * @param bool $octet Whether this is for octet string form.
     * @return string The concatenated sections in upper-case.
     */
    protected function parseSection($hex, array $sections, $octet = false)
    {
        $parsedString = '';

        foreach ($sections as $section) {
            $start = $octet ? $section : $section[0];
            $length = $octet ? 2 : $section[1];
            $parsedString .= substr($hex, $start, $length);
        }

        return $parsedString;
    }
}
