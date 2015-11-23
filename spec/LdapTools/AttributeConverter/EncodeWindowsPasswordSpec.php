<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\AttributeConverter;

use LdapTools\DomainConfiguration;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class EncodeWindowsPasswordSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\AttributeConverter\EncodeWindowsPassword');
    }

    function it_should_implement_AttributeConverterInterface()
    {
        $this->shouldImplement('\LdapTools\AttributeConverter\AttributeConverterInterface');
    }

    function it_should_not_return_anything_when_calling_fromLdap()
    {
        $this->fromLdap('foo')->shouldBeNull();
    }

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     */
    function it_should_throw_an_exception_if_ssl_or_tls_is_not_enabled($connection)
    {
        $this->toLdap('test')->shouldNotThrow('\LdapTools\Exception\LdapConnectionException');

        $config = new DomainConfiguration('example.local');
        $config->setUseTls(true);
        $connection->getConfig()->willReturn($config);
        $this->setLdapConnection($connection);

        $this->toLdap('test')->shouldNotThrow('\LdapTools\Exception\LdapConnectionException');
        $config->setUseTls(false);
        $this->shouldThrow('\LdapTools\Exception\LdapConnectionException')->duringToLdap('test');
        $config->setUseSsl(true);
        $this->toLdap('test')->shouldNotThrow('\LdapTools\Exception\LdapConnectionException');
    }
}
