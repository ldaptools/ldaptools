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

use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\DomainConfiguration;
use LdapTools\Event\EventDispatcherInterface;
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

    function let(LdapConnectionInterface $connection, EventDispatcherInterface $dispatcher)
    {
        $connection->execute(Argument::that(function($operation) {
            return $operation->getFilter() == "(&(objectClass=*))" && $operation->getBaseDn() == "";
        }))->willReturn($this->entry);
        $connection->getConfig()->willReturn(new DomainConfiguration('example.local'));
        $connection->isBound()->willReturn(false);
        $connection->connect('','', true)->willReturn(null);

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

    function it_should_call_the_schema_load_event_when_getting_the_rootdse_schema($dispatcher)
    {
        $dispatcher->dispatch(Argument::type('\LdapTools\Event\LdapObjectSchemaEvent'))->shouldBeCalled();
        $this->get();
    }
}
