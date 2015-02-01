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
use LdapTools\Exception\LdapConnectionException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LdapServerPoolSpec extends ObjectBehavior
{
    protected $servers = [ 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p' ];

    public function let()
    {
        $this->beConstructedWith($this->servers, 389);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Connection\LdapServerPool');
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
        $this->shouldThrow('\InvalidArgumentException')->duringSetSelectionMethod('foo');
    }

    function it_should_use_the_server_array_as_is_when_using_the_method_order()
    {
        $this->getSortedServersArray()->shouldBeEqualTo($this->servers);
    }

    function it_should_randomize_the_server_array_when_using_the_method_random()
    {
        $this->setSelectionMethod(LdapServerPool::SELECT_RANDOM);
        $this->getSortedServersArray()->shouldNotBeEqualTo($this->servers);
    }

    function it_should_throw_an_exception_when_no_servers_are_available()
    {
        $this->beConstructedWith(['thisFakeServerShouldNotExistEver'], 389);

        $this->shouldThrow(new LdapConnectionException('No LDAP server is available.'))->duringGetServer();
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
