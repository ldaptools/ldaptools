<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools;

use LdapTools\Configuration;
use LdapTools\Connection\LdapConnection;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Factory\CacheFactory;
use LdapTools\Factory\LdapObjectSchemaFactory;
use LdapTools\Factory\SchemaParserFactory;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LdapObjectRepositorySpec extends ObjectBehavior
{
    public function let(LdapConnectionInterface $ldap)
    {
        $config = new Configuration();
        $config->setCacheType('none');
        $ldap->search(Argument::any(), Argument::any(), Argument::any(), Argument::any(), Argument::any())->willReturn(['count' => 0]);
        $ldap->getLdapType()->willReturn(LdapConnection::TYPE_AD);

        $cache = CacheFactory::get($config->getCacheType(), $config->getCacheOptions());
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), $config->getSchemaFolder());
        $schemaFactory = new LdapObjectSchemaFactory($cache, $parser);

        $this->beConstructedWith($schemaFactory->get('ad', 'user'), $ldap);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\LdapObjectRepository');
    }

    function it_should_call_findOneByGuid()
    {
        $this->findOneByGuid('foo')->shouldBeArray();
    }

    function it_should_call_findByFirstName()
    {
        $this->findByFirstName('foo')->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObjectCollection');
    }

    function it_should_error_when_calling_findOneByFooBar()
    {
        $this->shouldThrow('\RuntimeException')->duringfindOneByFooBar('test');
    }

    function it_should_error_when_calling_findByFooBar()
    {
        $this->shouldThrow('\RuntimeException')->duringfindOneByFooBar('test');
    }

    function it_should_set_default_attributes_when_calling_setAttributes()
    {
        $this->setAttributes(['foo']);
        $this->getAttributes()->shouldBeEqualTo(['foo']);
    }
}
