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
 * Constants representing the Active Directory functional level types.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ADFunctionalLevelType
{
    /**
     * Windows 2000 Native
     */
    const WIN2000 = 0;

    /**
     * Windows Server 2003 Interim
     */
    const WIN2003_MIXED_DOMAIN = 1;

    /**
     * Windows Server 2003
     */
    const WIN2003 = 2;

    /**
     * Windows Server 2008
     */
    const WIN2008 = 3;

    /**
     * Windows Server 2008 R2
     */
    const WIN2008R2 = 4;

    /**
     * Windows Server 2012
     */
    const WIN2012 = 5;

    /**
     * Windows Server 2012 R2
     */
    const WIN2012R2 = 6;

    /**
     * Windows Server 2016
     */
    const WIN2016 = 7;

    /**
     * Maps the types to their readable names.
     */
    const TYPES = [
        self::WIN2000 => 'Windows 2000 Native',
        self::WIN2003_MIXED_DOMAIN => 'Windows Server 2003 Interim',
        self::WIN2003 => 'Windows Server 2003',
        self::WIN2008 => 'Windows Server 2008',
        self::WIN2008R2 => 'Windows Server 2008 R2',
        self::WIN2012 => 'Windows Server 2012',
        self::WIN2012R2 => 'Windows Server 2012 R2',
        self::WIN2016 => 'Windows Server 2016',
    ];
}
