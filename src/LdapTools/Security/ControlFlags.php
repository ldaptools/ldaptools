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

/**
 * Represents Security Descriptor control flags.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ControlFlags extends Flags
{
    use FlagsSddlTrait;

    /**
     * Recognized control access bit flags (in bit order).
     */
    const FLAG = [
        'SELF_RELATIVE' => 32768,
        'RM_CONTROL_VALID' => 16386,
        'SACL_PROTECTED' => 8192,
        'DACL_PROTECTED' => 4096,
        'SACL_AUTO_INHERIT' => 2048,
        'DACL_AUTO_INHERIT' => 1024,
        'SACL_AUTO_INHERIT_REQ' => 512,
        'DACL_AUTO_INHERIT_REQ' => 256,
        'DACL_TRUSTED' => 128,
        'SERVER_SECURITY' => 64,
        'SACL_DEFAULTED' => 32,
        'SACL_PRESENT' => 16,
        'DACL_DEFAULTED' => 8,
        'DACL_PRESENT' => 4,
        'GROUP_DEFAULTED' => 2,
        'OWNER_DEFAULTED' => 1,
    ];

    const SHORT_NAME = [
        'SR' => 32768,
        'RM' => 16386,
        'PS' => 8192,
        'PD' => 4096,
        'SI' => 2048,
        'DI' => 1024,
        'SC' => 512,
        'DC' => 256,
        'DT' => 128,
        'SS' => 64,
        'SD' => 32,
        'SP' => 16,
        'DD' => 8,
        'DP' => 4,
        'GD' => 2,
        'OD' => 1,
    ];
}
