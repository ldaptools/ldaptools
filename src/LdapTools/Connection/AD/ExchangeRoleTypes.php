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
 * The different Exchange Role types.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ExchangeRoleTypes
{
    /**
     * Maps the various Exchange Roles to their integer values.
     */
    const ROLES = [
        'MailboxDatabase' => 2,
        'ClientAccess' => 4,
        'UnifiedMessaging' => 16,
        'HubTransport' => 32,
        'EdgeTransport' => 64,
        'Provisioned' => 4096
    ];
    
    /**
     * @var array Maps the possible Exchange Roles to their friendly names.
     */
    const NAMES = [
        self::ROLES['MailboxDatabase'] => 'Mailbox Database',
        self::ROLES['ClientAccess'] => 'Client Access',
        self::ROLES['UnifiedMessaging'] => 'Unified Messaging',
        self::ROLES['HubTransport'] => 'Hub Transport',
        self::ROLES['EdgeTransport'] => 'Edge Transport',
        self::ROLES['Provisioned'] => 'Provisioned',
    ];
}
