<?php

namespace spec\LdapTools\Connection;

use LdapTools\Connection\LdapConnectionInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RootDseSpec extends ObjectBehavior
{
    function let(LdapConnectionInterface $ldap)
    {
        $ldap->search(Argument::any(), Argument::any(), Argument::any(), Argument::any())->willReturn(['count' => 1, []]);
        $ldap->isBound()->willReturn(false);
        $ldap->connect(Argument::any(), Argument::any(), Argument::any())->willReturn(true);
        $ldap->close(Argument::any())->willReturn(true);
        $ldap->setPagedResults(Argument::any())->willReturn(null);
        $ldap->getPagedResults()->willReturn(true);

        $this->beConstructedWith($ldap);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Connection\RootDse');
    }

    function it_should_return_an_array_when_calling_toArray()
    {
        $this->toArray()->shouldBeEqualTo([]);
    }

    function it_should_return_null_if_the_root_dse_doesnt_have_the_default_nc_value()
    {
        $this->getDefaultNamingContext()->shouldBeNull();
    }

    function it_should_return_null_if_the_root_dse_doesnt_have_the_config_nc_value()
    {
        $this->getConfigurationNamingContext()->shouldBeNull();
    }

    function it_should_return_false_if_the_root_dse_doesnt_have_the_oid_control()
    {
        $this->isControlSupported('foo')->shouldBeEqualTo(false);
    }
}
