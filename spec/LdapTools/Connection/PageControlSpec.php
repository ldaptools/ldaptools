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

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class PageControlSpec extends ObjectBehavior
{
    protected $connection;

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     */
    public function let($connection)
    {
        $this->connection = $connection;
        $this->beConstructedWith($connection);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Connection\PageControl');
    }

    function it_should_not_be_active_when_constructed()
    {
        $this->isActive()->shouldBeEqualTo(false);
    }

    function it_should_call_start_with_a_page_size()
    {
        $this->start(10);
    }

    function it_should_set_whether_or_not_paging_is_enabled()
    {
        $this->isEnabled()->shouldBeEqualTo(true);
        $this->setIsEnabled(false);
        $this->isEnabled()->shouldBeEqualTo(false);
    }

    function it_should_not_call_paging_operations_when_it_is_disabled()
    {
        $this->setIsEnabled(false);
        $this->connection->getConnection()->shouldNotBeCalled();

        $this->start(10);
        $this->update(null);
        $this->end();
    }
}
