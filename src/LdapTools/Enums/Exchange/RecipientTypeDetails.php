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
 * Possible values for Exchange Recipient Type Details.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class RecipientTypeDetails implements SimpleEnumInterface
{
    use SimpleEnumTrait;

    const UserMailbox = 1;

    const LinkedMailbox = 2;

    const SharedMailbox = 4;

    const LegacyMailbox = 8;

    const RoomMailbox = 16;

    const EquipmentMailbox = 32;

    const MailContact = 64;

    const MailUser = 128;

    const MailUniversalDistributionGroup = 256;

    const MailNonUniversalDistributionGroup = 512;

    const MailUniversalSecurityGroup = 1024;

    const DynamicDistributionGroup = 2048;

    const PublicFolder = 4096;

    const SystemAttendantMailbox = 8192;

    const SystemMailbox = 16384;

    const MailForestContact = 32768;

    const User = 65536;

    const Contact = 131072;

    const UniversalDistributionGroup = 262144;

    const UniversalSecurityGroup = 524288;

    const NonUniversalGroup = 1048576;

    const DisabledUser = 2097152;

    const MicrosoftExchange = 4194304;

    const ArbitrationMailbox = 8388608;

    const MailboxPlan = 16777216;

    const LinkedUser = 33554432;

    const RoomList = 268435456;

    const DiscoveryMailbox = 536870912;

    const RoleGroup = 1073741824;

    const RemoteMailbox = 2147483648;

    const TeamMailbox = 137438953472;
}
