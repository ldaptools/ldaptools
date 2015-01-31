<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Query;

/**
 * Defines various LDAP matching rule OIDs.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class MatchingRuleOid
{
    /**
     * Equivalent to a bitwise AND operation.
     */
    const BIT_AND = '1.2.840.113556.1.4.803';

    /**
     * Equivalent to a bitwise OR operation.
     */
    const BIT_OR = '1.2.840.113556.1.4.804';

    /**
     * Walks the chain of ancestry in objects all the way to the root until it finds a match.
     */
    const IN_CHAIN = '1.2.840.113556.1.4.1941';
}
