<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\AttributeConverter;

use LdapTools\AttributeConverter\ConvertAccountName;
use PhpSpec\ObjectBehavior;

class ConvertAccountNameSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(ConvertAccountName::class);
    }

    function it_should_remove_unallowed_characters_going_to_ldap_on_create_or_modify()
    {
        $this->setOperationType(ConvertAccountName::TYPE_MODIFY);

        $this->toLdap('Foo.Bar.')->shouldBeEqualTo('Foo.Bar');
        $this->toLdap('Foo=Bar+ foo')->shouldBeEqualTo('FooBarfoo');

        $this->setOperationType(ConvertAccountName::TYPE_CREATE);

        $this->toLdap('Foo.Bar.')->shouldBeEqualTo('Foo.Bar');
        $this->toLdap('Foo=Bar+ foo')->shouldBeEqualTo('FooBarfoo');
    }

    function it_should_not_remove_unallowed_characters_on_a_search()
    {
        $this->setOperationType(ConvertAccountName::TYPE_SEARCH_TO);

        $this->toLdap('Foo.Bar.')->shouldBeEqualTo('Foo.Bar.');
        $this->toLdap('Foo=Bar+ foo')->shouldBeEqualTo('Foo=Bar+ foo');
    }

    function it_should_not_remove_unallowed_characters_from_ldap()
    {
        $this->fromLdap('Foo.Bar.')->shouldBeEqualTo('Foo.Bar.');
        $this->fromLdap('Foo=Bar+ foo')->shouldBeEqualTo('Foo=Bar+ foo');
    }
}
