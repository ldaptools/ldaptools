<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Enums\AD;

use LdapTools\Enums\AD\ResponseCode;
use PhpSpec\ObjectBehavior;

class ResponseCodeSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('AccountDisabled');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(ResponseCode::class);
    }

    function it_should_get_the_error_message_for_the_instantiated_enum()
    {
        $this->getMessage()->shouldBeEqualTo('The account is currently disabled.');
    }

    function it_should_get_the_error_message_for_a_response_code_enum_name()
    {
        $this::getMessageForError('AccountLocked')->shouldBeEqualTo('The account is currently locked out.');
    }

    function it_should_get_the_error_message_for_a_response_code_enum_value()
    {
        $this::getMessageForError(1909)->shouldBeEqualTo('The account is currently locked out.');
    }

    function it_should_check_if_an_error_message_exists_for_a_given_error()
    {
        $this::hasMessageForError('AccountLocked')->shouldBeEqualTo(true);
        $this::hasMessageForError(1909)->shouldBeEqualTo(true);
        $this::hasMessageForError('foo')->shouldBeEqualTo(false);
    }
}
