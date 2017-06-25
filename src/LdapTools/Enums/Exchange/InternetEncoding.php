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
 * Internet encoding types for mail-enabled objects.
 *
 * These values come from the Active Directory Cookbook (O'Reilly). I'm unable to find any official documentation from
 * Microsoft, including their Open Specification docs, that verifies these values. So I'm unsure as to the original source.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class InternetEncoding implements SimpleEnumInterface
{
    use SimpleEnumTrait;

    const MailServiceSettings = 1310720;

    const PlainText = 917504;

    const PlainTextOrHtml = 1441792;

    const PlainTextUuencoding  = 2228224;

    const PlainTextUuencodingBinHex = 131072;
}
