<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Security\Acl;

use LdapTools\Security\Ace\Ace;
use LdapTools\Security\Ace\AceType;
use LdapTools\Utilities\NumberUtilitiesTrait;
use LdapTools\Exception\InvalidArgumentException;

/**
 * Represents an Access Control List structure.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
abstract class Acl
{
    use NumberUtilitiesTrait;

    /**
     * ACL Revision numbers.
     */
    const REVISION = [
        'GENERIC' => 0x02,
        'DS' => 0x04,
    ];

    /**
     * @var int The revision value of the ACL.
     */
    protected $revision;

    /**
     * @var int The Sbz1 reserved value of the ACL.
     */
    protected $sbz1 = 0;

    /**
     * @var int The Sbz2 reserved value of the ACL.
     */
    protected $sbz2 = 0;

    /**
     * @var Ace[]
     */
    protected $aces = [];

    /**
     * @param string|null $acl
     */
    public function __construct($acl = null)
    {
        $this->revision = self::REVISION['DS'];
        if ($acl) {
            $this->decodeBinary($acl);
        }
    }

    /**
     * Get the character that represents the ACL type in the SDDL string.
     *
     * @return string
     */
    abstract public function getSddlIdentifier();

    /**
     * Get the revision of the ACL.
     *
     * @return int
     */
    public function getRevision()
    {
        return $this->revision;
    }

    /**
     * Set the revision of the ACL.
     *
     * @param int $revision
     * @return $this
     */
    public function setRevision($revision)
    {
        if (!in_array((int) $revision, self::REVISION)) {
            throw new InvalidArgumentException(sprintf(
                'The value "%s" is not a recognized revision number. Allowed revisions are: %s',
                $revision,
                implode(', ', self::REVISION)
            ));
        }
        $this->revision = (int) $revision;

        return $this;
    }

    /**
     * Get the Sbz1 value for the ACL.
     *
     * @return int
     */
    public function getSbz1()
    {
        return $this->sbz1;
    }

    /**
     * Set the Sbz1 value for the ACL.
     *
     * @param int $sbz1
     * @return $this
     */
    public function setSbz1($sbz1)
    {
        $this->sbz1 = (int) $sbz1;

        return $this;
    }

    /**
     * Get the Sbz2 value for the ACL.
     *
     * @return int
     */
    public function getSbz2()
    {
        return $this->sbz2;
    }

    /**
     * Set the Sbz2 value for the ACL.
     *
     * @param int $sbz2
     * @return $this
     */
    public function setSbz2($sbz2)
    {
        $this->sbz2 = (int) $sbz2;

        return $this;
    }

    /**
     * Set the ACEs for the ACL.
     *
     * @param Ace[] ...$aces
     * @return $this
     */
    public function setAces(Ace ...$aces)
    {
        $this->aces = $aces;

        return $this;
    }

    /**
     * Add one or more ACEs to the ACL.
     *
     * @param Ace[] ...$aces
     * @return $this
     */
    public function addAce(Ace ...$aces)
    {
        foreach ($aces as $ace) {
            $this->validateAce($ace);
            if (!$this->hasAce($ace)) {
                $this->aces[] = $ace;
            }
        }

        return $this;
    }

    /**
     * Remove one or more ACEs from the ACL.
     *
     * @param Ace[] ...$aces
     * @return $this
     */
    public function removeAce(Ace ...$aces)
    {
        foreach ($aces as $ace) {
            if ($this->hasAce($ace)) {
                unset($this->aces[array_search($ace, $this->aces)]);
            }
        }

        return $this;
    }

    /**
     * Check if an ACE, or multiple ACEs, exist within this ACL.
     *
     * @param Ace[] ...$aces
     * @return bool
     */
    public function hasAce(Ace ...$aces)
    {
        $inArray = false;

        foreach ($aces as $ace) {
            $inArray = in_array($ace, $this->aces, true);
        }

        return $inArray;
    }

    /**
     * Get the ACEs for this ACL.
     *
     * @return Ace[]
     */
    public function getAces()
    {
        return $this->aces;
    }

    /**
     * Get the binary string representation of the ACL.
     *
     * @return string
     */
    public function toBinary()
    {
        $aces = '';
        foreach ($this->aces as $ace) {
            $aces .= $ace->toBinary();
        }

        return pack(
            'C1C1v1v1v1',
            $this->revision,
            $this->sbz1,
            ((16 + (strlen(bin2hex($aces)))) / 2),
            count($this->aces),
            $this->sbz2
        ).$aces;
    }

    /**
     * Get the SDDL string representation of the ACL.
     *
     * @return string
     */
    public function toSddl()
    {
        return implode('', $this->aces);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toSddl();
    }

    /**
     * Get the ACE type that is allowed (by the the string key constant it starts with).
     *
     * @return string
     */
    abstract protected function getAllowedAceType();

    /**
     * @param Ace $ace
     */
    protected function validateAce(Ace $ace)
    {
        $type = array_search($ace->getType()->getValue(), AceType::TYPE);

        if (substr($type, 0, strlen($this->getAllowedAceType())) !== $this->getAllowedAceType()) {
            throw new InvalidArgumentException(sprintf(
                'The Ace type with short name "%s" is not allowed in a %sacl.',
                $ace->getType()->getShortName(),
                $this->getSddlIdentifier()
            ));
        }
    }

    /**
     * @param string $acl
     */
    protected function decodeBinary($acl)
    {
        $acl = bin2hex($acl);

        $this->revision = hexdec(substr($acl, 0, 2));
        $this->sbz1 = hexdec(substr($acl, 2, 2));
        $this->sbz2 = $this->hexUShort16Le2Int(substr($acl, 12, 4));

        $position = 16;
        $aceCount = $this->hexUShort16Le2Int(substr($acl, 8, 4));
        for ($i = 0; $i < $aceCount; $i++) {
            // The ACE Size is always in this position, so use it to determine how much data we grab...
            $aceLength = $this->hexUShort16Le2Int(substr($acl, ($position + 4), 4)) * 2;

            $ace = new Ace(hex2bin(substr($acl, $position, $aceLength)));
            $this->addAce($ace);

            $position += $aceLength;
        }
    }
}
