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
 * Represents a SID structure.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class SID
{
    /**
     * @var int The revision level of the SID.
     */
    protected $revisionLevel;

    /**
     * @var int The value that indicates the authority under which the SID was created.
     */
    protected $identifierAuthority;

    /**
     * @var int[] Sub-authority values that uniquely identify a principal relative to the identifier authority.
     */
    protected $subAuthorities = [];

    /**
     * @param string $sid The SID in string or binary form.
     */
    public function __construct($sid)
    {
        if (LdapUtilities::isValidSid($sid)) {
            $this->decodeFromString($sid);
        } else {
            $this->decodeFromBinary($sid);
        }
    }

    /**
     * Get the SID in binary string form.
     *
     * @return string
     */
    public function toBinary()
    {
        return pack(
            'C2xxNV*',
            $this->revisionLevel,
            count($this->subAuthorities),
            $this->identifierAuthority,
            ...$this->subAuthorities
        );
    }

    /**
     * Get the SID in its friendly string form.
     *
     * @return string
     */
    public function toString()
    {
        return 'S-'.$this->revisionLevel.'-'.$this->identifierAuthority.implode(
            preg_filter('/^/', '-', $this->subAuthorities)
        );
    }

    /**
     * Get the revision level of the SID.
     *
     * @return int
     */
    public function getRevisionLevel()
    {
        return $this->revisionLevel;
    }

    /**
     * Get the value that indicates the authority under which the SID was created.
     *
     * @return int
     */
    public function getIdentifierAuthority()
    {
        return $this->identifierAuthority;
    }

    /**
     * Get the array of sub-authority values that uniquely identify a principal relative to the identifier authority.
     *
     * @return int[]
     */
    public function getSubAuthorities()
    {
        return $this->subAuthorities;
    }

    /**
     * The number of elements in the sub-authority array.
     *
     * @return int
     */
    public function getSubAuthorityCount()
    {
        return count($this->subAuthorities);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Parse the binary form of a SID into its respective parts that make it up.
     *
     * @param string $value
     */
    protected function decodeFromBinary($value)
    {
        $sid = @unpack('C1rev/C1count/x2/N1id', $value);
        if (!isset($sid['id']) || !isset($sid['rev'])) {
            throw new \UnexpectedValueException(
                'The revision level or identifier authority was not found when decoding the SID.'
            );
        }

        $this->revisionLevel = $sid['rev'];
        $this->identifierAuthority = $sid['id'];
        $subs = isset($sid['count']) ? $sid['count'] : 0;

        // The sub-authorities depend on the count, so only get as many as the count, regardless of data beyond it
        for ($i = 0; $i < $subs; $i++) {
            $this->subAuthorities[] = unpack('V1sub', hex2bin(substr(bin2hex($value), 16 + ($i * 8), 8)))['sub'];
        }
    }

    /**
     * @param string $value
     */
    protected function decodeFromString($value)
    {
        $sid = explode('-', ltrim($value, 'S-'));

        $this->revisionLevel = (int) array_shift($sid);
        $this->identifierAuthority = (int) array_shift($sid);
        $this->subAuthorities = array_map('intval', $sid);
    }
}
