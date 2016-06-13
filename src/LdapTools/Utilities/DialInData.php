<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Utilities;

/**
 * Represents the dial-in data binary value within the userParameters binary blob. The dial-in data starts at position
 * 44 and goes until the TSPropertyArray data (position 96).
 *
 * @todo Add CallbackPhoneNumber encoding/decoding. Unsure as to how it is supposed to be done.
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class DialInData
{
    /**
     * Represents that the dial-in data is valid.
     */
    const VALID_SIGNATURE = 'd';

    /**
     * @var int Dial-in bit-mask (NoCallback = 1, AdminSetCallback = 2, CallerSetCallback = 4, DialinPrivilege = 8)
     */
    protected $userPrivilege = 1;

    /**
     * @var string Hex representation of a phone number configured for the user on which the answerer should call back.
     */
    protected $callbackPhoneNumber = '202020202020202020202020202020202020202020202020';

    /**
     * @var string
     */
    protected $signature = self::VALID_SIGNATURE;

    /**
     * @param string|null $dialInData
     */
    public function __construct($dialInData = null)
    {
        if ($dialInData) {
            $this->decode($dialInData);
        }
    }

    /**
     * Get the UserPrivilege bit-mask.
     *
     * @return int
     */
    public function getUserPrivilege()
    {
        return $this->userPrivilege;
    }

    /**
     * Set the UserPrivilege bit-mask.
     *
     * @param int $userPrivilege
     * @return $this
     */
    public function setUserPrivilege($userPrivilege)
    {
        $this->userPrivilege = $userPrivilege;

        return $this;
    }

    /**
     * Get the CallbackPhoneNumber as a hex value. The encoding process for this value is currently unknown.
     * 
     * @return string
     */
    public function getCallbackPhoneNumber()
    {
        return $this->callbackPhoneNumber;
    }

    /**
     * Set the CallbackPhoneNumber as a hex value. The encoding process for this value is currently unknown.
     * 
     * @param string $callbackPhoneNumber
     * @return $this
     */
    public function setCallbackPhoneNumber($callbackPhoneNumber)
    {
        $this->callbackPhoneNumber = $callbackPhoneNumber;

        return $this;
    }

    /**
     * Get the signature value for the dial-in data.
     * 
     * @return string
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * Check whether the signature is valid, thus signifying a valid data structure.
     * 
     * @return bool
     */
    public function isSignatureValid()
    {
        return $this->signature == self::VALID_SIGNATURE;
    }

    /**
     * Get the binary representation of the dial-in data for the userParameters value.
     *
     * @return string
     */
    public function toBinary()
    {
        $binary = hex2bin(str_pad(dechex(MBString::ord($this->signature)), 2, 0, STR_PAD_LEFT));
        $binary .= hex2bin($this->dec2hex($this->userPrivilege));
        $binary .= hex2bin($this->callbackPhoneNumber);
        
        return $binary;
    }

    /**
     * Decode and parse the binary dial-in data from the userParameters attribute (the data between 44 and 96 bytes).
     * 
     * @param string $binary
     */
    protected function decode($binary)
    {
        $hex = bin2hex($binary);
        $this->signature = MBString::chr(hexdec(substr($hex, 0, 2)));
        $this->userPrivilege = hexdec(substr($hex, 2, 2));
        $this->callbackPhoneNumber = substr($hex, 4, 48);
    }

    /**
     * Need to make sure hex values are always an even length, so pad as needed.
     *
     * @param int $int
     * @param int $padLength The hex string must be padded to this length (with zeros).
     * @return string
     */
    protected function dec2hex($int, $padLength = 2)
    {
        return str_pad(dechex($int), $padLength, 0, STR_PAD_LEFT);
    }
}
