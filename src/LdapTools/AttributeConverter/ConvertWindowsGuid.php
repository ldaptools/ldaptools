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
 * Converts a binary objectGuid to a string representation and also from it's string representation back to hex for
 * searches. The back to hex structure is slightly unusual in that the 3 GUID sections are reverse hex-pair ordered
 * for the search.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertWindowsGuid implements AttributeConverterInterface
{
    use AttributeConverterTrait;

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
     * {@inheritdoc}
     */
    public function toLdap($guid)
    {
        $data = '';

        $guid = str_replace('-', '', $guid);
        foreach ($this->octetSections as $section) {
            $data .= $this->parseSection($guid, $section, true);
        }
        if ($this->getOperationType() == self::TYPE_CREATE || $this->getOperationType() == self::TYPE_MODIFY) {
            $data = hex2bin(str_replace('\\', '', $data));
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($guid)
    {
        $hex = unpack('H*hex', $guid)['hex'];

        $guidStrings = [];
        foreach ($this->guidSections as $section) {
            $guidStrings[] = $this->parseSection($hex, $section);
        }

        return implode('-', $guidStrings);
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
        $prefix = $octet ? '\\' : '';

        foreach ($sections as $section) {
            $start = $octet ? $section : $section[0];
            $length = $octet ? 2 : $section[1];
            $parsedString .= $prefix.substr($hex, $start, $length);
        }

        return $parsedString;
    }
}
