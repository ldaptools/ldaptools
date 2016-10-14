<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Connection\AD;

use PhpSpec\ObjectBehavior;

class ADFunctionalLevelTypeSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Connection\AD\ADFunctionalLevelType');
    }

    function it_should_have_a_WIN2000_constant()
    {
        $this->shouldHaveConstant('WIN2000');
    }

    function it_should_have_a_WIN2003_constant()
    {
        $this->shouldHaveConstant('WIN2003');
    }

    function it_should_have_a_WIN2003_MIXED_DOMAIN_constant()
    {
        $this->shouldHaveConstant('WIN2003_MIXED_DOMAIN');
    }

    function it_should_have_a_WIN2008_constant()
    {
        $this->shouldHaveConstant('WIN2008');
    }

    function it_should_have_a_WIN2008R2_constant()
    {
        $this->shouldHaveConstant('WIN2008R2');
    }

    function it_should_have_a_WIN2012_constant()
    {
        $this->shouldHaveConstant('WIN2012');
    }

    function it_should_have_a_WIN2012R2_constant()
    {
        $this->shouldHaveConstant('WIN2012R2');
    }

    function it_should_have_a_WIN2016_constant()
    {
        $this->shouldHaveConstant('WIN2016');
    }

    public function getMatchers()
    {
        return [
            'haveConstant' => function($subject, $constant) {
                return defined('\LdapTools\Connection\AD\ADFunctionalLevelType::'.$constant);
            }
        ];
    }
}
