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

use LdapTools\AttributeConverter\ConvertExchangeLegacyDn;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\DomainConfiguration;
use LdapTools\Object\LdapObject;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ConvertExchangeLegacyDnSpec extends ObjectBehavior
{
    function let(LdapConnectionInterface $connection)
    {
        $connection->getConfig()->willReturn(new DomainConfiguration('foo.bar'));
        $connection->getRootDse()->willReturn(new LdapObject(['configurationNamingContext' => 'cn=foo,dc=foo,dc=bar']));
        $this->setLdapConnection($connection);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(ConvertExchangeLegacyDn::class);
    }

    function it_should_do_nothing_with_the_value_coming_from_ldap()
    {
        $this->fromLdap('foo')->shouldBeEqualTo('foo');
    }

    function it_should_not_convert_the_value_going_to_ldap_if_it_is_not_set_to_auto($connection)
    {
        $connection->execute(Argument::any())->shouldNotBeCalled();
        $this->toLdap('foo')->shouldBeEqualTo('foo');
    }

    function it_should_convert_to_a_legacy_dn_with_auto_specified($connection)
    {
        $connection->execute(Argument::any())->willReturn([
            'count' => 1,
            0 => [
                'legacyExchangeDn' => [
                    'count' => 1,
                    0 => "/o=LdapTools/ou=Exchange Administrative Group (FYDIBOHF23SPDLT)",
                ],
                'count' => 1,
                'dn' => "CN=foo,DC=foo,DC=bar",
            ],
        ]);

        $this->toLdap('auto:Chad')->shouldMatch("/"
            // This first part is the legacyDn of the admin group/org object in Exchange...
            ."\/o=LdapTools\/ou=Exchange Administrative Group \(FYDIBOHF23SPDLT\)\/"
            // This is the recipient portion of the DN with the GUID and username portion...
            ."cn=Recipients\/cn=[0-9a-z]{32}-Chad"
        ."/");
    }

    function it_should_throw_an_exception_if_it_cannot_determine_the_legacy_exchange_dn_automatically($connection)
    {
        $connection->execute(Argument::any())->willThrow('LdapTools\Exception\EmptyResultException');

        $this->shouldThrow('LdapTools\Exception\AttributeConverterException')->duringToLdap('auto:Foo');
    }
}
