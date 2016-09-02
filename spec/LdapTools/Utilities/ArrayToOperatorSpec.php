<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Utilities;

use LdapTools\Schema\LdapObjectSchema;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Yaml\Yaml;

class ArrayToOperatorSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Utilities\ArrayToOperator');
    }
    
    function it_should_get_an_operator_filter_from_an_array()
    {
        $yml = Yaml::parse(file_get_contents(__DIR__.'/../../resources/filter/filters.yaml'));
        
        $this->toOperator($yml['filter1'])->toLdapFilter()->shouldBeEqualTo('(&(serverRole=*)(username=admin*))');
        $this->toOperator($yml['filter2'])->toLdapFilter()->shouldBeEqualTo('(&(serverRole=*)(username=admin*))');
        $this->toOperator($yml['filter3'])->toLdapFilter()->shouldBeEqualTo('(&(&(name=chad)(state=WI))(|(name=Frank)(state=IL)))');
        $this->toOperator($yml['filter4'])->toLdapFilter()->shouldBeEqualTo('(&(&(objectClass=user)(objectCategory=person))(&(emailAddress=*)(department=IT*)))');
    }
    
    function it_should_get_an_operator_filter_for_a_schema_adding_the_objectClass_objectCategory_and_filter_array()
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setObjectClass(['user']);
        $schema->setObjectCategory('person');
        
        $this->getOperatorForSchema($schema, [])->toLdapFilter()->shouldBeEqualTo('(&(objectCategory=person)(objectClass=user))');
        $this->getOperatorForSchema($schema, ['starts_with' => ['username', 'admin']])->toLdapFilter()->shouldBeEqualTo('(&(&(objectCategory=person)(objectClass=user))(&(username=admin*)))');
    }
}
