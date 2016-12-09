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

use LdapTools\AttributeConverter\ConvertExchangeRecipientType;
use LdapTools\Connection\AD\ExchangeRecipient;
use PhpSpec\ObjectBehavior;

class ConvertExchangeRecipientTypeSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(ConvertExchangeRecipientType::class);
    }

    function it_should_convert_a_recipient_type_details_number_to_the_friendly_name()
    {
        $this->setAttribute('recipientTypeDetails');

        foreach (ExchangeRecipient::TYPE_DETAILS as $type => $number) {
            $this->fromLdap($number)->shouldBeEqualTo($type);
        }
    }

    function it_should_convert_a_recipient_display_type_number_to_the_friendly_name()
    {
        $this->setAttribute('recipientDisplayType');

        foreach (ExchangeRecipient::DISPLAY_TYPE as $type => $number) {
            $this->fromLdap($number)->shouldBeEqualTo($type);
        }
    }

    function it_should_convert_a_recipient_type_details_name_to_the_number()
    {
        $this->setAttribute('recipientTypeDetails');

        foreach (ExchangeRecipient::TYPE_DETAILS as $type => $number) {
            $this->toLdap($type)->shouldBeEqualTo((string) $number);
        }
    }

    function it_should_convert_a_recipient_display_type_name_to_the_number()
    {
        $this->setAttribute('recipientDisplayType');

        foreach (ExchangeRecipient::DISPLAY_TYPE as $type => $number) {
            $this->toLdap($type)->shouldBeEqualTo((string) $number);
        }
    }
}
