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
        $subAuthCount = count($sid);

        $sidHex = str_pad(dechex($revLevel), 2, '0', STR_PAD_LEFT);
        $sidHex .= str_pad(dechex($subAuthCount), 2, '0', STR_PAD_LEFT);
        $sidHex .= str_pad(dechex($authIdent), 12, '0', STR_PAD_LEFT);

        foreach ($sid as $subAuth) {
            $sidHex .= $this->leDec2hex($subAuth);
        }

        if ($this->getOperationType() == self::TYPE_CREATE || $this->getOperationType() == self::TYPE_MODIFY) {
            $sidData = hex2bin($sidHex);
        } else {
            // All hex parts must have a leading backslash for the search.
            $sidData = '\\'.implode('\\', str_split($sidHex, '2'));
        }

        return $sidData;
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($value)
    {
        $sidHex = bin2hex($value);

        $revLevel = hexdec(substr($sidHex, 0, 2));
        $subAuthCount = hexdec(substr($sidHex, 2, 2));
        $authIdent = hexdec(substr($sidHex, 4, 12));

        $sid = 'S-'.$revLevel.'-'.$authIdent;
        if ($subAuthCount > 0) {
            $subAuths = unpack('V*', hex2bin(substr($sidHex, 16)));
            $sid .= '-'.implode('-', $subAuths);
        }

        return $sid;
    }

    /**
     * Converts a decimal to little-endian hex form.
     *
     * @param int $dec
     * @return string
     */
    protected function leDec2hex($dec)
    {
        return implode('', array_reverse(
            // After going from dec to hex, pad it and split it into hex chunks so it can be reversed.
            str_split(str_pad(str_pad(dechex($dec), 2, '0', STR_PAD_LEFT), 8, '0', STR_PAD_LEFT), 2))
        );
    }
}
