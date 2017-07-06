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

use LdapTools\AttributeConverter\ConvertEnum;
use LdapTools\Enums\Exchange\RecipientDisplayType;
use PhpSpec\ObjectBehavior;

class ConvertEnumSpec extends ObjectBehavior
{
    function let()
    {
        $this->setOptions(['enum' => '\LdapTools\Enums\Exchange\RecipientDisplayType']);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(ConvertEnum::class);
    }

    function it_should_convert_an_enum_to_ldap()
    {
        $this->setOperationType(ConvertEnum::TYPE_CREATE);
        $this->toLdap('DistributionGroup')->shouldBeEqualTo('1');
    }

    function it_should_convert_an_enum_object_instance_to_ldap()
    {
        $this->setOperationType(ConvertEnum::TYPE_CREATE);
        $recipient = new RecipientDisplayType('DistributionGroup');
        $this->toLdap($recipient)->shouldBeEqualTo('1');

    }

    function it_should_convert_an_enum_from_ldap()
    {
        $this->setOperationType(ConvertEnum::TYPE_SEARCH_FROM);
        $this->fromLdap('1')->shouldBeEqualTo('DistributionGroup');
    }

    function it_should_throw_an_error_if_the_enum_does_not_implement_the_enum_interface()
    {
        $this->setOptions(['enum' => '\SplObjectStorage']);

        $this->shouldThrow('LdapTools\Exception\AttributeConverterException')->duringToLdap('DistributionGroup');
    }
}
