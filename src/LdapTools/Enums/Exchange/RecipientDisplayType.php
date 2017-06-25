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

use Enums\SimpleEnumInterface;
use Enums\SimpleEnumTrait;

/**
 * Possible values for Exchange Recipient Display Types.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class RecipientDisplayType implements SimpleEnumInterface
{
    use SimpleEnumTrait;

    const MailboxUser = 0;

    const DistributionGroup = 1;

    const PublicFolder = 2;

    const DynamicDistributionGroup = 3;

    const Organization = 4;

    const PrivateDistributionGroup = 5;

    const RemoteMailUser = 6;

    const ConferenceRoomMailbox = 7;

    const EquipmentMailbox = 8;

    const ACLableMailboxUser = 1073741824;

    const SecurityDistributionGroup = 1073741833;

    const SyncedMailboxUser = -2147483642;

    const SyncedUDGasUDG = -2147483391;

    const SyncedUDGasContact = -2147483386;

    const SyncedPublicFolder = -2147483130;

    const SyncedDynamicDistributionGroup = -2147482874;

    const SyncedRemoteMailUser = -2147482106;

    const SyncedConferenceRoomMailbox = -2147481850;

    const SyncedEquipmentMailbox = -2147481594;

    const SyncedUSGasUDG = -21474831343;

    const SyncedUSGasContact = -2147481338;

    const ACLableSyncedMailboxUser = -1073741818;

    const ACLableSyncedRemoteMailUser = -1073740282;

    const ACLableSyncedUSGasContact = -1073739514;

    const SyncedUSGasUSG = -1073739511;
}
