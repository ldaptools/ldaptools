<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Connection;

use PhpSpec\ObjectBehavior;

class ADResponseCodesSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Connection\ADResponseCodes');
    }

    function it_should_have_a_constant_for_an_invalid_account()
    {
        $this->shouldHaveConstant('ACCOUNT_INVALID');
    }

    function it_should_have_a_constant_for_an_incorrect_current_password()
    {
        $this->shouldHaveConstant('CURRENT_PASSWORD_INCORRECT');
    }

    function it_should_have_a_constant_for_a_malformed_password()
    {
        $this->shouldHaveConstant('PASSWORD_MALFORMED');
    }

    function it_should_have_a_constant_for_password_restrictions()
    {
        $this->shouldHaveConstant('PASSWORD_RESTRICTIONS');
    }

    function it_should_have_a_constant_for_account_credentials_invalid()
    {
        $this->shouldHaveConstant('ACCOUNT_CREDENTIALS_INVALID');
    }

    function it_should_have_a_constant_for_account_restrictions()
    {
        $this->shouldHaveConstant('ACCOUNT_RESTRICTIONS');
    }

    function it_should_have_a_constant_for_account_restrictions_time()
    {
        $this->shouldHaveConstant('ACCOUNT_RESTRICTIONS_TIME');
    }

    function it_should_have_a_constant_for_account_restrictions_device()
    {
        $this->shouldHaveConstant('ACCOUNT_RESTRICTIONS_DEVICE');
    }

    function it_should_have_a_constant_for_account_password_expired()
    {
        $this->shouldHaveConstant('ACCOUNT_PASSWORD_EXPIRED');
    }

    function it_should_have_a_constant_for_account_disabled()
    {
        $this->shouldHaveConstant('ACCOUNT_DISABLED');
    }

    function it_should_have_a_constant_for_account_context_ids()
    {
        $this->shouldHaveConstant('ACCOUNT_CONTEXT_IDS');
    }

    function it_should_have_a_constant_for_account_expired()
    {
        $this->shouldHaveConstant('ACCOUNT_EXPIRED');
    }

    function it_should_have_a_constant_for_account_password_must_change()
    {
        $this->shouldHaveConstant('ACCOUNT_PASSWORD_MUST_CHANGE');
    }

    function it_should_have_a_constant_for_account_locked()
    {
        $this->shouldHaveConstant('ACCOUNT_LOCKED');
    }

    function it_should_have_a_constant_for_member_not_in_group()
    {
        $this->shouldHaveConstant('MEMBER_NOT_IN_GROUP');
    }

    public function getMatchers()
    {
        return [
            'haveConstant' => function($subject, $constant) {
                return defined('\LdapTools\Connection\ADResponseCodes::'.$constant);
            }
        ];
    }
}
