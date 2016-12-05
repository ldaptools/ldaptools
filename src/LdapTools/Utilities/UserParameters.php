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
 * Represents userParameters data. Contains dial-in data and the TSPropertyArray data within separate objects. Allows
 * for safe encoding/decoding/modifying/creating of all userParameters data.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class UserParameters
{
    /**
     * @var array Possible reserved data values
     */
    const RESERVED_DATA_VALUE = [
        'RDS' => 'CtxCfgPresent',
        'NPS' => 'm:',
        'NPS_RDS' => 'mtxCfgPresent'
    ];

    /**
     * @var string Binary data that occurs before the dial-in data.
     */
    protected $preBinary = '';

    /**
     * @var string Binary data that occurs after the TSPropertyArray data.
     */
    protected $postBinary = '';

    /**
     * @var TSPropertyArray|null
     */
    protected $tsPropertyArray;

    /**
     * @var DialInData|null
     */
    protected $dialInData;

    /**
     * @param null|string|TSPropertyArray|DialInData $userParameters
     */
    public function __construct($userParameters = null)
    {
        if (is_string($userParameters)) {
            $this->decode($userParameters);
        } elseif ($userParameters instanceof TSPropertyArray) {
            $this->tsPropertyArray = $userParameters;
        } elseif ($userParameters instanceof DialInData) {
            $this->dialInData = $userParameters;
        }
    }

    /**
     * Get the DialInData.
     *
     * @return DialInData|null
     */
    public function getDialInData()
    {
        return $this->dialInData;
    }

    /**
     * Set the DialInData.
     *
     * @param DialInData|null $dialInData
     * @return $this
     */
    public function setDialInData(DialInData $dialInData = null)
    {
        $this->dialInData = $dialInData;

        return $this;
    }

    /**
     * Get the TSPropertyArray data.
     *
     * @return TSPropertyArray|null
     */
    public function getTSPropertyArray()
    {
        return $this->tsPropertyArray;
    }

    /**
     * Set the TSPropertyArray data.
     *
     * @param TSPropertyArray|null $tsPropertyArray
     * @return $this
     */
    public function setTSPropertyArray(TSPropertyArray $tsPropertyArray = null)
    {
        $this->tsPropertyArray = $tsPropertyArray;

        return $this;
    }

    /**
     * Get the userParameters in binary form that can be saved back to LDAP.
     *
     * @return string
     */
    public function toBinary()
    {
        /**
         * @todo There is no documentation on the reserved data, but this is how it seems to act. May need to change this.
         */
        if (!$this->tsPropertyArray && !$this->dialInData) {
            $binary = $this->encodeReservedData('');
        } elseif ($this->tsPropertyArray && $this->dialInData) {
            $binary = $this->encodeReservedData(self::RESERVED_DATA_VALUE['NPS_RDS']);
        } elseif ($this->dialInData) {
            $binary = $this->encodeReservedData(self::RESERVED_DATA_VALUE['NPS']);
        } else {
            $binary = $this->encodeReservedData(self::RESERVED_DATA_VALUE['RDS']);
        }
        
        $binary .= $this->dialInData ? $this->dialInData->toBinary() : hex2bin(str_pad('', 52, '20'));
        if ($this->tsPropertyArray) {
            $binary .= $this->tsPropertyArray->toBinary();
        }

        return $binary.$this->postBinary;
    }

    /**
     * Get the binary data that comes after the TSPropertyArray data.
     *
     * @return string
     */
    public function getPostBinary()
    {
        return $this->postBinary;
    }

    /**
     * Set the binary data that should come after the TSPropertyArray data. This needs to be in binary form, not hex.
     *
     * @param string $binary
     * @return $this
     */
    public function setPostBinary($binary)
    {
        $this->postBinary = $binary;
        
        return $this;
    }

    /**
     * Get the decoded reserved data string, if any is set. These values don't seem to be documented anywhere.
     *
     * @return string
     */
    public function getReservedDataString()
    {
        return $this->preBinary;
    }
    
    /**
     * @param string $userParameters
     */
    protected function decode($userParameters)
    {
        $hex = bin2hex($userParameters);
        $this->preBinary = rtrim(pack('H*', substr($hex, 0, 44)));
        
        // Check if any dial-in data is set first.
        $dialInData = substr($hex, 44, 52);
        if ($dialInData != str_pad('', 52, '20')) {
            $this->dialInData = new DialInData(hex2bin($dialInData));
        }
        
        // It's possible there is no TSPropertyArray data and only dial-in data is set.
        if (strlen($hex) > 96) {
            $this->tsPropertyArray = new TSPropertyArray(hex2bin(substr($hex, 96)));
            $this->postBinary = $this->tsPropertyArray->getPostBinary();
        }
    }

    /**
     * Encode the string of reserved data that goes in the first 44 bytes.
     *
     * @param string $reserved
     * @return string
     */
    protected function encodeReservedData($reserved = '')
    {
        return hex2bin(str_pad(unpack('H*', $reserved)[1], 44, '20', STR_PAD_RIGHT));
    }
}
