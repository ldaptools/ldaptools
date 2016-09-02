<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Ldif\UrlLoader;

use PhpSpec\ObjectBehavior;

class BaseUrlLoaderSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Ldif\UrlLoader\BaseUrlLoader');
    }

    function it_should_implement_UrlLoaderInterface()
    {
        $this->shouldImplement('\LdapTools\Ldif\UrlLoader\UrlLoaderInterface');
    }

    function it_should_load_a_file()
    {
        $this->load('file://'.__DIR__.'/../../../resources/ldif/url_load_test.txt')->shouldBeEqualTo('foo');
    }

    function it_should_throw_a_url_loader_exception_if_the_file_cannot_be_found()
    {
        $this->shouldThrow('LdapTools\Exception\LdifUrlLoaderException')->duringLoad('file:///this/is/a/ridiculous/path/name/that/should/never/exist.txt');
    }
}
