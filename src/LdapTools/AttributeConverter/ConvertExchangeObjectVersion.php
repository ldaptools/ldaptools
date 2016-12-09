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

use LdapTools\Exception\AttributeConverterException;
use LdapTools\Query\LdapQueryBuilder;
use LdapTools\Connection\AD\ExchangeObjectVersion;

/**
 * Converts the msExchVersion value that is stamped on mail-enabled objects.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertExchangeObjectVersion extends ConvertExchangeVersion
{
    /**
     * @var array A simple map for major build numbers that have recognized msExchVersion numbers.
     */
    protected $buildMap =[
        '08' => '2007',
        '14' => '2010',
        '15.00' => '2013',
        '15.01' => '2016',
    ];

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
        $value = strtolower($value);

        if ($value == 'auto') {
            $msExchVersion = ExchangeObjectVersion::VERSION[$this->getMsExchVersion()];
        } elseif (array_key_exists($value, ExchangeObjectVersion::VERSION)) {
            $msExchVersion = ExchangeObjectVersion::VERSION[$value];
        } else {
            throw new AttributeConverterException(sprintf(
               'Version name "%s" is not recognized. Recognized values are: %s',
                $value,
                'auto, '.implode(', ', array_keys(ExchangeObjectVersion::VERSION))
            ));
        }

        return (string) $msExchVersion;
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($value)
    {
        // Exchange 2013 and 2016 share the same value. No way to tell them apart aside from querying other things.
        // But that still does not mean this mailbox/object is also at that "version"?
        $version = array_search($value, ExchangeObjectVersion::VERSION);

        return $version === false ? $value : (string) $version;
    }

    /**
     * @return string
     * @throws AttributeConverterException
     */
    protected function getMsExchVersion()
    {
        $version = $this->getExchangeServerVersion();
        if (is_null($version) || !preg_match(self::VERSION_REGEX, $version, $matches)) {
            throw new AttributeConverterException(sprintf(
                'Unable to determine msExchVersion version number for attribute "%s"',
                $this->getAttribute()
            ));
        }
        $build = $this->getBuildNumbers($matches);

        if (isset($this->buildMap[$build[1].'.'.$build[2]])) {
            $msExchVersion = $this->buildMap[$build[1].'.'.$build[2]];
        } elseif (isset($this->buildMap[$build[1]])) {
            $msExchVersion = $this->buildMap[$build[1]];
        } else {
            throw new AttributeConverterException(sprintf(
                'Unable to determine msExchVersion version number for build "%s" and attribute "%s"',
                $build[1].'.'.$build[2],
                $this->getAttribute()
            ));
        }

        return $msExchVersion;
    }

    /**
     * @return null|string
     */
    public function getExchangeServerVersion()
    {
        $query = new LdapQueryBuilder($this->connection);

        /**
         * @todo Not sure if this is really the best way to get this information...?
         */
        return $query->select('serialNumber')
            ->where(['objectClass' => 'msExchExchangeServer'])
            ->andWhere($query->filter()->present('serverRole'))
            ->setBaseDn('%_configurationnamingcontext_%')
            ->setSizeLimit(1)
            ->getLdapQuery()
            ->getSingleScalarOrNullResult();
    }
}
