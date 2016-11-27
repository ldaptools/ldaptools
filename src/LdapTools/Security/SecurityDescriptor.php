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

use LdapTools\Exception\LogicException;
use LdapTools\Security\Acl\Acl;
use LdapTools\Security\Acl\Dacl;
use LdapTools\Security\Acl\Sacl;
use LdapTools\Utilities\NumberUtilitiesTrait;

/**
 * Represents a Security Descriptor structure.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 * @see https://msdn.microsoft.com/en-us/library/cc230273.aspx
 */
class SecurityDescriptor
{
    use NumberUtilitiesTrait;

    /**
     * @var int The security descriptor revision.
     */
    protected $revision = 1;

    /**
     * @var null|Flags The resource manager control flags.
     */
    protected $rmControlFlags;

    /**
     * @var null|SID
     */
    protected $owner;

    /**
     * @var null|SID
     */
    protected $group;

    /**
     * @var null|Dacl
     */
    protected $dacl;

    /**
     * @var null|Sacl
     */
    protected $sacl;

    /**
     * @var ControlFlags
     */
    protected $controlFlags;

    /**
     * @var array A simple map of SDDL control flags and their ControlFlag constant names (minus the prepended ACL type)
     */
    protected $aclFlagMap = [
        'P' => 'ACL_PROTECTED',
        'AR' => 'ACL_AUTO_INHERIT_REQ',
        'AI' => 'ACL_AUTO_INHERIT',
    ];

    /**
     * @param null|string $descriptor
     */
    public function __construct($descriptor = null)
    {
        $this->rmControlFlags = new Flags(0);
        $this->controlFlags = new ControlFlags(ControlFlags::FLAG['SELF_RELATIVE']);
        if ($descriptor) {
            $this->decodeFromBinary($descriptor);
        }
    }

    /**
     * @return int
     */
    public function getRevision()
    {
        return $this->revision;
    }

    /**
     * Get the Resource Manager control flag value. This specifies control bits that contain specific information for
     * the resource manager accessing it and the RM_CONTROL_VALID flag is set in the controls.
     *
     * @return Flags
     */
    public function getRmControlFlags()
    {
        return $this->rmControlFlags;
    }

    /**
     * Set the ACE control flags object.
     *
     * @param ControlFlags $controlFlags
     * @return $this
     */
    public function setControlFlags(ControlFlags $controlFlags)
    {
        $this->controlFlags = $controlFlags;

        return $this;
    }

    /**
     * Get the ACE control flags.
     *
     * @return ControlFlags
     */
    public function getControlFlags()
    {
        return $this->controlFlags;
    }

    /**
     * Set the owner SID for the security descriptor.
     *
     * @param SID|string $owner
     * @return $this
     */
    public function setOwner($owner)
    {
        $this->owner = $owner instanceof SID ? $owner : new SID($owner);

        return $this;
    }

    /**
     * Get the owner SID for the security descriptor.
     *
     * @return SID|null
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set the group SID for the security descriptor.
     *
     * @param SID|string $group
     * @return $this
     */
    public function setGroup($group)
    {
        $this->group = $group instanceof SID ? $group : new SID($group);

        return $this;
    }

    /**
     * Get the group SID for the security descriptor.
     *
     * @return SID
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Get the Discretionary ACL
     *
     * @return Dacl|null
     */
    public function getDacl()
    {
        return $this->dacl;
    }

    /**
     * Set the Discretionary ACL.
     *
     * @param Dacl $dacl
     * @return $this
     */
    public function setDacl(Dacl $dacl = null)
    {
        $this->dacl = $dacl;

        return $this->toggleAclPresent((bool) $dacl, Dacl::SDDL_CHAR);
    }

    /**
     * Get the System ACL.
     *
     * @return Sacl|null
     */
    public function getSacl()
    {
        return $this->sacl;
    }

    /**
     * Set the System ACL.
     *
     * @param Sacl $sacl
     * @return $this
     */
    public function setSacl(Sacl $sacl = null)
    {
        $this->sacl = $sacl;

        return $this->toggleAclPresent((bool) $sacl, Sacl::SDDL_CHAR);
    }

    /**
     * Get the binary string form of the security descriptor.
     *
     * @param bool $canonicalize Whether or not to canonicalize the DACL
     * @return string
     */
    public function toBinary($canonicalize = true)
    {
        $offsetOwner = 0;
        $offsetGroup = 0;
        $offsetDacl = 0;
        $offsetSacl = 0;
        $owner = $this->owner ? $this->owner->toBinary() : null;
        $group = $this->group ? $this->group->toBinary() : null;
        $dacl = $this->dacl ? $this->dacl->toBinary($canonicalize) : null;
        $sacl = $this->sacl ? $this->sacl->toBinary() : null;

        if ($owner === null || $group === null) {
            throw new LogicException('The owner and the group must be set in the Security Descriptor.');
        }
        if ($sacl === null && $dacl === null) {
            throw new LogicException('Either the SACL or DACL must be set on the Security Descriptor.');
        }

        /**
         * According the the MS docs, the order of the elements is not important. And indeed, I have found no rhyme or
         * reason as to how the owner/group/sacl/dacl elements are ordered in the security descriptor. As long as they
         * point to the correct offset where the element is located then it will work. But the order seems unpredictable
         * as far as coming from AD/Exchange/etc.
         */
        $offset = 40;
        if ($owner) {
            $offsetOwner = $offset;
            $offset += strlen(bin2hex($owner));
        }
        if ($group) {
            $offsetGroup = $offset;
            $offset += strlen(bin2hex($group));
        }
        if ($sacl) {
            $offsetSacl = $offset;
            $offset += strlen(bin2hex($sacl));
        }
        if ($dacl) {
            $offsetDacl = $offset;
        }

        return pack(
            'C1C1v1V1V1V1V1',
            $this->revision,
            $this->rmControlFlags->getValue(),
            $this->controlFlags->getValue(),
            ($offsetOwner / 2),
            ($offsetGroup / 2),
            ($offsetSacl / 2),
            ($offsetDacl / 2)
        ).$owner.$group.$sacl.$dacl;
    }

    /**
     * Get the SDDL string format that represents this Security Descriptor and everything it contains.
     *
     * @param bool $canonicalize Whether or not to canonicalize the DACL
     * @return string
     */
    public function toSddl($canonicalize = true)
    {
        // These will always be present in the SDDL...
        $sddl = 'O:'.($this->owner->getShortName() ?: $this->owner).'G:'.($this->group->getShortName() ?: $this->group);

        foreach ([$this->dacl, $this->sacl] as $acl) {
            // It should be omitted if empty or not set...
            if ($acl && count($acl->getAces()) > 0) {
                $sddl .= $acl->getSddlIdentifier().':'.$this->getAclSddlFlags($acl);
                $sddl .= $acl instanceof Dacl ? $acl->toSddl($canonicalize) : $acl->toSddl();
            }
        }

        return $sddl;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toSddl();
    }

    /**
     * Get the string flags that need to be prepended to the Dacl/Sacl SDDL string.
     *
     * @param Acl $acl
     * @return string
     */
    protected function getAclSddlFlags(Acl $acl)
    {
        $flags = '';

        foreach ($this->aclFlagMap as $flag => $name) {
            $name = $acl->getSddlIdentifier().$name;
            if ($this->controlFlags->has(ControlFlags::FLAG[$name])) {
                $flags .= $flag;
            }
        }

        return $flags;
    }

    /**
     * @param string $descriptor
     */
    protected function decodeFromBinary($descriptor)
    {
        $descriptor = bin2hex($descriptor);

        $this->revision = hexdec(substr($descriptor, 0, 2));
        $this->rmControlFlags = new Flags(hexdec(substr($descriptor, 2, 2)));
        $this->controlFlags = new ControlFlags($this->hexUShort16Le2Int(substr($descriptor, 4, 4)));

        $offsetOwner = $this->hexULong32Le2int(substr($descriptor, 8, 8)) * 2;
        $offsetGroup = $this->hexULong32Le2int(substr($descriptor, 16, 8)) * 2;
        $offsetSacl = $this->hexULong32Le2int(substr($descriptor, 24, 8)) * 2;
        $offsetDacl = $this->hexULong32Le2int(substr($descriptor, 32, 8)) * 2;

        if ($offsetOwner !== 0) {
            $this->owner = new SID(hex2bin(substr($descriptor, $offsetOwner)));
        }
        if ($offsetGroup !== 0) {
            $this->group = new SID(hex2bin(substr($descriptor, $offsetGroup)));
        }
        if ($offsetSacl !== 0) {
            $this->sacl = new Sacl(hex2bin(substr($descriptor, $offsetSacl)));
        }
        if ($offsetDacl !== 0) {
            $this->dacl = new Dacl(hex2bin(substr($descriptor, $offsetDacl)));
        }
    }

    /**
     * @param bool $present
     * @param string $identifier
     * @return $this
     */
    protected function toggleAclPresent($present, $identifier)
    {
        if ($present) {
            $this->controlFlags->add(ControlFlags::FLAG[$identifier.'ACL_PRESENT']);
        } else {
            $this->controlFlags->remove(ControlFlags::FLAG[$identifier.'ACL_PRESENT']);
        }

        return $this;
    }
}
