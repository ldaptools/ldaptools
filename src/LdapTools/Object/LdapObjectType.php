<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Object;

/**
 * Base object types which are then further defined in the schema.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapObjectType
{
    /**
     * A normal user account.
     */
    const USER = 'user';

    /**
     * Either a security or distribution group
     */
    const GROUP = 'group';

    /**
     * A contact object.
     */
    const CONTACT = 'contact';

    /**
     * A computer object.
     */
    const COMPUTER = 'computer';

    /**
     * An OU object.
     */
    const OU = 'ou';

    /**
     * A container object.
     */
    const CONTAINER = 'container';

    /**
     * Deleted LDAP objects.
     */
    const DELETED = 'deleted';

    /**
     * An Exchange Server object.
     */
    const EXCHANGE_SERVER = 'ExchangeServer';

    /**
     * An Exchange Database object.
     */
    const EXCHANGE_DATABASE = 'ExchangeDatabase';

    /**
     * An Exchange Mailbox User object.
     */
    const EXCHANGE_MAILBOX_USER = 'ExchangeMailboxUser';

    /**
     * An Exchange ActiveSync Policy object.
     */
    const EXCHANGE_ACTIVESYNC_POLICY = 'ExchangeActiveSyncPolicy';
    
    /**
     * An Exchange Recipient Policy object.
     */
    const EXCHANGE_RECIPIENT_POLICY = 'ExchangeRecipientPolicy';

    /**
     * An Exchange RBAC policy object.
     */
    const EXCHANGE_RBAC_POLICY = 'ExchangeRBACPolicy';

    /**
     * An Exchange Transport Rule object.
     */
    const EXCHANGE_TRANSPORT_RULE = 'ExchangeTransportRule';

    /**
     * An Exchange DAG object.
     */
    const EXCHANGE_DAG = 'ExchangeDAG';

    /**
     * An Exchange OWA object.
     */
    const EXCHANGE_OWA = 'ExchangeOWA';
}
