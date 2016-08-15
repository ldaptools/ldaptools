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

use LdapTools\Object\LdapObject;
use LdapTools\Object\LdapObjectCollection;
use LdapTools\Schema\LdapObjectSchema;
use PhpSpec\ObjectBehavior;

class LdapResultSorterSpec extends ObjectBehavior
{
    protected $toSort = [
        [
            'firstName' => 'Bob',
            'lastName' => 'Thomas',
        ],
        [
            'firstName' => 'Gregory',
            'lastName' => 'Smith',
        ],
        [
            'firstName' => 'Amy',
            'lastName' => 'Feng',
        ],
        [
            'firstName' => 'Amy',
            'lastName' => 'Yang',
        ],
        [
            'firstName' => 'Tim',
            'lastName' => 'Peterson',
        ],
        [
            'firstName' => 'Chad',
            'lastName' => 'Sikorra',
        ],
    ];

    protected $toSortGroups = [
        [
            'name' => 'Marketing',
            'description' => 'Pointless Stuff',
        ],
        [
            'name' => 'Finance',
            'description' => 'Money and Things',
        ],
        [
            'name' => 'Accounting',
            'description' => 'Excel Spreadsheet Makers',
        ],
        [
            'name' => 'Accounts Payable',
            'description' => 'More Money and Things',
        ],
        [
            'name' => 'IT',
            'description' => 'Tech People',
        ],
        [
            'name' => 'Environmental',
            'description' => 'Waste Disposal Specialists',
        ],
    ];

    protected $toSortUtf8 = [
        [
            'name' => 'Böb',
        ],
        [
            'name' => 'Tim',
        ],
        [
            'name' => 'Müller',
        ],
        [
            'name' => 'Mike',
        ],
        [
            'name' => 'Ädam',
        ],
    ];

    protected $orderBy = [
        'firstName' => 'ASC'
    ];

    /**
     * @var LdapObjectCollection
     */
    protected $collection;

    function let()
    {
        $this->collection = new LdapObjectCollection();
        foreach ($this->toSort as $sort) {
            $this->collection->add(new LdapObject($sort, 'user'));
        }
        $this->beConstructedWith($this->orderBy);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Query\LdapResultSorter');
    }

    function it_should_be_set_to_case_insensitive_by_default()
    {
        $this->getIsCaseSensitive()->shouldBeEqualTo(false);
    }

    function it_should_sort_an_array_of_results_ascending_by_first_name()
    {
        $this->sort($this->toSort)->shouldHaveFirstValue('firstName','Amy');
        $this->sort($this->toSort)->shouldHaveLastValue('firstName','Tim');

        $this->sort($this->collection->toArray())->shouldHaveFirstValue('firstName','Amy');
        $this->sort($this->collection->toArray())->shouldHaveLastValue('firstName','Tim');
    }

    function it_should_sort_an_array_of_results_descending_by_first_name()
    {
        $this->beConstructedWith(['firstName' => 'DESC']);
        $this->sort($this->toSort)->shouldHaveFirstValue('firstName','Tim');
        $this->sort($this->toSort)->shouldHaveLastValue('firstName','Amy');
        $this->sort($this->collection->toArray())->shouldHaveFirstValue('firstName','Tim');
        $this->sort($this->collection->toArray())->shouldHaveLastValue('firstName','Amy');
    }

    function it_should_sort_on_multiple_attributes_desc()
    {
        $this->beConstructedWith(['firstName' => 'ASC', 'lastName' => 'DESC']);
        $this->sort($this->toSort)->shouldHaveFirstValue('firstName','Amy');
        $this->sort($this->toSort)->shouldHaveFirstValue('lastName','Yang');
        $this->sort($this->collection->toArray())->shouldHaveFirstValue('firstName','Amy');
        $this->sort($this->collection->toArray())->shouldHaveFirstValue('lastName','Yang');
    }

    function it_should_sort_on_multiple_attributes_asc()
    {
        $this->beConstructedWith(['firstName' => 'ASC', 'lastName' => 'ASC']);
        $this->sort($this->toSort)->shouldHaveFirstValue('lastName','Feng');
        $this->sort($this->toSort)->shouldHaveFirstValue('firstName','Amy');
        $this->sort($this->collection->toArray())->shouldHaveFirstValue('lastName','Feng');
        $this->sort($this->collection->toArray())->shouldHaveFirstValue('firstName','Amy');
    }

    function it_should_sort_on_a_datetime_object_asc()
    {
        $toSort = $this->toSort;
        $toSort[0]['created'] = new \DateTime('2014-6-07');
        $toSort[1]['created'] = new \DateTime('2013-5-01');
        $toSort[2]['created'] = new \DateTime('2015-2-22');
        $toSort[3]['created'] = new \DateTime('2014-8-01');
        $this->beConstructedWith(['created' => 'ASC']);
        $this->sort($toSort)->shouldHaveFirstValue('lastName','Smith');
    }

    function it_should_sort_on_a_datetime_object_desc()
    {
        $toSort = $this->toSort;
        $toSort[0]['created'] = new \DateTime('2014-6-07');
        $toSort[1]['created'] = new \DateTime('2013-5-01');
        $toSort[2]['created'] = new \DateTime('2015-2-22');
        $toSort[3]['created'] = new \DateTime('2014-8-01');
        $this->beConstructedWith(['created' => 'DESC']);
        $this->sort($toSort)->shouldHaveFirstValue('lastName','Feng');
        $this->sort($toSort)->shouldHaveFirstValue('firstName','Amy');
    }

    function it_should_sort_on_multiple_attributes_with_aliases()
    {
        $aliases = [
            'user' => new LdapObjectSchema('ad', 'user'),
            'group' => new LdapObjectSchema('ad', 'group'),
        ];
        $this->beConstructedWith(['name' => 'ASC', 'user.firstName' => 'ASC'], $aliases);
        foreach ($this->toSortGroups as $sort) {
            $this->collection->add(new LdapObject($sort, 'group'));
        }

        $this->sort($this->toSort)->shouldHaveFirstValue('firstName','Amy');
        $this->sort($this->toSort)->shouldHaveLastValue('firstName','Tim');
        $this->sort(array_merge($this->toSort, $this->toSortGroups))->shouldHaveFirstValue('name','Accounting');
        $this->sort(array_merge($this->toSort, $this->toSortGroups))->shouldHaveLastValue('lastName','Peterson');
        $this->sort($this->collection)->first()->get('name')->shouldBeEqualTo('Accounting');
        $this->sort($this->collection)->last()->get('lastName')->shouldBeEqualTo('Peterson');
    }

    function it_should_sort_UTF8_data()
    {
        $this->collection = new LdapObjectCollection();
        foreach ($this->toSortUtf8 as $sort) {
            $this->collection->add(new LdapObject($sort, 'user'));
        }
        $this->beConstructedWith(['name' => 'ASC']);

        $arrayResult = [
            [
                'name' => "Ädam",
            ],
            [
                'name' => "Böb",
            ],
            [
                'name' => "Mike",
            ],
            [
                'name' => "Müller",
            ],
            [
                'name' => "Tim",
            ],
        ];
        $objectResult = new LdapObjectCollection();
        foreach ($arrayResult as $entry) {
            $objectResult->add(new LdapObject($entry, 'user'));
        }

        $this->sort($this->toSortUtf8)->shouldEqual($arrayResult);
        $this->sort($this->collection)->shouldBeLike($objectResult);
    }

    function it_should_sort_case_insensitive_by_default()
    {
        $results = $this->toSortUtf8;
        array_push($results, ['name' => 'mike']);
        array_unshift($results, ['name' => 'mIkE']);
        array_push($results, ['name' => 'MikE']);
        $this->beConstructedWith(['name' => 'ASC']);
        $this->collection = new LdapObjectCollection();
        foreach ($results as $sort) {
            $this->collection->add(new LdapObject($sort, 'user'));
        }

        $arrayResult = [
            [
                'name' => "Ädam",
            ],
            [
                'name' => "Böb",
            ],
            [
                'name' => "mIkE",
            ],
            [
                'name' => "Mike",
            ],
            [
                'name' => "mike",
            ],
            [
                'name' => "MikE",
            ],
            [
                'name' => "Müller",
            ],
            [
                'name' => "Tim",
            ],
        ];
        $objectResult = new LdapObjectCollection();
        foreach ($arrayResult as $entry) {
            $objectResult->add(new LdapObject($entry, 'user'));
        }

        $this->sort($results)->shouldBeEqualTo($arrayResult);
        $this->sort($this->collection)->shouldBeLike($objectResult);
    }

    function it_should_sort_case_sensitive_if_specified()
    {
        $results = $this->toSortUtf8;
        array_push($results, ['name' => 'mike']);
        array_unshift($results, ['name' => 'mIkE']);
        array_push($results, ['name' => 'MikE']);
        $this->collection = new LdapObjectCollection();
        foreach ($results as $sort) {
            $this->collection->add(new LdapObject($sort, 'user'));
        }
        $this->beConstructedWith(['name' => 'ASC']);

        $arrayResult = [
            [
                'name' => "Ädam",
            ],
            [
                'name' => "Böb",
            ],
            [
                'name' => "mike",
            ],
            [
                'name' => "mIkE",
            ],
            [
                'name' => "Mike",
            ],
            [
                'name' => "MikE",
            ],
            [
                'name' => "Müller",
            ],
            [
                'name' => "Tim",
            ],
        ];
        $objectResult = new LdapObjectCollection();
        foreach ($arrayResult as $entry) {
            $objectResult->add(new LdapObject($entry, 'user'));
        }

        $this->setIsCaseSensitive(true)->shouldReturnAnInstanceOf('LdapTools\Query\LdapResultSorter');
        $this->sort($results)->shouldBeEqualTo($arrayResult);
        $this->sort($this->collection)->shouldBeLike($objectResult);
    }

    public function getMatchers()
    {
        return [
            'haveFirstValue' => function ($subject, $key, $value) {
                $subject = reset($subject);
                $subject = is_array($subject) ? $subject[$key] : $subject->get($key);
                return ($subject === $value);
            },
            'haveLastValue' => function ($subject, $key, $value) {
                $subject = end($subject);
                $subject = is_array($subject) ? $subject[$key] : $subject->get($key);
                return ($subject === $value);
            },
        ];
    }
}
