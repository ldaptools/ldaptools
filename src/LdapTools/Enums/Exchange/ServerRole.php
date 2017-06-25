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
 * Possible Exchange Server role flags.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ServerRole implements FlagEnumInterface
{
    use FlagEnumTrait;

    const MailboxDatabase = 2;

    const ClientAccess = 4;

    const UnifiedMessaging = 16;

    const HubTransport = 32;

    const EdgeTransport = 64;

    const Provisioned = 4096;
}
