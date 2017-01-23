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

use LdapTools\Connection\LdapServerPool;
use LdapTools\DomainConfiguration;
use LdapTools\Exception\LdapConnectionException;
use LdapTools\Utilities\Dns;
use LdapTools\Utilities\TcpSocket;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LdapServerPoolSpec extends ObjectBehavior
{
    protected $servers = [ 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p' ];

    public function let(TcpSocket $tcp)
    {
        $tcp->close()->willReturn($tcp);
        $config = new DomainConfiguration('example.com');
        $this->beConstructedWith($config, $tcp);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Connection\LdapServerPool');
    }

    function it_should_set_the_domain_config($tcp)
    {
        $config = (new DomainConfiguration('foo.bar'))->setServers(['foo']);
        $tcp->connect('foo', Argument::any(), Argument::any())->willReturn(true);

        $this->setConfig($config)->getServer()->shouldBeEqualTo('foo');
    }

    function it_should_be_able_to_check_if_a_specific_server_is_available($tcp)
    {
        $tcp->connect('foobar', Argument::any(), Argument::any())->shouldBeCalled()->willReturn(true);

        $this->isServerAvailable('foobar')->shouldBeEqualTo(true);
    }

    function it_should_have_a_SELECT_ORDER_constant()
    {
        $this->shouldHaveConstant('SELECT_ORDER');
    }

    function it_should_have_a_SELECT_RANDOM_constant()
    {
        $this->shouldHaveConstant('SELECT_RANDOM');
    }

    function it_should_have_order_as_the_default_selection_method()
    {
        $this->getSelectionMethod()->shouldBeEqualTo(LdapServerPool::SELECT_ORDER);
    }

    function it_should_change_the_selection_method_when_calling_setSelectionMethod()
    {
        $this->setSelectionMethod(LdapServerPool::SELECT_RANDOM);
        $this->getSelectionMethod()->shouldBeEqualTo(LdapServerPool::SELECT_RANDOM);
    }

    function it_should_throw_an_error_when_calling_setting_an_invalid_selection_method()
    {
        $this->shouldThrow('\LdapTools\Exception\InvalidArgumentException')->duringSetSelectionMethod('foo');
    }

    function it_should_use_the_server_array_as_is_when_using_the_method_order()
    {
        $config = new DomainConfiguration('example.com');
        $config->setServers($this->servers);
        $this->beConstructedWith($config);
        $this->getSortedServersArray()->shouldBeEqualTo($this->servers);
    }

    function it_should_randomize_the_server_array_when_using_the_method_random()
    {
        $config = new DomainConfiguration('example.com');
        $config->setServers($this->servers);
        $this->beConstructedWith($config);

        $this->setSelectionMethod(LdapServerPool::SELECT_RANDOM);
        $this->getSortedServersArray()->shouldNotBeEqualTo($this->servers);
    }

    function it_should_throw_an_exception_when_no_servers_are_available(TcpSocket $tcp)
    {
        $tcp->connect('foo', 389, 1)->shouldBeCalled()->willReturn(false);
        $config = new DomainConfiguration('example.com');
        $config->setServers(['foo']);
        $this->beConstructedWith($config, $tcp);

        $this->shouldThrow(new LdapConnectionException('No LDAP server is available.'))->duringGetServer();
    }

    function it_should_lookup_servers_via_dns_if_no_servers_are_defined(TcpSocket $tcp, Dns $dns)
    {
        $tcp->connect('bar.example.com', 389, 1)->shouldBeCalled()->willReturn(false);
        $tcp->connect('test.example.com', 389, 1)->shouldBeCalled()->willReturn(false);
        $tcp->connect('foo.example.com', 389, 1)->shouldBeCalled()->willReturn(true);

        $tcp->close()->willReturn(null);
        $srvRecords = [
            [
                'host' => '_ldap._tcp.example.com',
                'class' => 'IN',
                'ttl' => 600,
                'type' => 'SRV',
                'pri' => 1,
                'weight' => 101,
                'port' => 389,
                'target' => 'foo.example.com',
            ],
            [
                'host' => '_ldap._tcp.example.com',
                'class' => 'IN',
                'ttl' => 600,
                'type' => 'SRV',
                'pri' => 0,
                'weight' => 100,
                'port' => 389,
                'target' => 'test.example.com',
            ],
            [
                'host' => '_ldap._tcp.example.com',
                'class' => 'IN',
                'ttl' => 600,
                'type' => 'SRV',
                'pri' => 0,
                'weight' => 101,
                'port' => 389,
                'target' => 'bar.example.com',
            ],
        ];

        $dns->getRecord("_ldap._tcp.example.com", DNS_SRV)->willReturn($srvRecords);
        $config = new DomainConfiguration('example.com');
        $this->beConstructedWith($config, $tcp, $dns);

        $this->getServer()->shouldBeEqualTo('foo.example.com');
    }

    function it_should_throw_an_error_when_no_servers_are_returned_from_dns(TcpSocket $tcp, Dns $dns)
    {
        $e = new LdapConnectionException('No LDAP servers found via DNS for "example.com".');
        $dns->getRecord("_ldap._tcp.example.com", DNS_SRV)->willReturn(false);
        $config = new DomainConfiguration('example.com');
        $this->beConstructedWith($config, $tcp, $dns);

        $this->shouldThrow($e)->duringGetServer();
    }

    function it_should_adjust_the_port_if_it_changes_in_the_domain_config(TcpSocket $tcp)
    {
        $tcp->connect('foo', 389, 1)->shouldBeCalled()->willReturn(true);
        $tcp->close()->shouldBeCalled();
        $config = new DomainConfiguration('example.com');
        $config->setServers(['foo']);
        $this->beConstructedWith($config, $tcp);

        $this->getServer()->shouldReturn('foo');
        $config->setPort(9001);

        $tcp->connect('foo', 9001, 1)->shouldBeCalled()->willReturn(true);

        $this->getServer()->shouldReturn('foo');
    }

    function it_should_use_the_connect_timeout_value_from_the_config(TcpSocket $tcp)
    {
        $tcp->connect('foo', 389, 5)->shouldBeCalled()->willReturn(true);
        $tcp->close()->shouldBeCalled();
        $config = (new DomainConfiguration('example.com'))->setConnectTimeout(5)->setServers(['foo']);
        $this->beConstructedWith($config, $tcp);

        $this->getServer()->shouldReturn('foo');
    }

    public function getMatchers()
    {
        return [
            'haveConstant' => function($subject, $constant) {
                return defined('\LdapTools\Connection\LdapServerPool::'.$constant);
            }
        ];
    }
}
