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
use LdapTools\Schema\LdapObjectSchema;
use PhpSpec\ObjectBehavior;

class NoCacheSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Cache\NoCache');
    }

    function it_should_implement_the_CacheInterface()
    {
        $this->shouldImplement('\LdapTools\Cache\CacheInterface');
    }

    public function it_should_always_return_null_when_calling_get()
    {
        $item = new CacheItem(CacheItem::TYPE['SCHEMA_OBJECT'].'.foo.bar', new LdapObjectSchema('foo', 'bar'));

        $this->get($item->getKey())->shouldBeNull();
    }

    public function it_should_never_cache_when_calling_set()
    {
        $item = new CacheItem(CacheItem::TYPE['SCHEMA_OBJECT'].'.foo.bar', new LdapObjectSchema('foo', 'bar'));
        $this->set($item)->get($item->getKey())->shouldBeNull();
    }

    public function it_should_return_true_when_calling_delete()
    {
        $this->delete('foo.bar')->shouldBeEqualTo(true);
    }

    public function it_should_return_true_when_calling_delete_all()
    {
        $this->deleteAll()->shouldBeEqualTo(true);
    }

    public function it_should_return_false_when_calling_getCacheCreationTime()
    {
        $this->getCacheCreationTime('foo.bar')->shouldBeEqualTo(false);
    }

    public function it_should_return_false_when_calling_getUseAutoCache()
    {
        $this->getUseAutoCache()->shouldBeEqualTo(false);
    }

    public function it_should_return_false_when_calling_contains()
    {
        $this->contains('foo.bar')->shouldBeEqualTo(false);
    }
}
