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

use LdapTools\Exception\InvalidArgumentException;

/**
 * Represents TSPropertyArray data that contains individual TSProperty structures in a userParameters value.
 *
 * @see https://msdn.microsoft.com/en-us/library/ff635189.aspx
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class TSPropertyArray
{
    /**
     * Represents that the TSPropertyArray data is valid.
     */
    const VALID_SIGNATURE = 'P';

    /**
     * @var array The default values for the TSPropertyArray structure.
     */
    const DEFAULTS = [
        'CtxCfgPresent' => 2953518677,
        'CtxWFProfilePath' => '',
        'CtxWFProfilePathW' => '',
        'CtxWFHomeDir' => '',
        'CtxWFHomeDirW' => '',
        'CtxWFHomeDirDrive' => '',
        'CtxWFHomeDirDriveW' => '',
        'CtxShadow' => 1,
        'CtxMaxDisconnectionTime' => 0,
        'CtxMaxConnectionTime' => 0,
        'CtxMaxIdleTime' => 0,
        'CtxWorkDirectory' => '',
        'CtxWorkDirectoryW' => '',
        'CtxCfgFlags1' => 2418077696,
        'CtxInitialProgram' => '',
        'CtxInitialProgramW' => '',
    ];
    
    /**
     * @var TSProperty[]
     */
    protected $tsProperty = [];

    /**
     * @var string
     */
    protected $signature = self::VALID_SIGNATURE;

    /**
     * @var string
     */
    protected $postBinary = '';

    /**
     * Construct in one of the following ways:
     *
     *   - Pass an array of TSProperty key => value pairs (See DEFAULTS constant).
     *   - Pass the TSPropertyArray binary value. The object representation of that will be decoded and constructed.
     *   - Pass nothing and a default set of TSProperty key => value pairs will be used (See DEFAULTS constant).
     *
     * @param mixed $tsPropertyArray
     */
    public function __construct($tsPropertyArray = null)
    {
        if (is_null($tsPropertyArray) || is_array($tsPropertyArray)) {
            $tsPropertyArray = $tsPropertyArray ?: self::DEFAULTS;
            foreach ($tsPropertyArray as $key => $value) {
                $tsProperty = new TSProperty();
                $tsProperty->setName($key);
                $tsProperty->setValue($value);
                $this->tsProperty[$key] = $tsProperty;
            }
        } else {
            $this->decode($tsPropertyArray);
        }
    }

    /**
     * Check if a specific TSProperty exists by its property name.
     *
     * @param string $propName
     * @return bool
     */
    public function has($propName)
    {
        return array_key_exists(MBString::strtolower($propName), MBString::array_change_key_case($this->tsProperty));
    }

    /**
     * Get a TSProperty object by its property name (ie. CtxWFProfilePath).
     *
     * @param string $propName
     * @return TSProperty
     */
    public function get($propName)
    {
        $this->validateProp($propName);

        return $this->getTsPropObj($propName)->getValue();
    }

    /**
     * Add a TSProperty object. If it already exists, it will be overwritten.
     *
     * @param TSProperty $tsProperty
     * @return $this
     */
    public function add(TSProperty $tsProperty)
    {
        $this->tsProperty[$tsProperty->getName()] = $tsProperty;

        return $this;
    }

    /**
     * Remove a TSProperty by its property name (ie. CtxMinEncryptionLevel).
     *
     * @param string $propName
     * @return $this
     */
    public function remove($propName)
    {
        foreach (array_keys($this->tsProperty) as $property) {
            if (MBString::strtolower($propName) == MBString::strtolower($property)) {
                unset($this->tsProperty[$property]);
            }
        }
        
        return $this;
    }

    /**
     * Set the value for a specific TSProperty by its name.
     *
     * @param string $propName
     * @param mixed $propValue
     * @return $this
     */
    public function set($propName, $propValue)
    {
        $this->validateProp($propName);
        $this->getTsPropObj($propName)->setValue($propValue);
        
        return $this;
    }

    /**
     * Get the full binary representation of the userParameters containing the TSPropertyArray data.
     *
     * @return string
     */
    public function toBinary()
    {
        $binary = hex2bin(str_pad(dechex(MBString::ord($this->signature)), 2, 0, STR_PAD_LEFT));
        $binary .= hex2bin(str_pad(dechex(count($this->tsProperty)), 2, 0, STR_PAD_LEFT));
        foreach ($this->tsProperty as $tsProperty) {
            $binary .= $tsProperty->toBinary();
        }

        return $binary;
    }

    /**
     * Get a simple associative array containing of all TSProperty names and values.
     *
     * @return array
     */
    public function toArray()
    {
        $userParameters = [];
        
        foreach ($this->tsProperty as $property => $tsPropObj) {
            $userParameters[$property] = $tsPropObj->getValue();
        }
        
        return $userParameters;
    }
    
    /**
     * Get all TSProperty objects.
     *
     * @return TSProperty[]
     */
    public function getTSProperties()
    {
        return $this->tsProperty;
    }

    /**
     * Get any binary data that was after the decoded binary TSPropertyArray data.
     *
     * @return string
     */
    public function getPostBinary()
    {
        return $this->postBinary;
    }

    /**
     * Get the signature value for the TSPropertyArray data.
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
     * @param string $propName
     */
    protected function validateProp($propName)
    {
        if (!$this->has($propName)) {
            throw new InvalidArgumentException(sprintf('TSProperty for "%s" does not exist.', $propName));
        }
    }
    
    /**
     * @param string $propName
     * @return TSProperty
     */
    protected function getTsPropObj($propName)
    {
        return MBString::array_change_key_case($this->tsProperty)[MBString::strtolower($propName)];
    }
    
    /**
     * Given the TSPropertyArray binary data, extract out all of the TSProperty values.
     *
     * @param string $tsPropArray
     * @return array
     */
    protected function decode($tsPropArray)
    {
        $tsPropArray = bin2hex($tsPropArray);
        // The signature is a 2-byte unicode character at the front
        $this->signature = MBString::chr(hexdec(substr($tsPropArray, 0, 2)));
        // The property count is a 2-byte unsigned integer indicating the number of elements for the tsPropertyArray
        // It starts at position 2. The actual variable data begins at position 4.
        $length = $this->addTSPropData(substr($tsPropArray, 4), hexdec(substr($tsPropArray, 2, 2)));
        // Reserved data length + (count and sig length == 4) + the added lengths of the TSPropertyArray
        // This saves anything after that variable TSPropertyArray data, so as to not squash anything stored there
        if (strlen($tsPropArray) > (4 + $length)) {
            $this->postBinary = hex2bin(substr($tsPropArray, (4 + $length)));
        }
    }

    /**
     * Given the start of TSPropertyArray hex data, and the count for the number of TSProperty structures in contains,
     * parse and split out the individual TSProperty structures. Return the full length of the TSPropertyArray data.
     *
     * @param string $tsPropertyArray
     * @param int $tsPropCount
     * @return int The length of the data in the TSPropertyArray
     */
    protected function addTSPropData($tsPropertyArray, $tsPropCount)
    {
        $length = 0;
        
        for ($i = 0; $i < $tsPropCount; $i++) {
            // Prop length = name length + value length + type length + the space for the length data.
            $propLength = hexdec(substr($tsPropertyArray, $length, 2)) + (hexdec(substr($tsPropertyArray, $length + 2, 2)) * 3) + 6;
            $tsProperty = new TSProperty(hex2bin(substr($tsPropertyArray, $length, $propLength)));
            $this->tsProperty[$tsProperty->getName()] = $tsProperty;
            $length += $propLength;
        }
        
        return $length;
    }
}
