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

use LdapTools\AttributeConverter\ConvertExchangeObjectVersion;
use LdapTools\Connection\AD\ExchangeObjectVersion;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\DomainConfiguration;
use LdapTools\Object\LdapObject;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ConvertExchangeObjectVersionSpec extends ObjectBehavior
{
    protected $result = [
        'count' => 1,
        0 => [
            'serialNumber' => [
                'count' => 1,
                0 => "",
            ],
            'count' => 1,
            'dn' => "CN=foo,DC=foo,DC=bar",
        ],
    ];

    function let(LdapConnectionInterface $connection)
    {
        $connection->getConfig()->willReturn(new DomainConfiguration('foo.bar'));
        $connection->getRootDse()->willReturn(new LdapObject(['configurationNamingContext' => 'cn=config,dc=foo,dc=bar']));
        $this->setLdapConnection($connection);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(ConvertExchangeObjectVersion::class);
    }

    function it_should_allow_a_simple_version_number_going_to_ldap()
    {
        foreach (ExchangeObjectVersion::VERSION as $name => $value) {
            $this->toLdap($name)->shouldBeEqualTo((string) $value);
        }
    }

    function it_should_attempt_to_query_the_version_to_use_if_auto_is_sent_to_ldap($connection)
    {
        $v2007 = $this->result;
        $v2010 = $this->result;
        $v2013 = $this->result;
        $v2016 = $this->result;
        $v2007[0]['serialNumber'][0] = 'Version 08.01 (Build 11178.04)';
        $v2010[0]['serialNumber'][0] = 'Version 14.02 (Build 21178.04)';
        $v2013[0]['serialNumber'][0] = 'Version 15.00 (Build 31178.04)';
        $v2016[0]['serialNumber'][0] = 'Version 15.01 (Build 41178.04)';

        $connection->execute(Argument::any())->willReturn($v2007, $v2010, $v2013, $v2016);

        $this->toLdap('auto')->shouldBeEqualTo((string) ExchangeObjectVersion::VERSION['2007']);
        $this->toLdap('auto')->shouldBeEqualTo((string) ExchangeObjectVersion::VERSION['2010']);
        $this->toLdap('auto')->shouldBeEqualTo((string) ExchangeObjectVersion::VERSION['2013']);
        $this->toLdap('auto')->shouldBeEqualTo((string) ExchangeObjectVersion::VERSION['2016']);
    }

    function it_should_throw_an_exception_if_a_version_number_to_use_cannot_be_determined($connection)
    {
        $result = $this->result;

        // Exchange 2003 does not have this concept...
        $result[0]['serialNumber'][0] = 'Version 06.05 (Build 11178.04)';
        $connection->execute(Argument::any())->willReturn($result);

        $this->shouldThrow('LdapTools\Exception\AttributeConverterException')->duringToLdap('auto');
    }

    function it_should_throw_an_exception_if_a_simple_version_number_going_to_ldap_is_not_recognized()
    {
        $this->shouldThrow('LdapTools\Exception\AttributeConverterException')->duringToLdap('2003');
    }

    function it_should_get_the_object_version_from_ldap()
    {
        $this->fromLdap((string) ExchangeObjectVersion::VERSION['2007'])->shouldBeEqualTo('2007');
        $this->fromLdap((string) ExchangeObjectVersion::VERSION['2013'])->shouldBeEqualTo('2013');
        // No way around this, as they are both the same version...
        $this->fromLdap((string) ExchangeObjectVersion::VERSION['2016'])->shouldBeEqualTo('2013');
    }
}
