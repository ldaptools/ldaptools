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
 * Exchange Message Moderation type flags.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class MessageModeration implements FlagEnumInterface
{
    use FlagEnumTrait;

    const None = 0;

    const Internal = 2;

    const External = 4;

    const All = 6;
}
