<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Enums\Exchange;

use Enums\FlagEnumInterface;
use Enums\FlagEnumTrait;

/**
 * Mobile access (EAS, OMA, OWA) control related flags (msExchOmaAdminWirelessEnable).
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class MobileAccessControl implements FlagEnumInterface
{
    use FlagEnumTrait;

    /**
     * None for access control is the same as having everything enabled (ie. when the attribute is unset)
     */
    const None = 0;

    const ActiveSyncPushDisabled = 1;

    const OutlookMobileAccessDisabled = 2;

    const ActiveSyncDisabled = 4;

    const OWAForDevicesDisabled = 8;
}
