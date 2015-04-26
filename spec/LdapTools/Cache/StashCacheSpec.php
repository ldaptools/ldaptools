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

use LdapTools\Schema\LdapObjectSchema;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class StashCacheSpec extends ObjectBehavior
{
    protected $testCacheDir = '/ldaptoolstesting';

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
        $item = new LdapObjectSchema('foo', 'bar');
        $this->setCachePrefix($this->testCacheDir);
        $this->get($item->getCacheType(), 'foo.bar')->shouldBeNull();
    }

    function it_should_cache_an_item_when_calling_set()
    {
        $this->setCacheFolder(sys_get_temp_dir());
        $this->setCachePrefix($this->testCacheDir);
        $item = new LdapObjectSchema('foo', 'bar');
        $this->set($item);
        $this->get(LdapObjectSchema::getCacheType(), 'foo.bar')->shouldBeLike($item);
        $this->clear();
    }

    function it_should_return_false_when_calling_getCacheCreationTime_for_a_non_existent_item()
    {
        $this->setCacheFolder(sys_get_temp_dir());
        $this->setCachePrefix($this->testCacheDir);
        $this->getCacheCreationTime('foo','bar')->shouldBeEqualTo(false);
    }

    function it_should_return_a_datetime_when_calling_getCacheCreationTime_when_the_item_is_in_the_cache()
    {
        $this->setCacheFolder(sys_get_temp_dir());
        $this->setCachePrefix($this->testCacheDir);
        $item = new LdapObjectSchema('foo', 'bar');
        $this->set($item);
        $this->getCacheCreationTime(LdapObjectSchema::getCacheType(), 'foo.bar')->shouldReturnAnInstanceOf('\DateTime');
        $this->clear();
    }

    function it_should_return_true_when_calling_contains_and_the_item_is_in_the_cache()
    {
        $this->setCacheFolder(sys_get_temp_dir());
        $this->setCachePrefix($this->testCacheDir);
        $item = new LdapObjectSchema('foo', 'bar');
        $this->set($item);
        $this->contains(LdapObjectSchema::getCacheType(), 'foo.bar')->shouldBeEqualTo(true);
        $this->clear();
    }

    function it_should_return_false_when_calling_contains_and_the_item_is_not_in_the_cache()
    {
        $this->setCacheFolder(sys_get_temp_dir());
        $this->setCachePrefix($this->testCacheDir);
        $this->contains(LdapObjectSchema::getCacheType(), 'foo.bar')->shouldBeEqualTo(false);
        $this->clear();
    }
}
