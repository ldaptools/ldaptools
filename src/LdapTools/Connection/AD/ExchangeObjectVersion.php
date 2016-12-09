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
 * Represents version information stored in the msExchVersion attribute for a mail-enabled user/contact/etc.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ExchangeObjectVersion
{
    const VERSION = [
        '2007' => 4535486012416,
        '2010' => 44220983382016,
        '2013' => 88218628259840,
        '2016' => 88218628259840,
    ];
}
