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
 * Exchange User Account Control values. Unlike the typical userAccountControl, this will only ever be one of 2 values.
 * Either enabled (0) or disabled (2).
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ExchangeUserAccountControl implements SimpleEnumInterface
{
    use SimpleEnumTrait;

    const Enabled = 0;

    const Disabled = 2;
}
