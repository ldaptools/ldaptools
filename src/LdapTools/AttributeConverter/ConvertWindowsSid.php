<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\AttributeConverter;

/**
 * Converts a binary objectSid to a string representation and also from it's string representation back to hex for
 * searches.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertWindowsSid implements AttributeConverterInterface
{
    use AttributeConverterTrait;

    /**
     * {@inheritdoc}
     */
    public function toLdap($sid)
    {
        $sid = ltrim($sid, 'S-');
        $sid = explode('-', $sid);

        $revLevel = array_shift($sid);
        $authIdent = array_shift($sid);
        $id = array_shift($sid);

        $sidHex = str_pad(dechex($revLevel), 2, '0', STR_PAD_LEFT);
        $sidHex .= str_pad(dechex($authIdent), 2, '0', STR_PAD_LEFT);
        $sidHex .= str_pad(dechex($authIdent), 12, '0', STR_PAD_LEFT);
        $sidHex .= str_pad(dechex($id), 8, '0', STR_PAD_RIGHT);

        foreach ($sid as $subAuth) {
            // little endian, so reverse the hex order.
            $sidHex .= implode('', array_reverse(
                // After going from dec to hex, pad it and split it into hex chunks so it can be reversed.
                str_split(str_pad(dechex($subAuth), 8, '0', STR_PAD_LEFT), 2))
            );
        }
        // All hex parts must have a leading backslash for the search.
        $sidHex = str_split($sidHex, '2');

        return '\\'.implode('\\', $sidHex);
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($sid)
    {
        // How to unpack all of this in one statement to avoid resorting to hexdec? Is it even possible?
        $sidHex = unpack('H*hex', $sid)['hex'];
        $subAuths = unpack('H2/H2/n/N/V*', $sid);

        $revLevel = hexdec(substr($sidHex, 0, 2));
        $authIdent = hexdec(substr($sidHex, 4, 12));

        return 'S-'.$revLevel.'-'.$authIdent.'-'.implode('-', $subAuths);
    }
}
