<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Query;

use PhpSpec\ObjectBehavior;

class MatchingRuleOidSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Query\MatchingRuleOid');
    }

    function it_should_have_a_bitwise_and_oid_constant()
    {
        $this->shouldHaveConstant('BIT_AND');
    }

    function it_should_have_a_bitwise_or_oid_constant()
    {
        $this->shouldHaveConstant('BIT_OR');
    }

    function it_should_have_a_in_chain_oid_constant()
    {
        $this->shouldHaveConstant('IN_CHAIN');
    }

    public function getMatchers()
    {
        return [
            'haveConstant' => function($subject, $constant) {
                return defined('\LdapTools\Query\MatchingRuleOid::'.$constant);
            }
        ];
    }
}
