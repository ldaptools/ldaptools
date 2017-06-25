<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Enums\AD;

use Enums\FlagEnumInterface;
use Enums\FlagEnumTrait;

/**
 * These values control whether a GPO link is ignored/enforced.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class GpoOptions implements FlagEnumInterface
{
    use FlagEnumTrait;

    const NotIgnoredNotEnforced = 0;

    const Ignored = 1;

    const Enforced = 2;

    const IgnoredEnforced = 3;
}
