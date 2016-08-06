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

/**
 * Represents a GPO link in AD.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class GPOLink
{
    /**
     * These are the flag values that control whether the link is ignored/enforced.
     */
    const FLAGS = [
        'NOT_IGNORED_NOT_ENFORCED' => 0,
        'IGNORED' => 1,
        'ENFORCED' => 2,
        'IGNORED_ENFORCED' => 3,
    ];

    /**
     * @var int The GPO options bit flags
     */
    protected $options;

    /**
     * @var string The GPO (name, DN, SID, or GUID)
     */
    protected $gpo;

    /**
     * @param string|\LdapTools\Object\LdapObject $gpo The GPO (name, DN, SID, GUID, or LdapObject)
     * @param int $options The GPO options bit flags
     */
    public function __construct($gpo, $options = self::FLAGS['NOT_IGNORED_NOT_ENFORCED'])
    {
        $this->gpo = $gpo;
        $this->setOptionsFlag($options);
    }

    /**
     * Set the GPO for the link.
     *
     * @param string|\LdapTools\Object\LdapObject $gpo
     * @return $this
     */
    public function setGpo($gpo)
    {
        $this->gpo = $gpo;

        return $this;
    }

    /**
     * Get the GPO for the link.
     *
     * @return string|\LdapTools\Object\LdapObject
     */
    public function getGpo()
    {
        return $this->gpo;
    }

    /**
     * Set whether the GPO link is enabled.
     *
     * @param bool $isEnabled
     * @return GPOLink
     */
    public function setIsEnabled($isEnabled)
    {
        return $this->modifyOptions($isEnabled, self::FLAGS['IGNORED']);
    }

    /**
     * Get whether the GPO link is enabled.
     *
     * @return bool
     */
    public function getIsEnabled()
    {
        return $this->isOptionFlagSet(self::FLAGS['IGNORED']);
    }

    /**
     * Set whether the GPO is enforced.
     *
     * @param bool $isEnforced
     * @return GPOLink
     */
    public function setIsEnforced($isEnforced)
    {
        return $this->modifyOptions($isEnforced, self::FLAGS['ENFORCED']);
    }

    /**
     * Get whether the GPO is enforced.
     *
     * @return bool
     */
    public function getIsEnforced()
    {
        return $this->isOptionFlagSet(self::FLAGS['ENFORCED']);
    }

    /**
     * Set the GPO options bit flags.
     *
     * @param int $options
     * @return $this
     */
    public function setOptionsFlag($options)
    {
        if (in_array($options, self::FLAGS) === false) {
            throw new InvalidArgumentException(sprintf("The GPO options flag must be a value 0 - 3, got: %s", $options));
        }
        $this->options = $options;

        return $this;
    }

    /**
     * Get the GPO options bit flags.
     *
     * @return int
     */
    public function getOptionsFlag()
    {
        return $this->options;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->gpo;
    }

    /**
     * Modifies a specific bit in the options flag. Ignores it if it is already set to the desired action.
     *
     * @param bool $action
     * @param int $int
     * @return $this
     */
    protected function modifyOptions($action, $int)
    {
        if ($this->isOptionFlagSet($int) === $action) {
            return $this;
        }
        $action = $int == self::FLAGS['IGNORED'] ? !$action : $action;

        if ($action) {
            $this->options = (int) $this->options | (int) $int;
        } else {
            $this->options = (int) $this->options ^ (int) $int;
        }

        return $this;
    }

    /**
     * A quick check to determine if an option flag is already set.
     *
     * @param int $flag
     * @return bool
     */
    protected function isOptionFlagSet($flag)
    {
        $isFlagSet = (bool) ((int) $this->options & (int) $flag);

        return $flag == self::FLAGS['IGNORED'] ? !$isFlagSet : $isFlagSet;
    }
}
