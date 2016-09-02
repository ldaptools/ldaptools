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

use PhpSpec\ObjectBehavior;

class CacheFactorySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Factory\CacheFactory');
    }

    function it_should_have_a_constant_for_the_stash_cache_type()
    {
        $this->shouldHaveConstant('TYPE_STASH');
    }

    function it_should_have_a_constant_for_the_doctrine_cache_type()
    {
        $this->shouldHaveConstant('TYPE_DOCTRINE');
    }

    function it_should_return_the_stash_type_when_calling_get_with_stash()
    {
        $this->get('stash', [])->shouldBeAnInstanceOf('\LdapTools\Cache\StashCache');
    }

    function it_should_return_the_NoCache_type_when_calling_get_with_none()
    {
        $this->get('none', [])->shouldBeAnInstanceOf('\LdapTools\Cache\NoCache');
    }

    function it_should_return_the_DoctrineCache_type_when_calling_get_with_doctrine()
    {
        $this->get('doctrine', [])->shouldBeAnInstanceOf('\LdapTools\Cache\DoctrineCache');
    }

    function it_should_thrown_InvalidArgumentException_when_passing_unknown_cache_types()
    {
        $this->shouldThrow('\LdapTools\Exception\InvalidArgumentException')->duringGet('foobar', []);
    }

    public function getMatchers()
    {
        return [
            'haveConstant' => function($subject, $constant) {
                return defined('\LdapTools\Factory\CacheFactory::'.$constant);
            }
        ];
    }
}
