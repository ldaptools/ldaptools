<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Connection\AD;

/**
 * Constant values for Exchange Recipient Type Details and Display Type.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ExchangeRecipient
{
    /**
     * Maps the various recipient type details to their integer values.
     */
    const TYPE_DETAILS = [
        'UserMailbox' => 1,
        'LinkedMailbox' => 2,
        'SharedMailbox' => 4,
        'LegacyMailbox' => 8,
        'RoomMailbox' => 16,
        'EquipmentMailbox' => 32,
        'MailContact' => 64,
        'MailUser' => 128,
        'MailUniversalDistributionGroup' => 256,
        'MailNonUniversalDistributionGroup' => 512,
        'MailUniversalSecurityGroup' => 1024,
        'DynamicDistributionGroup' => 2048,
        'PublicFolder' => 4096,
        'SystemAttendantMailbox' => 8192,
        'SystemMailbox' => 16384,
        'MailForestContact' => 32768,
        'User' => 65536,
        'Contact' => 131072,
        'UniversalDistributionGroup' => 262144,
        'UniversalSecurityGroup' => 524288,
        'NonUniversalGroup' => 1048576,
        'DisabledUser' => 2097152,
        'MicrosoftExchange' => 4194304,
        'ArbitrationMailbox' => 8388608,
        'MailboxPlan' => 16777216,
        'LinkedUser' => 33554432,
        'RoomList' => 268435456,
        'DiscoveryMailbox' => 536870912,
        'RoleGroup' => 1073741824,
        'RemoteMailbox' => 2147483648,
        'TeamMailbox' => 137438953472,
    ];

    /**
     * Maps the various display type's to their integer values.
     */
    const DISPLAY_TYPE = [
        'MailboxUser' => 0,
        'DistributionGroup' => 1,
        'PublicFolder' => 2,
        'DynamicDistributionGroup' => 3,
        'Organization' => 4,
        'PrivateDistributionGroup' => 5,
        'RemoteMailUser' => 6,
        'ConferenceRoomMailbox' => 7,
        'EquipmentMailbox' => 8,
        'ACLableMailboxUser' => 1073741824,
        'SecurityDistributionGroup' => 1043741833,
        'SyncedMailboxUser' => -2147483642,
        'SyncedUDGasUDG' => -2147483391,
        'SyncedUDGasContact' => -2147483386,
        'SyncedPublicFolder' => -2147483130,
        'SyncedDynamicDistributionGroup' => -2147482874,
        'SyncedRemoteMailUser' => -2147482106,
        'SyncedConferenceRoomMailbox' => -2147481850,
        'SyncedEquipmentMailbox' => -2147481594,
        'SyncedUSGasUDG' => -21474831343,
        'SyncedUSGasContact' => -2147481338,
        'ACLableSyncedMailboxUser' => -1073741818,
        'ACLableSyncedRemoteMailUser' => -1073740282,
        'ACLableSyncedUSGasContact' => -1073739514,
        'SyncedUSGasUSG' => -1073739511,
    ];
}
