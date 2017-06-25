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
 * Exchange versions that get stamped on certain AD objects that indicate a specific version of Exchange. However, it
 * did not change between 2013 and 2016. Seems to have been used for certain Exchange processes to determine how to treat
 * an object based on the version it was created/modified in.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ObjectVersion implements SimpleEnumInterface
{
    use SimpleEnumTrait;

    const v2007 = 4535486012416;

    const v2010 = 44220983382016;

    const v2013 = 88218628259840;

    const v2016 = 88218628259840;
}
