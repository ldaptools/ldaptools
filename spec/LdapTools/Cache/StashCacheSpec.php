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
use Prophecy\Argument;
use Stash\Interfaces\PoolInterface;
use Stash\Item;

class StashCacheSpec extends ObjectBehavior
{
    protected $testCacheDir = '/ldaptoolstesting';

    function let()
    {
        $this->testCacheDir = sys_get_temp_dir().'/ldaptoolstesting';
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Cache\StashCache');
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
        $this->setCacheFolder(sys_get_temp_dir());
        $this->getCacheFolder()->shouldBeEqualTo(sys_get_temp_dir());
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
        $this->setOptions(['cache_folder' => sys_get_temp_dir()]);
        $this->getCacheFolder()->shouldBeEqualTo(sys_get_temp_dir());
    }

    function it_should_return_null_on_an_item_not_in_the_cache_when_calling_get()
    {
        $this->setCacheFolder($this->testCacheDir);
        $this->get('foo.bar')->shouldBeNull();
    }

    function it_should_cache_an_item_when_calling_set()
    {
        $this->setCacheFolder($this->testCacheDir);
        $item = new CacheItem(CacheItem::TYPE['SCHEMA_OBJECT'].'.foo.bar', new LdapObjectSchema('foo', 'bar'));
        $this->set($item);
        $this->get($item->getKey())->getValue()->shouldBeLike($item->getValue());
        $this->deleteAll();
    }

    function it_should_return_false_when_calling_getCacheCreationTime_for_a_non_existent_item()
    {
        $this->setCacheFolder($this->testCacheDir);
        $this->getCacheCreationTime(CacheItem::TYPE['SCHEMA_OBJECT'].'.foo.bar')->shouldBeEqualTo(false);
    }

    function it_should_return_a_datetime_when_calling_getCacheCreationTime_when_the_item_is_in_the_cache()
    {
        $this->setCacheFolder($this->testCacheDir);
        $item = new CacheItem(CacheItem::TYPE['SCHEMA_OBJECT'].'.foo.bar', new LdapObjectSchema('foo', 'bar'));
        $this->set($item);
        $this->getCacheCreationTime($item->getKey())->shouldReturnAnInstanceOf('\DateTime');
        $this->deleteAll();
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
        $this->contains(CacheItem::TYPE['SCHEMA_OBJECT'].'.foo.bar')->shouldBeEqualTo(false);
        $this->deleteAll();
    }

    function it_should_delete_an_item_from_the_cache()
    {
        $this->setCacheFolder($this->testCacheDir);
        $item = new CacheItem(CacheItem::TYPE['SCHEMA_OBJECT'].'.foo.bar', new LdapObjectSchema('foo', 'bar'));
        $this->set($item);
        $this->contains($item->getKey())->shouldBeEqualTo(true);
        $this->delete($item->getKey())->shouldBeEqualTo(true);
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
        $this->deleteAll()->shouldBeEqualTo(true);
        $this->contains($itemOne->getKey())->shouldBeEqualTo(false);
        $this->contains($itemTwo->getKey())->shouldBeEqualTo(false);
    }

    function it_should_be_case_insensitive_when_looking_up_an_item_in_the_cache()
    {
        $this->setCacheFolder($this->testCacheDir);
        $item = new CacheItem(CacheItem::TYPE['SCHEMA_OBJECT'].'.foo.bar', new LdapObjectSchema('foo', 'bar'));
        $this->set($item);
        $this->contains($item->getKey())->shouldBeEqualTo(true);
        $this->get($item->getKey())->getValue()->shouldBeLike($item->getValue());
        $this->deleteAll();
    }

    function it_should_set_a_expiration_for_the_cache_item_if_specified(PoolInterface $pool, Item $item)
    {
        $this->beConstructedWith($pool);

        $item->set(Argument::any())->willReturn($item);
        $item->get()->willReturn(null);
        $item->lock()->willReturn(null);
        $date = new \DateTime();

        $pool->getItem(Argument::any())->willReturn($item);
        $pool->save($item)->shouldBeCalled();
        $item->getExpiration()->willReturn(null, $date);

        $itemOne = new CacheItem(CacheItem::TYPE['SCHEMA_OBJECT'].'.foo.bar', new LdapObjectSchema('foo', 'bar'));
        $itemTwo = new CacheItem(CacheItem::TYPE['SCHEMA_OBJECT'].'.foo.bar', new LdapObjectSchema('foo', 'bar'), $date);
        $item->expiresAt(null)->shouldBeCalled();
        $item->expiresAt($date)->shouldBeCalled();

        $this->set($itemOne);
        $this->set($itemTwo);
    }
}
