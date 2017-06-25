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
 * Email Lifecycle (ELC) mailbox flags.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ELCMailbox implements FlagEnumInterface
{
    use FlagEnumTrait;

    const RetentionEnabled = 1;

    const MRMEnabled = 2;

    const CalendarLoggingDisabled = 4;

    const LitigationEnabled = 8;

    const SingleItemRecoveryEnabled = 16;

    const ArchiveDatabaseValid = 32;
}
