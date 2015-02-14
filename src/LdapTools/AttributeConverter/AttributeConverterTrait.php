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

use LdapTools\Connection\LdapConnectionInterface;

/**
 * Common Attribute Converter methods and properties.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
trait AttributeConverterTrait
{
    /**
     * @var LdapConnectionInterface
     */
    protected $connection;

    /**
     * @var array Any options that may be recognized by the converter.
     */
    protected $options = [];

    /**
     * Sets the current LdapConnection for access by the converter.
     *
     * @param LdapConnectionInterface $connection
     */
    public function setLdapConnection(LdapConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Set an array of options for the converter to use.
     *
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }
}