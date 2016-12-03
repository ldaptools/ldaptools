<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Factory;

use LdapTools\Cache\CacheInterface;
use LdapTools\Cache\CacheItem;
use LdapTools\Configuration;
use LdapTools\Event\Event;
use LdapTools\Event\EventDispatcherInterface;
use LdapTools\Event\LdapObjectSchemaEvent;
use LdapTools\Factory\CacheFactory;
use LdapTools\Factory\SchemaParserFactory;
use LdapTools\Object\LdapObjectType;
use LdapTools\Schema\LdapObjectSchema;
use LdapTools\Schema\Parser\SchemaParserInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LdapObjectSchemaFactorySpec extends ObjectBehavior
{
    function let(EventDispatcherInterface $dispatcher)
    {
        $config = new Configuration();
        $parser = SchemaParserFactory::get('yml', $config->getSchemaFolder());
        $cache = CacheFactory::get('none', []);
        $this->beConstructedWith($cache, $parser, $dispatcher);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Factory\LdapObjectSchemaFactory');
    }

    function it_should_return_an_LdapObjectSchema_object_when_calling_get()
    {
        $this->get('ad', LdapObjectType::USER)->shouldReturnAnInstanceOf('\LdapTools\Schema\LdapObjectSchema');
    }

    function it_should_throw_a_parser_exception_when_the_schema_object_type_is_not_found()
    {
        $this->shouldThrow('\LdapTools\Exception\SchemaParserException')->duringGet('foo', 'bar');
    }

    function it_should_build_the_cache_item_if_it_cannot_be_found(CacheInterface $cache, SchemaParserInterface $parser, $dispatcher)
    {
        $cache->getUseAutoCache()->willReturn(false);
        $cache->contains(CacheItem::TYPE['SCHEMA_OBJECT'].'.ad.user')->willReturn(false);
        $parser->parse('ad', 'user')->willReturn(new LdapObjectSchema('ad', 'user'));
        $parser->parse('ad', 'user')->shouldBeCalled();
        $cache->set(Argument::any())->shouldBeCalled();
        $this->beConstructedWith($cache, $parser, $dispatcher);
        $this->get('ad', LdapObjectType::USER)->shouldReturnAnInstanceOf('\LdapTools\Schema\LdapObjectSchema');
    }

    function it_should_not_build_the_cache_item_if_it_is_in_the_cache_and_auto_cache_is_not_on(CacheInterface $cache, SchemaParserInterface $parser, $dispatcher, CacheItem $item)
    {
        $cache->getUseAutoCache()->willReturn(false);
        $cache->contains(CacheItem::TYPE['SCHEMA_OBJECT'].'.ad.user')->willReturn(true);
        $cache->set(Argument::any())->shouldNotBeCalled();
        $cache->get(Argument::any())->shouldBeCalled()->willReturn($item);
        $item->getValue()->willReturn(new LdapObjectSchema('ad', 'user'));
        $parser->parse('ad', 'user')->shouldNotBeCalled();
        $this->beConstructedWith($cache, $parser, $dispatcher);

        $this->get('ad', LdapObjectType::USER);
    }

    function it_should_build_the_cache_when_auto_cache_is_enabled_and_the_cache_item_is_out_of_date(CacheInterface $cache, SchemaParserInterface $parser, $dispatcher)
    {
        $cache->getUseAutoCache()->willReturn(true);
        $cache->contains(CacheItem::TYPE['SCHEMA_OBJECT'].'.ad.user')->willReturn(true);
        $cache->getCacheCreationTime(CacheItem::TYPE['SCHEMA_OBJECT'].'.ad.user')->willReturn(new \DateTime('2015-1-1'));
        $cache->set(Argument::any())->shouldBeCalled();

        $parser->parse('ad', 'user')->shouldBeCalled()->willReturn(new LdapObjectSchema('ad', 'user'));
        $parser->getSchemaModificationTime('ad')->willReturn(new \DateTime('2015-1-2'));

        $this->beConstructedWith($cache, $parser, $dispatcher);
        $this->get('ad', LdapObjectType::USER)->shouldReturnAnInstanceOf('\LdapTools\Schema\LdapObjectSchema');
    }

    function it_should_not_build_the_cache_when_auto_cache_is_enabled_and_the_cache_item_is_not_out_of_date(CacheInterface $cache, SchemaParserInterface $parser, $dispatcher)
    {
        $cache->getUseAutoCache()->willReturn(true);
        $cache->contains(CacheItem::TYPE['SCHEMA_OBJECT'].'.ad.user')->willReturn(true);
        $cache->getCacheCreationTime(CacheItem::TYPE['SCHEMA_OBJECT'].'.ad.user')->willReturn(new \DateTime('2015-1-3'));
        $cache->set(Argument::any())->shouldNotBeCalled();
        $cache->get(CacheItem::TYPE['SCHEMA_OBJECT'].'.ad.user')->willReturn(new CacheItem(CacheItem::TYPE['SCHEMA_OBJECT'].'.ad.user', new LdapObjectSchema('ad', 'user')));
        $parser->parse('ad', 'user')->shouldNotBeCalled();
        $parser->getSchemaModificationTime('ad')->willReturn(new \DateTime('2015-1-2'));

        $this->beConstructedWith($cache, $parser, $dispatcher);
        $this->get('ad', LdapObjectType::USER)->shouldReturnAnInstanceOf('\LdapTools\Schema\LdapObjectSchema');
    }

    function it_should_call_the_load_schema_event($dispatcher)
    {
        $config = new Configuration();
        $parser = SchemaParserFactory::get('yml', $config->getSchemaFolder());
        $cache = CacheFactory::get('none', []);
        new LdapObjectSchemaEvent(Event::LDAP_SCHEMA_LOAD, new LdapObjectSchema('ad', 'user'));
        $dispatcher->dispatch(Argument::type('\LdapTools\Event\LdapObjectSchemaEvent'))->shouldBeCalled();

        $this->beConstructedWith($cache, $parser, $dispatcher);
        $this->get('ad', LdapObjectType::USER);
    }

    function it_should_not_call_the_load_schema_event_when_retrieving_from_the_cache(CacheInterface $cache, SchemaParserInterface $parser, $dispatcher)
    {
        $cache->getUseAutoCache()->willReturn(true);
        $cache->contains(CacheItem::TYPE['SCHEMA_OBJECT'].'.ad.user')->willReturn(true);
        $cache->getCacheCreationTime(CacheItem::TYPE['SCHEMA_OBJECT'].'.ad.user')->willReturn(new \DateTime('2015-1-3'));
        $cache->set(Argument::any())->shouldNotBeCalled();
        $cache->get(CacheItem::TYPE['SCHEMA_OBJECT'].'.ad.user')->willReturn(new CacheItem(CacheItem::TYPE['SCHEMA_OBJECT'].'.ad.user', new LdapObjectSchema('ad', 'user')));
        $parser->parse('ad', 'user')->shouldNotBeCalled();
        $parser->getSchemaModificationTime('ad')->willReturn(new \DateTime('2015-1-2'));
        new LdapObjectSchemaEvent(Event::LDAP_SCHEMA_LOAD, new LdapObjectSchema('ad', 'user'));
        $dispatcher->dispatch(Argument::type('\LdapTools\Event\LdapObjectSchemaEvent'))->shouldNotBeCalled();

        $this->beConstructedWith($cache, $parser, $dispatcher);
        $this->get('ad', LdapObjectType::USER);
    }
}
