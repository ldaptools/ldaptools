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
 * Converts the Exchange version from the number to the actual familiar name.
 *
 * @link https://technet.microsoft.com/en-us/library/hh135098%28v=exchg.150%29.aspx
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertExchangeVersion implements AttributeConverterInterface
{
    use AttributeConverterTrait;

    /**
     * Matches an Exchange version number in the serialNumber attribute.
     */
    const VERSION_REGEX = "/^Version (\d+)\.(\d+)?.*Build ([\d.]+).*$/";
    
    /**
     * @var array A version map for the main Exchange versions.
     */
    protected $versionsMajor = [
        '06.00' => 'Exchange 2000',
        '06.05' => 'Exchange 2003',
        '08' => 'Exchange 2007',
        '14' => 'Exchange 2010',
        '15.00' => 'Exchange 2013',
        '15.01' => 'Exchange 2016',
    ];

    /**
     * @var array An array of build numbers to provide more specific information about the version (SP and/or CU)
     */
    protected $builds = [
        '15.01.0396.030' => 'Exchange Server 2016 CU1',
        '15.01.0225.042' => 'Exchange Server 2016 RTM',
        '15.01.0225.016' => 'Exchange 2016 Preview',
        '15.00.1178.004' => 'Exchange Server 2013 CU12',
        '15.00.1156.006' => 'Exchange Server 2013 CU11',
        '15.00.1130.007' => 'Exchange Server 2013 CU10',
        '15.00.1104.005' => 'Exchange Server 2013 CU9',
        '15.00.1076.009' => 'Exchange Server 2013 CU8',
        '15.00.1044.025' => 'Exchange Server 2013 CU7',
        '15.00.0995.029' => 'Exchange Server 2013 CU6',
        '15.00.0913.022' => 'Exchange Server 2013 CU5',
        '15.00.0847.032' => 'Exchange Server 2013 SP1',
        '15.00.0775.038' => 'Exchange Server 2013 CU3',
        '15.00.0712.024' => 'Exchange Server 2013 CU2',
        '15.00.0620.029' => 'Exchange Server 2013 CU1',
        '15.00.0516.032' => 'Exchange Server 2013 RTM',
        '14.03.0294.000' => 'Exchange Server 2010 SP3 Update Rollup 13',
        '14.03.0279.002' => 'Exchange Server 2010 SP3 Update Rollup 12',
        '14.03.0266.002' => 'Exchange Server 2010 SP3 Update Rollup 11',
        '14.03.0248.002' => 'Exchange Server 2010 SP3 Update Rollup 10',
        '14.03.0235.001' => 'Exchange Server 2010 SP3 Update Rollup 9',
        '14.03.0224.002' => 'Exchange Server 2010 SP3 Update Rollup 8 v2',
        '14.03.0224.001' => 'Exchange Server 2010 SP3 Update Rollup 8 v1',
        '14.03.0210.002' => 'Exchange Server 2010 SP3 Update Rollup 7',
        '14.03.0195.001' => 'Exchange Server 2010 SP3 Update Rollup 6',
        '14.03.0181.006' => 'Exchange Server 2010 SP3 Update Rollup 5',
        '14.03.0174.001' => 'Exchange Server 2010 SP3 Update Rollup 4',
        '14.03.0169.001' => 'Exchange Server 2010 SP3 Update Rollup 3',
        '14.03.0158.001' => 'Exchange Server 2010 SP3 Update Rollup 2',
        '14.03.0146.000' => 'Exchange Server 2010 SP3 Update Rollup 1',
        '14.03.0123.004' => 'Exchange Server 2010 SP3',
        '14.02.0390.003' => 'Exchange Server 2010 SP2 Update Rollup 8',
        '14.02.0375.000' => 'Exchange Server 2010 SP2 Update Rollup 7',
        '14.02.0342.003' => 'Exchange Server 2010 SP2 Update Rollup 6',
        '14.02.0328.010' => 'Exchange Server 2010 SP2 Update Rollup 5 v2',
        '14.03.0328.005' => 'Exchange Server 2010 SP2 Update Rollup 5 v1',
        '14.02.0318.004' => 'Exchange Server 2010 SP2 Update Rollup 4 v2',
        '14.02.0318.002' => 'Exchange Server 2010 SP2 Update Rollup 4 v1',
        '14.02.0309.002' => 'Exchange Server 2010 SP2 Update Rollup 3',
        '14.02.0298.004' => 'Exchange Server 2010 SP2 Update Rollup 2',
        '14.02.0283.003' => 'Exchange Server 2010 SP2 Update Rollup 1',
        '14.02.0247.005' => 'Exchange Server 2010 SP2',
        '14.01.0438.000' => 'Exchange Server 2010 SP1 Update Rollup 8',
        '14.01.0421.003' => 'Exchange Server 2010 SP1 Update Rollup 7 v3',
        '14.01.0421.002' => 'Exchange Server 2010 SP1 Update Rollup 7 v2',
        '14.01.0421.000' => 'Exchange Server 2010 SP1 Update Rollup 7 v1',
        '14.01.0355.002' => 'Exchange Server 2010 SP1 Update Rollup 6',
        '14.01.0339.001' => 'Exchange Server 2010 SP1 Update Rollup 5',
        '14.01.0323.006' => 'Exchange Server 2010 SP1 Update Rollup 4',
        '14.01.0289.007' => 'Exchange Server 2010 SP1 Update Rollup 3',
        '14.01.0270.001' => 'Exchange Server 2010 SP1 Update Rollup 2',
        '14.01.0255.002' => 'Exchange Server 2010 SP1 Update Rollup 1',
        '14.01.0218.015' => 'Exchange Server 2010 SP1',
        '14.00.0726.000' => 'Exchange Server 2010 Update Rollup 5',
        '14.00.0702.001' => 'Exchange Server 2010 Update Rollup 4',
        '14.00.0694.000' => 'Exchange Server 2010 Update Rollup 3',
        '14.00.0689.000' => 'Exchange Server 2010 Update Rollup 2',
        '14.00.0682.001' => 'Exchange Server 2010 Update Rollup 1',
        '14.00.0639.021' => 'Exchange Server 2010 RTM',
        '08.03.0445.000' => 'Exchange Server 2007 SP3 Update Rollup 18',
        '08.03.0417.001' => 'Exchange Server 2007 SP3 Update Rollup 17',
        '08.03.0406.000' => 'Exchange Server 2007 SP3 Update Rollup 16',
        '08.03.0389.002' => 'Exchange Server 2007 SP3 Update Rollup 15',
        '08.03.0379.002' => 'Exchange Server 2007 SP3 Update Rollup 14',
        '08.03.0348.002' => 'Exchange Server 2007 SP3 Update Rollup 13',
        '08.03.0342.004' => 'Exchange Server 2007 SP3 Update Rollup 12',
        '08.03.0327.001' => 'Exchange Server 2007 SP3 Update Rollup 11',
        '08.03.0298.003' => 'Exchange Server 2007 SP3 Update Rollup 10',
        '08.03.0297.002' => 'Exchange Server 2007 SP3 Update Rollup 9',
        '08.03.0279.006' => 'Exchange Server 2007 SP3 Update Rollup 8 v3',
        '08.03.0279.005' => 'Exchange Server 2007 SP3 Update Rollup 8 v2',
        '08.03.0279.003' => 'Exchange Server 2007 SP3 Update Rollup 8 v1',
        '08.03.0264.000' => 'Exchange Server 2007 SP3 Update Rollup 7',
        '08.03.0245.002' => 'Exchange Server 2007 SP3 Update Rollup 6',
        '08.03.0213.001' => 'Exchange Server 2007 SP3 Update Rollup 5',
        '08.03.0192.001' => 'Exchange Server 2007 SP3 Update Rollup 4',
        '08.03.0159.002' => 'Exchange Server 2007 SP3 Update Rollup 3 v2',
        '08.03.0137.003' => 'Exchange Server 2007 SP3 Update Rollup 2',
        '08.03.0106.002' => 'Exchange Server 2007 SP3 Update Rollup 1',
        '08.03.0083.006' => 'Exchange Server 2007 SP3',
        '08.02.0305.003' => 'Exchange Server 2007 SP2 Update Rollup 5',
        '08.02.0254.000' => 'Exchange Server 2007 SP2 Update Rollup 4',
        '08.02.0247.002' => 'Exchange Server 2007 SP2 Update Rollup 3',
        '08.02.0234.001' => 'Exchange Server 2007 SP2 Update Rollup 2',
        '08.02.0217.003' => 'Exchange Server 2007 SP2 Update Rollup 1',
        '08.02.0176.002' => 'Exchange Server 2007 SP2',
        '08.01.0436.000' => 'Exchange Server 2007 SP1 Update Rollup 10',
        '08.01.0393.001' => 'Exchange Server 2007 SP1 Update Rollup 9',
        '08.01.0375.002' => 'Exchange Server 2007 SP1 Update Rollup 8',
        '08.01.0359.002' => 'Exchange Server 2007 SP1 Update Rollup 7',
        '08.01.0340.001' => 'Exchange Server 2007 SP1 Update Rollup 6',
        '08.01.0336.001' => 'Exchange Server 2007 SP1 Update Rollup 5',
        '08.01.0311.003' => 'Exchange Server 2007 SP1 Update Rollup 4',
        '08.01.0291.002' => 'Exchange Server 2007 SP1 Update Rollup 3',
        '08.01.0278.002' => 'Exchange Server 2007 SP1 Update Rollup 2',
        '08.01.0263.001' => 'Exchange Server 2007 SP1 Update Rollup 1',
        '08.01.0240.006' => 'Exchange Server 2007 SP1',
        '08.00.0813.000' => 'Exchange Server 2007 Update Rollup 7',
        '08.00.0783.002' => 'Exchange Server 2007 Update Rollup 6',
        '08.00.0754.000' => 'Exchange Server 2007 Update Rollup 5',
        '08.00.0744.000' => 'Exchange Server 2007 Update Rollup 4',
        '08.00.0730.001' => 'Exchange Server 2007 Update Rollup 3',
        '08.00.0711.002' => 'Exchange Server 2007 Update Rollup 2',
        '08.00.0708.003' => 'Exchange Server 2007 Update Rollup 1',
        '08.00.0685.025' => 'Exchange Server 2007 RTM',
    ];

    /**
     * {@inheritdoc}
     */
    public function fromLdap($value)
    {
        $version = $value;

        if (!preg_match(self::VERSION_REGEX, $value, $matches)) {
            return $version;
        }

        return $this->getFriendlyName(...$this->getBuildNumbers($matches));
    }

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
        return $value;
    }

    /**
     * @param string $fullBuild
     * @param string $major
     * @param string $minor
     * @return string
     */
    protected function getFriendlyName($fullBuild, $major, $minor)
    {
        $friendly = null;

        // Check for a full build number match for the most info first...
        if (isset($this->builds[$fullBuild])) {
            $friendly = $this->builds[$fullBuild];
            // Next see if the general version as a combination of 2 numbers...
        } elseif (isset($this->versionsMajor[$major . '.' . $minor])) {
            $friendly = $this->versionsMajor[$major . '.' . $minor];
            // Lastly see if the major version is recognized if the rest fails...
        } elseif (isset($this->versionsMajor[$major])) {
            $friendly = $this->versionsMajor[$major];
        }

        // If all else fails, at least display the full build.
        if (is_null($friendly)) {
            $friendly = 'Build ' . $fullBuild;
        } else {
            $friendly .= " (Build $fullBuild)";
        }

        return $friendly;
    }

    /**
     * @param array $matches
     * @return array
     */
    protected function getBuildNumbers($matches)
    {
        $major = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        $minor = isset($matches[2]) ? str_pad($matches[2], 2, '0', STR_PAD_LEFT) : 0;
        $revision = isset($matches[3]) ? $this->parseRevision($matches[3]) : 0;
        $fullBuild = $major . '.' . $minor . '.' . $revision;

        return [$fullBuild, $major, $minor];
    }

    /**
     * @param string $revision
     * @return  string
     */
    protected function parseRevision($revision)
    {
        $revision = explode('.', $revision);

        /**
         * Is this really the correct way to do this? I'm not sure why, but the build number exposed in LDAP seems to
         * start with extra numbers that don't relate to anything. That is why the first part is grabbing that last 4
         * numbers. The others mean something else? Tested in an Exchange 2013 environment anyway.
         */
        if (count($revision) >= 1) {
            $revision[0] = count($revision) == 1 ? $revision[0] : substr($revision[0], -4);
        }
        if (count($revision) == 2) {
            $revision[1] = str_pad($revision[1], 3, '0', STR_PAD_LEFT);
        }

        return implode('.', $revision);
    }
}
