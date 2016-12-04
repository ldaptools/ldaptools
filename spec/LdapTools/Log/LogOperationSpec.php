<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Log;

use LdapTools\Operation\DeleteOperation;
use LdapTools\Operation\QueryOperation;
use PhpSpec\ObjectBehavior;

class LogOperationSpec extends ObjectBehavior
{
    /**
     * @var QueryOperation
     */
    protected $operation;

    public function let()
    {
        $this->operation = new QueryOperation('(foo=bar)');
        $this->operation->setAttributes(['foo'])
            ->setBaseDn('foo')
            ->setPageSize(2000)
            ->setScope(QueryOperation::SCOPE['SUBTREE']);
        $this->beConstructedWith($this->operation);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Log\LogOperation');
    }

    function it_should_set_the_domain()
    {
        $this->setDomain('example.local');
        $this->getDomain()->shouldBeEqualTo('example.local');
    }

    function it_should_set_an_error_message()
    {
        $this->setError('foo');
        $this->getError()->shouldBeEqualTo('foo');
    }

    function it_should_set_a_start_time()
    {
        $this->getStartTime()->shouldBeEqualTo(null);
        $this->start();
        $this->getStartTime()->shouldNotBeEqualTo(null);
    }

    function it_should_set_a_stop_time()
    {
        $this->getStopTime()->shouldBeEqualTo(null);
        $this->stop();
        $this->getStopTime()->shouldNotBeEqualTo(null);
    }

    function it_should_get_the_ldap_operation()
    {
        $this->getOperation()->shouldBeEqualTo($this->operation);
    }

    function it_should_set_the_ldap_operation()
    {
        $op = new DeleteOperation('foo');

        $this->setOperation($op)->shouldBeAnInstanceOf('\LdapTools\Log\LogOperation');
        $this->getOperation()->shouldBeEqualTo($op);
    }

    function it_should_get_whether_the_cache_was_used_for_the_result()
    {
        $this->getUsedCachedResult()->shouldBeEqualTo(false);
        $this->setUsedCachedResult(true)->getUsedCachedResult()->shouldBeEqualTo(true);
    }
}
