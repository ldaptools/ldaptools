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

use PhpSpec\ObjectBehavior;

class ConvertLdapObjectTypeSpec extends ObjectBehavior
{
    protected $options = [
        'user' => [ 'top', 'person', 'organizationalPerson', 'user' ],
        'group' => [ 'top', 'group' ],
        'computer' => [ 'top', 'person', 'organizationalPerson', 'user', 'computer' ],
        'contact' => [ 'top', 'person', 'organizationalPerson', 'contact' ],
        'ou' => [ 'top', 'organizationalUnit' ],
    ];
    
    function let()
    {
        $this->setOptions($this->options);   
    }
    
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\AttributeConverter\ConvertLdapObjectType');
    }
    
    function it_should_implement_AttributeConverterInterface()
    {
        $this->shouldImplement('\LdapTools\AttributeConverter\AttributeConverterInterface');
    }

    function it_should_convert_classes_to_the_LdapTools_object_type_for_AD()
    {
        foreach ($this->options as $type => $classes) {
            $this->fromLdap($classes)->shouldBeEqualTo([$type]);
        }
    }
    
    function it_should_return_unknown_if_the_type_cannot_be_determined()
    {
        $this->fromLdap(['foo'])->shouldBeEqualTo(['Unknown']);
    }
    
    function it_should_not_support_converting_to_ldap()
    {
        $this->shouldThrow('LdapTools\Exception\AttributeConverterException')->duringToLdap('foo');
    }
}
