<?php

namespace SitPHP\Helpers\Tests;

use Doublit\TestCase;
use SitPHP\Helpers\Collection;

class CollectionTest extends TestCase
{
    /*
     * Test basics
     */
    function testGetAddSet()
    {
        $collection = new Collection();
        $array = ['item_1', 'item_2'];
        $collection->add($array);
        $collection->set('name', $array);
        $this->assertEquals($array, $collection->get(0));
        $this->assertEquals($array, $collection->get('name'));
    }

    function testAddWithInvalidItemShouldFail()
    {
        $this->expectException(\InvalidArgumentException::class);
        $collection = new Collection();
        $collection->add(new \stdClass());
    }

    function testSetWithInvalidKeyShouldFail()
    {
        $this->expectException(\InvalidArgumentException::class);
        $collection = new Collection();
        $collection->set(new \stdClass(), ['value']);
    }

    function testSetWithInvalidItemShouldFail()
    {
        $this->expectException(\InvalidArgumentException::class);
        $collection = new Collection();
        $collection->set('item', new \stdClass());
    }

    function testCollectionShouldBehaveAsArray()
    {
        $collection = new Collection();
        $array_1 = ['item_1', 'item_2'];
        $array_2 = ['item_3', 'item_4'];

        $collection['key'] = $array_1;
        $collection[] = $array_2;

        $this->assertEquals($array_1, $collection['key']);
        $this->assertEquals($array_2, $collection[0]);

        unset($collection['key']);
        $this->assertNull($collection['key']);
        $this->assertTrue(isset($collection[0]));
        $this->assertEquals([0 => $array_2], current($collection));
    }

    function testCollectionShouldBeIterable()
    {
        $collection = new Collection();
        $array_1 = ['item_1', 'item_2'];
        $array_2 = ['item_3', 'item_4'];
        $collection->add($array_1);
        $collection->add($array_2);

        $test = [];
        foreach ($collection as $key => $item) {
            $test[$key] = $item;
        }
        $this->assertEquals([$array_1, $array_2], $test);
    }

    function testCollectionShouldBeCountable()
    {
        $collection = new Collection();
        $array_1 = ['item_1', 'item_2'];
        $array_2 = ['item_3', 'item_4'];
        $collection->add($array_1);
        $collection->add($array_2);

        $this->assertEquals(2, count($collection));
    }

    function testHasValue()
    {
        $array_1 = ['item_1', 'item_2'];
        $array_2 = ['item_3', 'item_4'];
        $collection = new Collection([$array_1, $array_2]);

        $this->assertTrue($collection->hasKeyValue(0, 'item_1'));
        $this->assertFalse($collection->hasKeyValue(0, 'item_2'));
    }

    /*
     * Test filter
     */
    function testFilterBy()
    {
        $person_1 = ['name' => 'name-1', 'surname' => 'family-1', 'has_children' => true];
        $person_2 = ['name' => 'name-2', 'surname' => 'family-1', 'has_children' => false];
        $person_3 = ['name' => 'name-3', 'surname' => 'family-2', 'has_children' => 1];
        $person_4 = ['name' => 'name-4', 'surname' => 'family-3', 'has_children' => true];
        $collection = new Collection([$person_1, $person_2, $person_3, $person_4]);

        $filtered_by = $collection->filterBy('surname', 'family-1');
        $filtered_by_strict = $collection->filterBy('has_children', true);
        $filtered_by_not_strict = $collection->filterBy('has_children', true, false);


        $this->assertEquals([$person_1, $person_2], $filtered_by->toArray());
        $this->assertEquals([$person_1, $person_4], $filtered_by_strict->toArray());
        $this->assertEquals([$person_1, $person_3, $person_4], $filtered_by_not_strict->toArray());
    }

    function testFilterIn()
    {
        $person_1 = ['name' => 'name-1', 'surname' => 'family-1', 'has_children' => true];
        $person_2 = ['name' => 'name-2', 'surname' => 'family-1', 'has_children' => false];
        $person_3 = ['name' => 'name-3', 'surname' => 'family-2', 'has_children' => 1];
        $person_4 = ['name' => 'name-4', 'surname' => 'family-3', 'has_children' => true];
        $collection = new Collection([$person_1, $person_2, $person_3, $person_4]);

        $filtered_in = $collection->filterIn('surname', ['family-1', 'family-2']);
        $filtered_in_not_strict = $collection->filterIn('has_children', [true], false);

        $this->assertEquals([$person_1, $person_3, $person_4], $filtered_in_not_strict->toArray());
        $this->assertEquals([$person_1, $person_2, $person_3], $filtered_in->toArray());

    }

    function testFilterCallback()
    {
        $person_1 = ['name' => 'name-1', 'surname' => 'family-1', 'has_children' => true];
        $person_2 = ['name' => 'name-2', 'surname' => 'family-1', 'has_children' => false];
        $person_3 = ['name' => 'name-3', 'surname' => 'family-2', 'has_children' => 1];
        $person_4 = ['name' => 'name-4', 'surname' => 'family-3', 'has_children' => true];
        $collection = new Collection([$person_1, $person_2, $person_3, $person_4]);

        $filtered_callback = $collection->filterCallback(function ($item) {
            return $item['name'] === 'name-1' || $item['name'] === 'name-2';
        });

        $this->assertEquals([$person_1, $person_2], $filtered_callback->toArray());
    }

    function testFilterNotBy()
    {
        $person_1 = ['name' => 'name-1', 'surname' => 'family-1', 'has_children' => true];
        $person_2 = ['name' => 'name-3', 'surname' => 'family-2', 'has_children' => 1];
        $person_3 = ['name' => 'name-2', 'surname' => 'family-1', 'has_children' => false];
        $person_4 = ['name' => 'name-4', 'surname' => 'family-3', 'has_children' => true];
        $collection = new Collection([$person_1, $person_2, $person_3, $person_4]);

        $filtered_not_by = $collection->filterNotBy('surname', 'family-1');
        $filtered_not_by_not_strict = $collection->filterNotBy('has_children', false, false);
        $this->assertEquals([$person_2, $person_4], $filtered_not_by->toArray());
        $this->assertEquals([$person_1, $person_2, $person_4], $filtered_not_by_not_strict->toArray());
    }

    function testFilterNotIn()
    {
        $person_1 = ['name' => 'name-1', 'surname' => 'family-1', 'has_children' => true];
        $person_2 = ['name' => 'name-2', 'surname' => 'family-1', 'has_children' => false];
        $person_3 = ['name' => 'name-3', 'surname' => 'family-2', 'has_children' => 1];
        $person_4 = ['name' => 'name-4', 'surname' => 'family-3', 'has_children' => true];
        $collection = new Collection([$person_1, $person_2, $person_3, $person_4]);

        $filtered_not_in = $collection->filterNotIn('surname', ['family-1', 'family-2']);
        $filtered_not_in_not_strict = $collection->filterNotIn('has_children', [false], false);

        $this->assertEquals([$person_4], $filtered_not_in->toArray());
        $this->assertEquals([$person_1, $person_3, $person_4], $filtered_not_in_not_strict->toArray());
    }

    /*
     * Test first
     */
    function testFirstWith()
    {
        $person_1 = ['name' => 'name-1', 'surname' => 'family-1', 'has_children' => false];
        $person_2 = ['name' => 'name-2', 'surname' => 'family-1', 'has_children' => 1];
        $person_3 = ['name' => 'name-3', 'surname' => 'family-2', 'has_children' => true];
        $person_4 = ['name' => 'name-4', 'surname' => 'family-3', 'has_children' => true];
        $collection = new Collection([$person_1, $person_2, $person_3, $person_4]);

        $this->assertEquals($person_2, $collection->firstWith('name', 'name-2'));
        $this->assertEquals($person_2, $collection->firstWith('has_children', true, false));
        $this->assertEquals($person_3, $collection->firstWith('has_children', true));
        $this->assertNull($collection->firstWith('has_children', 'undefined'));
        $this->assertNull($collection->firstWith('undefined', 'undefined'));
    }

    function testFirstIn()
    {
        $person_1 = ['name' => 'name-1', 'surname' => 'family-1', 'has_children' => false];
        $person_2 = ['name' => 'name-2', 'surname' => 'family-1', 'has_children' => 1];
        $person_3 = ['name' => 'name-3', 'surname' => 'family-2', 'has_children' => true];
        $person_4 = ['name' => 'name-4', 'surname' => 'family-3', 'has_children' => true];
        $collection = new Collection([$person_1, $person_2, $person_3, $person_4]);

        $this->assertEquals($person_2, $collection->firstIn('name', ['name-2', 'name-3']));
        $this->assertEquals($person_2, $collection->firstIn('has_children', [true], false));
        $this->assertNull($collection->firstIn('name', ['undefined']));
    }

    function testFirstCallback()
    {
        $person_1 = ['name' => 'name-1', 'surname' => 'family-1', 'has_children' => false];
        $person_2 = ['name' => 'name-2', 'surname' => 'family-1', 'has_children' => 1];
        $person_3 = ['name' => 'name-3', 'surname' => 'family-2', 'has_children' => true];
        $person_4 = ['name' => 'name-4', 'surname' => 'family-3', 'has_children' => true];
        $collection = new Collection(['person-1' => $person_1, 'person-2' => $person_2, 'person-3' => $person_3, 'person-4' => $person_4]);

        $this->assertEquals($person_2, $collection->firstCallback(function ($item, $key) {
            return $key === 'person-2';
        }));
        $this->assertEquals($person_4, $collection->firstCallback(function ($item, $key) {
            return $item['name'] === 'name-4';
        }));
        $this->assertNull($collection->firstCallback(function ($item) {
            return $item['name'] === 'undefined';
        }));
    }

    function testFirstNotWith()
    {
        $person_1 = ['name' => 'name-1', 'surname' => 'family-1', 'has_children' => 1];
        $person_2 = ['name' => 'name-2', 'surname' => 'family-1', 'has_children' => true];
        $person_3 = ['name' => 'name-3', 'surname' => 'family-1', 'has_children' => false];
        $person_4 = ['name' => 'name-4', 'surname' => 'family-1', 'has_children' => true];
        $collection = new Collection(['person-1' => $person_1, 'person-2' => $person_2, 'person-3' => $person_3, 'person-4' => $person_4]);

        $this->assertEquals($person_1, $collection->firstNotWith('has_children', true));
        $this->assertEquals($person_3, $collection->firstNotWith('has_children', true, false));
        $this->assertNull($collection->firstNotWith('surname', 'family-1'));
    }

    function testFirstNotIn()
    {
        $person_1 = ['name' => 'name-1', 'surname' => 'family-1', 'has_children' => false];
        $person_2 = ['name' => 'name-2', 'surname' => 'family-1', 'has_children' => 1];
        $person_3 = ['name' => 'name-3', 'surname' => 'family-2', 'has_children' => true];
        $person_4 = ['name' => 'name-4', 'surname' => 'family-3', 'has_children' => true];
        $collection = new Collection(['person-1' => $person_1, 'person-2' => $person_2, 'person-3' => $person_3, 'person-4' => $person_4]);

        $this->assertEquals($person_3, $collection->firstNotIn('has_children', [false, 1]));
        $this->assertNull($collection->firstNotIn('has_children', [false, 1, true]));
    }


    /*
     * Test last
     */
    function testLastWith()
    {
        $person_1 = ['name' => 'name-1', 'surname' => 'family-1', 'has_children' => false];
        $person_2 = ['name' => 'name-2', 'surname' => 'family-3', 'has_children' => true];
        $person_3 = ['name' => 'name-3', 'surname' => 'family-2', 'has_children' => true];
        $person_4 = ['name' => 'name-4', 'surname' => 'family-1', 'has_children' => 1];
        $collection = new Collection([$person_1, $person_2, $person_3, $person_4]);

        $this->assertEquals($person_4, $collection->lastWith('surname', 'family-1'));
        $this->assertEquals($person_3, $collection->lastWith('has_children', true));
        $this->assertEquals($person_4, $collection->lastWith('has_children', true, false));

        $this->assertNull($collection->lastWith('has_children', 'undefined'));
        $this->assertNull($collection->lastWith('undefined', 'undefined'));
    }

    function testLastIn()
    {
        $person_1 = ['name' => 'name-1', 'surname' => 'family-1', 'has_children' => false];
        $person_2 = ['name' => 'name-2', 'surname' => 'family-3', 'has_children' => true];
        $person_3 = ['name' => 'name-3', 'surname' => 'family-2', 'has_children' => true];
        $person_4 = ['name' => 'name-4', 'surname' => 'family-1', 'has_children' => 1];
        $collection = new Collection([$person_1, $person_2, $person_3, $person_4]);

        $this->assertEquals($person_3, $collection->lastIn('surname', ['family-2', 'family-3']));
        $this->assertEquals($person_4, $collection->lastIn('has_children', [true, 1]));
        $this->assertEquals($person_4, $collection->lastIn('has_children', [true], false));

        $this->assertNull($collection->lastIn('has_children', ['undefined']));
    }

    function testLastCallback()
    {
        $person_1 = ['name' => 'name-1', 'surname' => 'family-1', 'has_children' => false];
        $person_2 = ['name' => 'name-2', 'surname' => 'family-1', 'has_children' => 1];
        $person_3 = ['name' => 'name-3', 'surname' => 'family-2', 'has_children' => true];
        $person_4 = ['name' => 'name-4', 'surname' => 'family-3', 'has_children' => true];
        $collection = new Collection(['person-1' => $person_1, 'person-2' => $person_2, 'person-3' => $person_3, 'person-4' => $person_4]);

        $this->assertEquals($person_2, $collection->lastCallback(function ($item, $key) {
            return $item['surname'] == 'family-1';
        }));
        $this->assertNull($collection->lastCallback(function ($item) {
            return $item['name'] === 'undefined';
        }));
    }

    function testLastNotWith()
    {
        $person_1 = ['name' => 'name-1', 'surname' => 'family-1', 'has_children' => false];
        $person_2 = ['name' => 'name-2', 'surname' => 'family-2', 'has_children' => true];
        $person_3 = ['name' => 'name-3', 'surname' => 'family-2', 'has_children' => false];
        $person_4 = ['name' => 'name-4', 'surname' => 'family-1', 'has_children' => 0];
        $collection = new Collection(['person-1' => $person_1, 'person-2' => $person_2, 'person-3' => $person_3, 'person-4' => $person_4]);

        $this->assertEquals($person_3, $collection->lastNotWith('surname', 'family-1'));
        $this->assertEquals($person_2, $collection->lastNotWith('has_children', false, false));
    }

    function testLastNotIn()
    {
        $person_1 = ['name' => 'name-1', 'surname' => 'family-1', 'has_children' => false];
        $person_2 = ['name' => 'name-2', 'surname' => 'family-1', 'has_children' => 1];
        $person_3 = ['name' => 'name-3', 'surname' => 'family-2', 'has_children' => true];
        $person_4 = ['name' => 'name-4', 'surname' => 'family-3', 'has_children' => true];
        $collection = new Collection(['person-1' => $person_1, 'person-2' => $person_2, 'person-3' => $person_3, 'person-4' => $person_4]);

        $this->assertEquals($person_4, $collection->lastNotIn('has_children', [false, 1]));
        $this->assertNull($collection->lastNotIn('has_children', [false, 1, true]));
    }

    /*
     * Test group by
     */
    function testGroupBy()
    {

        $person_1 = ['name' => 'name-1', 'surname' => 'family-1', 'has_children' => true];
        $person_2 = ['name' => 'name-2', 'surname' => 'family-1', 'has_children' => false];
        $person_3 = ['name' => 'name-3', 'surname' => 'family-2', 'has_children' => 1];
        $person_4 = ['name' => 'name-4', 'surname' => 'family-3', 'has_children' => true];
        $collection = new Collection([$person_1, $person_2, $person_3, $person_4]);

        $expected = [
            'family-1' => new Collection([$person_1, $person_2]),
            'family-2' => new Collection([$person_3]),
            'family-3' => new Collection([$person_4])
        ];

        $this->assertEquals($expected, $collection->groupBy('surname'));
    }

    function testGroupCallback()
    {

        $person_1 = ['name' => 'name-1', 'surname' => 'family-1', 'has_children' => true];
        $person_2 = ['name' => 'name-2', 'surname' => 'family-1', 'has_children' => false];
        $person_3 = ['name' => 'name-3', 'surname' => 'family-2', 'has_children' => 1];
        $person_4 = ['name' => 'name-4', 'surname' => 'family-3', 'has_children' => true];
        $collection = new Collection(['person-1' => $person_1, 'person-2' => $person_2, 'person-3' => $person_3, 'person-4' => $person_4]);

        $expected_1 = [
            '1' => new Collection([$person_1, $person_2]),
            '2' => new Collection([$person_3]),
            '3' => new Collection([$person_4])
        ];
        $expected_2 = [
            'person-family-1' => new Collection([$person_1, $person_2]),
            'person-family-2' => new Collection([$person_3]),
            'person-family-3' => new Collection([$person_4])
        ];

        $this->assertEquals($expected_1, $collection->groupCallback(function ($item, $key) {
            return substr($item['surname'], -1);
        }));
        $this->assertEquals($expected_2, $collection->groupCallback(function ($item, $key) {
            return substr($key, 0, 7) . $item['surname'];
        }));
    }

    /*
     * Test sort
     */
    function testSort()
    {
        $person_1 = ['name' => 'name-1', 'surname' => 'family-1', 'has_children' => true];
        $person_2 = ['name' => 'name-2', 'surname' => 'family-1', 'has_children' => false];
        $person_3 = ['name' => 'name-3', 'surname' => 'family-2', 'has_children' => 1];
        $person_4 = ['name' => 'name-4', 'surname' => 'family-3', 'has_children' => true];

        $collection = new Collection(['person3' => $person_3, 'person1' => $person_1, 'person4' => $person_4, 'person2' => $person_2]);

        $expected = ['person1' => $person_1, 'person2' => $person_2, 'person3' => $person_3, 'person4' => $person_4];
        $this->assertEquals($expected, $collection->sortBy('name')->toArray());
        $this->assertEquals($expected, $collection->sortCallback(function ($a, $b) {
            return $a['surname'] > $b['surname'];
        })->toArray());
    }


    /*
     * Test get values
     */
    function testGetValues()
    {
        $person_1 = ['name' => 'name-1', 'surname' => 'family-1', 'has_children' => true];
        $person_2 = ['name' => 'name-2', 'surname' => 'family-1', 'has_children' => false];
        $person_3 = ['name' => 'name-3', 'surname' => 'family-2', 'has_children' => 1];
        $person_4 = ['name' => 'name-4', 'surname' => 'family-3', 'has_children' => true];

        $collection = new Collection([$person_1, $person_2, $person_3, $person_4]);
        $this->assertEquals(['family-1', 'family-1', 'family-2', 'family-3'], $collection->getKeyValues('surname'));
        $this->assertEquals(['family-1', 'family-2', 'family-3'], $collection->getKeyValues('surname', true));
        $this->assertEquals(['FAMILY-1', 'FAMILY-1', 'FAMILY-2', 'FAMILY-3'], $collection->getCallbackValues(function ($item) {
            return strtoupper($item['surname']);
        }));
    }

    /*
     * Test magic
     */
    function testIsset()
    {
        $collection = new Collection();
        $collection->set('item', []);

        $this->assertTrue(isset($collection['item']));
        $this->assertFalse(isset($collection['undefined']));
    }

    function testUnset()
    {
        $collection = new Collection();
        $collection->set('item', []);

        unset($collection['item']);
        $this->assertNull($collection['item']);
    }

    function testSet()
    {
        $collection = new Collection();
        $collection['item'] = [];

        $this->assertEquals([], $collection->get('item'));
    }

    function testGet()
    {
        $collection = new Collection();
        $collection->set('item', []);

        $this->assertEquals([], $collection['item']);
    }
}
