<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Security\Ace;

use LdapTools\Exception\LogicException;
use LdapTools\Security\SID;
use LdapTools\Security\GUID;
use LdapTools\Utilities\NumberUtilitiesTrait;

/**
 * Represents an Access Control Entry used to encode the user rights afforded to a principal.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class Ace
{
    use NumberUtilitiesTrait;

    /**
     * @var AceType
     */
    protected $type;

    /**
     * @var AceFlags
     */
    protected $flags;

    /**
     * @var AceRights
     */
    protected $aceRights;

    /**
     * @var SID The trustee SID this ACE relates to.
     */
    protected $trustee;

    /**
     * @var null|GUID The object GUID, if present.
     */
    protected $objectType;

    /**
     * @var null|GUID The inherited object GUID, if present.
     */
    protected $inheritedObjectType;

    /**
     * @var AceObjectFlags When this is an object type ACE, these flags describe what GUID objects are present.
     */
    protected $objectFlags;

    /**
     * @var null|string Any application specific data for the ACE (in binary string format).
     */
    protected $applciationData;

    /**
     * @param null|string|AceType $ace
     */
    public function __construct($ace = null)
    {
        $this->flags = new AceFlags();
        $this->aceRights = new AceRights();

        if ($ace instanceof AceType) {
            $this->type = $ace;
        } elseif (array_key_exists($ace, AceType::SHORT_NAME) || in_array($ace, AceType::SHORT_NAME, true)) {
            $this->type = new AceType($ace);
        } elseif ($ace) {
            $this->decodeFromBinary($ace);
        }
    }

    /**
     * Get the AceType.
     *
     * @return AceType|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the AceType.
     *
     * @param AceType|string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type instanceof AceType ? $type : new AceType($type);

        return $this;
    }

    /**
     * Get the trustee SID this ACE applies to.
     *
     * @return SID
     */
    public function getTrustee()
    {
        return $this->trustee;
    }

    /**
     * Set the trustee SID this ACE applies to.
     *
     * @param SID|string $sid
     * @return $this
     */
    public function setTrustee($sid)
    {
        $this->trustee = $sid instanceof SID ? $sid : new SID($sid);

        return $this;
    }

    /**
     * Get the GUID object type this ACE applies to.
     *
     * @return GUID|null
     */
    public function getObjectType()
    {
        return $this->objectType;
    }

    /**
     * Set the GUID object type this ACE applies to.
     *
     * @param GUID|string|null $guid
     * @return $this
     */
    public function setObjectType($guid)
    {
        $this->objectType = ($guid instanceof GUID || $guid === null) ? $guid : new GUID($guid);

        return $this->toggleObjectStatus($guid, AceObjectFlags::FLAG['OBJECT_TYPE_PRESENT']);
    }

    /**
     * Get the inherited GUID object type this ACE applies to.
     *
     * @return GUID|null
     */
    public function getInheritedObjectType()
    {
        return $this->inheritedObjectType;
    }

    /**
     * Set the inherited GUID object type this ACE applies to.
     *
     * @param GUID|string|null $guid
     * @return $this
     */
    public function setInheritedObjectType($guid)
    {
        $this->inheritedObjectType = ($guid instanceof GUID || $guid === null) ? $guid : new GUID($guid);

        return $this->toggleObjectStatus($guid, AceObjectFlags::FLAG['INHERITED_OBJECT_TYPE_PRESENT']);
    }

    /**
     * Get the object flags that apply to the object type GUIDs.
     *
     * @return AceObjectFlags
     */
    public function getObjectFlags()
    {
        return $this->objectFlags;
    }

    /**
     * Set the object flags that apply to the object type GUIDs.
     *
     * @param null|AceObjectFlags $objectFlags
     * @return $this
     */
    public function setObjectFlags(AceObjectFlags $objectFlags = null)
    {
        $this->objectFlags = $objectFlags;

        return $this;
    }

    /**
     * Set the AceRights for this ACE.
     *
     * @param AceRights $aceRights
     * @return $this
     */
    public function setRights(AceRights $aceRights)
    {
        $this->aceRights = $aceRights;

        return $this;
    }

    /**
     * Get the AceRights object that contains all the rights flags set against this ACE.
     *
     * @return AceRights
     */
    public function getRights()
    {
        return $this->aceRights;
    }

    /**
     * Get the AceFlags object that contains all the flags set for this ACE.
     *
     * @return AceFlags
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * Set the AceFlags object that contains all the flags set for this ACE.
     *
     * @param AceFlags $aceFlags
     * @return $this
     */
    public function setFlags(AceFlags $aceFlags)
    {
        $this->flags = $aceFlags;

        return $this;
    }

    /**
     * Get the binary string form of the application data contained in this ACE.
     *
     * @return string
     */
    public function getApplicationData()
    {
        return $this->applciationData;
    }

    /**
     * Set the binary string application data for this ACE. You MUST pass the binary form of the data.
     *
     * @param string $appData
     * @return $this
     */
    public function setApplicationData($appData)
    {
        $this->applciationData = $appData;

        return $this;
    }

    /**
     * Get the binary string representation of this ACE.
     *
     * @return string
     */
    public function toBinary()
    {
        $this->validate();

        $binary = '';
        if ($this->objectFlags !== null) {
            $binary .= pack('V1', $this->objectFlags->getValue());
        }
        if ($this->objectType) {
            $binary .= $this->objectType->toBinary();
        }
        if ($this->inheritedObjectType) {
            $binary .= $this->inheritedObjectType->toBinary();
        }
        $binary .= $this->trustee->toBinary();
        if ($this->applciationData) {
            $binary .= $this->applciationData;
        }

        return pack(
            'C1C1v1l1',
            $this->type->getValue(),
            $this->flags->getValue(),
            (16 + strlen(bin2hex($binary))) / 2,
            $this->aceRights->getValue()
        ).$binary;
    }

    /**
     * A convenience method to check whether this is an object type ACE.
     *
     * @return bool
     */
    public function isObjectAce()
    {
        return $this->endsWith('OBJECT', array_search($this->getType()->getValue(), AceType::TYPE));
    }

    /**
     * A convenience method to check whether this is an ace to deny access.
     *
     * @return bool
     */
    public function isDenyAce()
    {
        return $this->startsWith('ACCESS_DENIED', array_search($this->getType()->getValue(), AceType::TYPE));
    }

    /**
     * A convenience method to check whether this is an ace to allow access.
     *
     * @return bool
     */
    public function isAllowAce()
    {
        return $this->startsWith('ACCESS_ALLOWED', array_search($this->getType()->getValue(), AceType::TYPE));
    }

    /**
     * Get the SDDL string format that represents this ACE.
     *
     * @return string
     */
    public function toSddl()
    {
        $this->validate();

        return '('.implode(';', [
            $this->type,
            $this->flags,
            $this->aceRights,
            $this->objectType,
            $this->inheritedObjectType,
            $this->trustee->getShortName() ?: $this->trustee,
        ]).')';
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toSddl();
    }

    /**
     * Some quick checks before going to SDDL or binary.
     */
    protected function validate()
    {
        if (!$this->trustee) {
            throw new LogicException('The ACE must have a trustee defined.');
        }
        if (!$this->type) {
            throw new LogicException('The ACE must have a type defined.');
        }
    }

    /**
     * @param string $ace
     */
    protected function decodeFromBinary($ace)
    {
        $ace = bin2hex($ace);

        $this->type = new AceType(hexdec(substr($ace, 0, 2)));
        $this->flags = new AceFlags(hexdec(substr($ace, 2, 2)));
        $this->aceRights = new AceRights($this->hexSLong32Be2Int(substr($ace, 8, 8)));

        // If this is an object-specific ACE type, then it contains additional object GUID(s) and flags...
        $position = 16;
        if (substr(array_search($this->type->getValue(), AceType::TYPE), -strlen("OBJECT")) === "OBJECT") {
            $this->objectFlags = new AceObjectFlags($this->hexULong32Le2int(substr($ace, 16, 8)));
            $position += 8;
            if ($this->objectFlags->has(AceObjectFlags::FLAG['OBJECT_TYPE_PRESENT'])) {
                $this->objectType = new GUID(hex2bin(substr($ace, $position, 32)));
                $position += 32;
            }
            if ($this->objectFlags->has(AceObjectFlags::FLAG['INHERITED_OBJECT_TYPE_PRESENT'])) {
                $this->inheritedObjectType = new GUID(hex2bin(substr($ace, $position, 32)));
                $position += 32;
            }
        }

        $this->trustee = new SID(hex2bin(substr($ace, $position)));
        $position += strlen(bin2hex($this->trustee->toBinary()));

        $size = $this->hexUShort16Le2Int(substr($ace, 4, 4));
        if ($position < ($size * 2)) {
            $this->applciationData = hex2bin(substr($ace, $position));
        }
    }

    /**
     * @param string $needle
     * @param string $haystack
     * @return bool
     */
    protected function startsWith($needle, $haystack)
    {
        return (substr($haystack, 0, strlen($needle)) === $needle);
    }

    /**
     * @param string $needle
     * @param string $haystack
     * @return bool
     */
    protected function endsWith($needle, $haystack)
    {
        return substr($haystack, -strlen($needle)) === $needle;
    }

    /**
     * @param GUID|null $object
     * @param int $type
     * @return $this
     */
    protected function toggleObjectStatus($object, $type)
    {
        if (!$object && !$this->objectFlags) {
            return $this;
        }
        if ($object && !$this->objectFlags) {
            $this->objectFlags = new AceObjectFlags();
        }

        if ($object) {
            $this->objectFlags->add($type);
        } else {
            $this->objectFlags->remove($type);
        }

        return $this;
    }
}
