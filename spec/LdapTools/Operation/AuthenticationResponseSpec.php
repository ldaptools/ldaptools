<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Operation;

use PhpSpec\ObjectBehavior;

class AuthenticationResponseSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->beConstructedWith(true);
        $this->shouldHaveType('LdapTools\Operation\AuthenticationResponse');
        $this->isAuthenticated()->shouldBeEqualTo(true);
    }

    function it_should_set_whether_it_is_authenticated()
    {
        $this->beConstructedWith(false);
        $this->isAuthenticated()->shouldBeEqualTo(false);
    }

    function it_should_get_the_error_message_and_error_code()
    {
        $message = 'Foo';
        $code = 2;
        $this->beConstructedWith(false, $message, $code);

        $this->getErrorMessage()->shouldBeEqualTo($message);
        $this->getErrorCode()->shouldBeEqualTo($code);
    }
}
