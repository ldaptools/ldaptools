<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Factory;

use LdapTools\Connection\LdapConnection;
use LdapTools\Connection\LdapConnectionInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RootDseFactorySpec extends ObjectBehavior
{
    protected $entry = [
        "count" => 1,
        0 => [
            "defaultnamingcontext" => [
                "count" => 1,
                0 => "dc=example,dc=local",
            ],
            0 => "defaultnamingcontext",
            "supportedsaslmechanisms" => [
                "count" => 1,
                0 => "GSSAPI",
            ],
            1 => "supportedsaslmechanisms",
            "count" => 2,
        ],
    ];

    protected $connection;

    function let(LdapConnectionInterface $connection)
    {
        $connection->search("(&(objectClass=*))", ["configurationNamingContext", "defaultNamingContext", "schemaNamingContext", "supportedControl", "namingContexts", "rootDomainNamingContext", "supportedSaslMechanisms", "supportedLdapPolicies", "supportedLdapVersion", "isSynchronized", "isGlobalCatalogReady", "domainFunctionality", "forestFunctionality", "domainControllerFunctionality", "domainFunctionality", "forestFunctionality", "domainControllerFunctionality", "dsServiceName", "currentTime"], "", "base", null)
            ->willReturn($this->entry);
        $connection->__toString()->willReturn('example.local');
        $connection->getPagedResults()->willReturn(true);
        $connection->isBound()->willReturn(false);
        $connection->setPagedResults(true)->willReturn(null);
        $connection->setPagedResults(false)->willReturn(null);
        $connection->connect('','', true)->willReturn(null);
        $connection->getLdapType()->willReturn('ad');

        $this->connection = $connection;
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Factory\RootDseFactory');
    }

    function it_should_get_a_LdapObject_for_a_connection(LdapConnection $connection)
    {
        $this::get($this->connection)->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObject');
    }

    function it_should_have_supported_sasl_mechanisms_as_an_array(LdapConnection $connection)
    {
        $this::get($this->connection)->getSupportedSaslMechanisms()->shouldBeArray();
        $this::get($this->connection)->hasSupportedSaslMechanisms('GSSAPI')->shouldBeEqualTo(true);
    }

    function it_should_be_able_to_get_the_default_naming_context()
    {
        $this::get($this->connection)->getDefaultNamingContext()->shouldBeEqualTo("dc=example,dc=local");
    }
}
