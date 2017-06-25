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
 * The possible flags that can be assigned to a groupType attribute.
 *
 * @see https://msdn.microsoft.com/en-us/library/cc223142.aspx
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class GroupType implements FlagEnumInterface
{
    use FlagEnumTrait;

    /**
     * A builtin group created by the system. This type of group cannot be created by the client.
     */
    const BuiltinGroup = 1;

    /**
     * A global type group.
     */
    const GlobalGroup = 2;

    /**
     * A domain local type group.
     */
    const DomainLocalGroup = 4;

    /**
     * A universal type group.
     */
    const UniversalGroup = 8;

    /**
     * An APP_BASIC group type for Windows Server Authorization Manager.
     */
    const AppBasic = 16;

    /**
     * An APP_QUERY group for Windows Server Authorization Manager.
     */
    const AppQuery = 32;

    /**
     * Specifies whether a group is security enabled. If this is not set, then it is a distribution type group.
     */
    const SecurityEnabled = 2147483648;
}
