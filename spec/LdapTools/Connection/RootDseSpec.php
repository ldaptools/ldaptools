<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Connection;

use LdapTools\Operation\QueryOperation;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RootDseSpec extends ObjectBehavior
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

    protected $dispatcher;

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     * @param \LdapTools\Event\EventDispatcherInterface $dispatcher
     */
    function let($connection, $dispatcher)
    {
        $connection->execute((new QueryOperation())->setFilter("(&(objectClass=*))")->setAttributes(["configurationNamingContext", "defaultNamingContext", "schemaNamingContext", "supportedControl", "namingContexts", "rootDomainNamingContext", "supportedSaslMechanisms", "supportedLdapPolicies", "supportedLdapVersion", "vendorName", "vendorVersion", "isSynchronized", "isGlobalCatalogReady", "domainFunctionality", "forestFunctionality", "domainControllerFunctionality", "domainFunctionality", "forestFunctionality", "domainControllerFunctionality", "dsServiceName", "currentTime"])->setBaseDn("")->setScope(QueryOperation::SCOPE['BASE']))
            ->willReturn($this->entry);
        $connection->__toString()->willReturn('example.local');
        $connection->getPagedResults()->willReturn(true);
        $connection->isBound()->willReturn(false);
        $connection->setPagedResults(true)->willReturn(null);
        $connection->setPagedResults(false)->willReturn(null);
        $connection->connect('','', true)->willReturn(null);
        $connection->getLdapType()->willReturn('ad');

        $this->connection = $connection;
        $this->dispatcher = $dispatcher;
        $this->beConstructedWith($connection, $dispatcher);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Connection\RootDse');
    }

    function it_should_get_a_LdapObject_for_a_connection()
    {
        $this->get()->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObject');
    }

    function it_should_have_supported_sasl_mechanisms_as_an_array()
    {
        $this->get()->getSupportedSaslMechanisms()->shouldBeArray();
        $this->get()->hasSupportedSaslMechanisms('GSSAPI')->shouldBeEqualTo(true);
    }

    function it_should_be_able_to_get_the_default_naming_context()
    {
        $this->get()->getDefaultNamingContext()->shouldBeEqualTo("dc=example,dc=local");
    }

    function it_should_call_the_schema_load_event_when_getting_the_rootdse_schema()
    {
        $this->dispatcher->dispatch(Argument::type('\LdapTools\Event\LdapObjectSchemaEvent'))->shouldBeCalled();
        $this->get();
    }
}
