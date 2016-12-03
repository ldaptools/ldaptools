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

use Doctrine\Common\Cache\FilesystemCache;
use LdapTools\Cache\CacheItem;
use LdapTools\Schema\LdapObjectSchema;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class DoctrineCacheSpec extends ObjectBehavior
{
    protected $testCacheDir = '/ldaptoolstesting';

    function let()
    {
        $this->testCacheDir = sys_get_temp_dir().'/ldaptoolstesting';
    }

    function letGo()
    {
        $this->deleteAll();
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Cache\DoctrineCache');
    }

    function it_should_implement_the_CacheInterface()
    {
        $this->shouldImplement('\LdapTools\Cache\CacheInterface');
    }

    function it_should_support_setting_the_cache_prefix()
    {
        $this->setCachePrefix('/foo');
        $this->getCachePrefix()->shouldBeEqualTo('/foo');
    }

    function it_should_have_the_system_temp_dir_with_a_ldaptools_subfolder_as_the_default_cache_location()
    {
        $this->getCacheFolder()->shouldBeEqualTo(sys_get_temp_dir().'/ldaptools');
    }

    function it_should_support_setting_the_cache_folder()
    {
        $this->setCacheFolder('/tmp/foo/bar');
        $this->getCacheFolder()->shouldBeEqualTo('/tmp/foo/bar');
    }

    function it_should_return_a_default_cache_prefix_of_ldaptools_when_calling_getCachePrefix()
    {
        $this->getCachePrefix()->shouldBeEqualTo('/ldaptools');
    }

    function it_should_recognize_the_cache_prefix_option()
    {
        $this->setOptions(['cache_prefix' => '/foo']);
        $this->getCachePrefix()->shouldBeEqualTo('/foo');
    }

    function it_should_recognize_the_cache_folder_option()
    {
        $this->setOptions(['cache_folder' => '/tmp/foo/bar']);
        $this->getCacheFolder()->shouldBeEqualTo('/tmp/foo/bar');
    }

    function it_should_return_null_on_an_item_not_in_the_cache_when_calling_get()
    {
        $item = new CacheItem(CacheItem::TYPE['SCHEMA_OBJECT'].'.foo.bar', new LdapObjectSchema('foo', 'bar'));
        $this->setCacheFolder($this->testCacheDir);
        $this->get($item->getKey())->shouldBeEqualTo(null);
    }

    function it_should_cache_an_item_when_calling_set()
    {
        $this->setCacheFolder($this->testCacheDir);
        $item = new CacheItem(CacheItem::TYPE['SCHEMA_OBJECT'].'.foo.bar', new LdapObjectSchema('foo', 'bar'));
        $this->set($item);
        $this->get($item->getKey())->getValue()->shouldBeLike($item->getValue());
        $this->deleteAll();
    }

    /**
     *  Expected to be false as it is not supported with the Doctrine Cache methods.
     */
    function it_should_return_a_false_when_calling_getCacheCreationTime()
    {
        $this->getCacheCreationTime('foo.bar')->shouldBeEqualTo(false);
    }

    function it_should_return_true_when_calling_contains_and_the_item_is_in_the_cache()
    {
        $this->setCacheFolder($this->testCacheDir);
        $item = new CacheItem(CacheItem::TYPE['SCHEMA_OBJECT'].'.foo.bar', new LdapObjectSchema('foo', 'bar'));
        $this->set($item);
        $this->contains($item->getKey())->shouldBeEqualTo(true);
        $this->deleteAll();
    }

    function it_should_return_false_when_calling_contains_and_the_item_is_not_in_the_cache()
    {
        $this->setCacheFolder($this->testCacheDir);
        $this->contains('foo.bar')->shouldBeEqualTo(false);
        $this->deleteAll();
    }

    function it_should_delete_an_item_from_the_cache()
    {
        $this->setCacheFolder($this->testCacheDir);
        $item = new CacheItem(CacheItem::TYPE['SCHEMA_OBJECT'].'.foo.bar', new LdapObjectSchema('foo', 'bar'));
        $this->set($item);
        $this->contains($item->getKey())->shouldBeEqualTo(true);
        $this->delete($item->getKey());
        $this->contains($item->getKey())->shouldBeEqualTo(false);
    }

    function it_should_delete_all_items_in_the_cache()
    {
        $this->setCacheFolder($this->testCacheDir);
        $itemOne = new CacheItem(CacheItem::TYPE['SCHEMA_OBJECT'].'.foo.bar', new LdapObjectSchema('foo', 'bar'));
        $itemTwo = new CacheItem(CacheItem::TYPE['SCHEMA_OBJECT'].'.foo.bar', new LdapObjectSchema('bar', 'foo'));
        $this->set($itemOne);
        $this->set($itemTwo);
        $this->contains($itemOne->getKey())->shouldBeEqualTo(true);
        $this->contains($itemTwo->getKey())->shouldBeEqualTo(true);
        $this->deleteAll();
        $this->contains($itemOne->getKey())->shouldBeEqualTo(false);
        $this->contains($itemTwo->getKey())->shouldBeEqualTo(false);
    }

    function it_should_be_case_insensitive_when_looking_up_an_item_in_the_cache()
    {
        $this->setCacheFolder($this->testCacheDir);
        $item = new CacheItem(CacheItem::TYPE['SCHEMA_OBJECT'].'.foo.bar', new LdapObjectSchema('foo', 'bar'));
        $this->set($item);
        $this->contains(CacheItem::TYPE['SCHEMA_OBJECT'].'.fOo.bAr')->shouldBeEqualTo(true);
        $this->get(CacheItem::TYPE['SCHEMA_OBJECT'].'.fOo.bAr')->getValue()->shouldBeLike($item->getValue());
        $this->deleteAll();
    }

    function it_should_set_a_ttl_for_the_cache_item_when_specified(FilesystemCache $cache)
    {
        $this->beConstructedWith($cache);

        $date = new \DateTime();
        $date->add(\DateInterval::createFromDateString('10 seconds'));
        $itemOne = new CacheItem(CacheItem::TYPE['SCHEMA_OBJECT'].'.foo.bar', new LdapObjectSchema('foo', 'bar'));
        $itemTwo = new CacheItem(CacheItem::TYPE['SCHEMA_OBJECT'].'.foo.bar', new LdapObjectSchema('foo', 'bar'), $date);

        $cache->save(Argument::any(), Argument::any(), 0)->shouldBeCalled();
        $cache->save(Argument::any(), Argument::any(), 10)->shouldBeCalled();
        $cache->flushAll()->shouldBeCalled();

        $this->set($itemOne);
        $this->set($itemTwo);
    }
}
