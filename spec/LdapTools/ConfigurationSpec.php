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
use LdapTools\Factory\CacheFactory;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use \LdapTools\DomainConfiguration;

class ConfigurationSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith(new DomainConfiguration('foo.bar'), new DomainConfiguration('example.local'));
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Configuration');
    }

    function it_is_initializable_with_no_domain_configurations()
    {
        $this->beConstructedWith();
        $this->shouldHaveType('LdapTools\Configuration');
    }

    function it_should_return_self_when_adding_domain_configuration()
    {
        $domain = new DomainConfiguration('example.com');
        $this->addDomain($domain)->shouldReturnAnInstanceOf('\LdapTools\Configuration');
    }

    function it_should_load_configuration_files()
    {
        $this->load(__DIR__.'/../../resources/config/example.yml')->shouldReturnAnInstanceOf('\LdapTools\Configuration');
    }

    function it_should_error_when_the_configuration_file_is_not_found()
    {
        $this->shouldthrow('\LdapTools\Exception\ConfigurationException')->duringLoad(__DIR__.'/thisisasuperlongfilenamethatshouldneverexist.yml');
    }

    function it_should_return_self_when_calling_setDefaultDomain()
    {
        $this->setDefaultDomain('foo.bar')->shouldReturnAnInstanceOf('\LdapTools\Configuration');
    }

    function it_should_return_correct_domain_when_calling_getDefaultDomain()
    {
        $this->setDefaultDomain('foo.bar');
        $this->getDefaultDomain()->shouldBeEqualTo('foo.bar');
    }

    function it_should_return_an_array_when_calling_getDomainConfiguration()
    {
        $this->getDomainConfiguration()->shouldBeArray();
    }

    function it_should_return_a_DomainConfiguration_when_calling_getDomainConfiguration_with_a_domain_name()
    {
        $this->getDomainConfiguration('foo.bar')->shouldReturnAnInstanceOf('\LdapTools\DomainConfiguration');
    }

    function it_should_throw_InvalidArgumentException_when_calling_getDomainConfiguration_with_an_invalid_name()
    {
        $this->shouldThrow('\InvalidArgumentException')->duringGetDomainConfiguration('happyfuntime');
    }

    function it_should_return_the_correct_number_of_domains_when_calling_getDomainConfiguration()
    {
        $this->getDomainConfiguration()->shouldHaveCount(2);
    }

    function it_should_return_a_string_when_calling_getSchemaFolder()
    {
        $this->getSchemaFolder()->shouldBeString();
    }

    function it_should_return_the_correct_schema_folder_after_calling_setSchemaFolder()
    {
        $this->setSchemaFolder('/dev/null');
        $this->getSchemaFolder()->shouldBeEqualTo('/dev/null');
    }

    function it_should_return_a_string_when_calling_getCacheType()
    {
        $this->getCacheType()->shouldBeString();
    }

    function it_should_return_the_correct_cache_type_after_calling_setCacheType()
    {
        $this->setCacheType(CacheFactory::TYPE_STASH);
        $this->getCacheType()->shouldBeEqualTo(CacheFactory::TYPE_STASH);
    }

    function it_should_throw_ConfigurationException_when_setting_invalid_cache_type()
    {
        $this->shouldThrow('\LdapTools\Exception\ConfigurationException')->duringSetCacheType('foo.bar');
    }
}
