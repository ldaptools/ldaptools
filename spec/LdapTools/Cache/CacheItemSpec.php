<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Cache;

use LdapTools\Cache\CacheItem;
use PhpSpec\ObjectBehavior;

class CacheItemSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('foo.bar', ['foo', 'bar']);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(CacheItem::class);
    }

    function it_should_set_and_get_the_value()
    {
        $this->getValue()->shouldBeEqualTo(['foo', 'bar']);
        $this->setValue(['bar', 'foo'])->getValue()->shouldBeEqualTo(['bar', 'foo']);
    }

    function it_should_get_the_key()
    {
        $this->getKey()->shouldBeEqualTo('foo.bar');
    }

    function it_should_get_and_set_the_expiry_time()
    {
        $date = new \DateTime();
        $this->getExpiresAt()->shouldBeNull();
        $this->setExpiresAt($date)->getExpiresAt($date);
    }
}
