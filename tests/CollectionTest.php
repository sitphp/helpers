<?php

namespace SitPHP\Helpers\Tests;

use BadMethodCallException;
use Doubles\TestCase;
use SebastianBergmann\CodeCoverage\TestFixture\C;
use SitPHP\Helpers\Collection;
use stdClass;

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

    function testGetAddSetObject()
    {
        $item1 = new stdClass();
        $item2 = new stdClass();
        $item1->property = 'property 1';
        $item2->property = 'property 2';
        $collection = new Collection();
        $collection->add($item1);
        $collection->set('item2', $item2);
        $this->assertEquals('property 1', $collection->get(0)->property);
        $this->assertEquals('property 2', $collection->get('item2')->property);
    }

    function testHas(){
        $collection = new Collection();
        $collection->set('name', ['item_1', 'item_2']);

        $this->assertTrue($collection->has('name'));
        $this->assertFalse($collection->has('undefined'));
    }

    function testPrepend(){
        $collection = new Collection();
        $collection->add(['item_1', 'item_2']);
        $collection->prepend(['item_3', 'item_4']);

        $this->assertEquals(['item_3', 'item_4'], $collection->get(0));
    }

    function testGetDeep(){
        $collection = new Collection();
        $obj1 = new stdClass();
        $obj1->property = 'property 1';
        $obj2 = function ($arg) {
            return $arg;
        };
        $collection->set('test', ['item_1' => ['item_1_1' => 'value_1_1'], 'item_2' => [$obj1, $obj2 ]]);

        $this->assertEquals('value_1_1', $collection->get('test.item_1.item_1_1'));
        $this->assertEquals('property 1', $collection->get('test.item_2.0.property'));
        $this->assertEquals('arg', $collection->get('test.item_2.1.arg'));
        $this->assertNull($collection->get('test.item_2.3'));
        $this->assertNull($collection->get('test.item_2.0.undefined'));

    }

    function testGetIn(){
        $collection = new Collection();
        $array = ['item_1', 'item_2'];
        $collection->set('name1', $array);
        $collection->set('name2', $array);
        $collection->set('name3', $array);

        $this->assertEquals(new Collection(['name1' => $array, 'name3' => $array]), $collection->getIn(['name1', 'name3']));
    }

    function testGetNotIn(){
        $collection = new Collection();
        $array = ['item_1', 'item_2'];
        $collection->set('name1', $array);
        $collection->set('name2', $array);
        $collection->set('name3', $array);
        $expected = new Collection(['name1' => $array, 'name3' => $array]);
        $this->assertEquals($expected, $collection->getNotIn(['name2']));
    }

    function testShift(){
        $collection = new Collection();
        $collection->set('name1', ['item_1', 'item_2']);
        $collection->set('name2', ['item_3', 'item_4']);
        $collection->pop();
        $this->assertTrue($collection->has('name1'));
        $this->assertFalse($collection->has('name2'));
    }
    function testPop(){
        $collection = new Collection();
        $collection->set('name1', ['item_1', 'item_2']);
        $collection->set('name2', ['item_3', 'item_4']);
        $collection->shift();
        $this->assertFalse($collection->has('name1'));
        $this->assertTrue($collection->has('name2'));
    }

    function testRemove(){
        $collection = new Collection();
        $collection->set('name1', ['item_1', 'item_2']);
        $collection->set('name2', ['item_3', 'item_4']);
        $collection->remove('name2');
        $this->assertTrue($collection->has('name1'));
        $this->assertFalse($collection->has('name2'));
    }

    function testRemoveCallback(){
        $collection = new Collection();
        $collection->set('name1', ['item_1', 'item_2']);
        $collection->set('name2', ['item_3', 'item_4']);
        $collection->set('name3', ['item_5', 'item_6']);
        $collection->removeCallback(function($item, $name){
            return $name == 'name2' || $item[0] == 'item_5';

        });
        $this->assertTrue($collection->has('name1'));
        $this->assertFalse($collection->has('name2'));
        $this->assertFalse($collection->has('name3'));
    }

    function testSetWithInvalidKeyShouldFail()
    {
        $this->expectException(\InvalidArgumentException::class);
        $collection = new Collection();
        $collection->set(new stdClass(), ['value']);
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
        $collection->set(1, $array_1);
        $collection->set(0, $array_2);
        
        $test = [];
        foreach ($collection as $key => $item) {

            $test[$key] = $item;
        }
        $this->assertEquals([1 => $array_1, 0 =>$array_2], $test);
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

    function testGetNames(){
        $collection = new Collection();
        $array_1 = ['item_1', 'item_2'];
        $array_2 = ['item_3', 'item_4'];
        $collection->set('name1', $array_1);
        $collection->set('name2', $array_2);

        $this->assertEquals(['name1', 'name2'], $collection->getKeys());
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
        $this->assertEquals($person_2,$collection->firstNotIn('has_children', [0], false));
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

        $this->assertEquals($person_4, $collection->lastNotIn('has_children', [0], false));
        $this->assertEquals($person_4, $collection->lastNotIn('has_children', [false, 1]));
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
        $person_5 = ['name' => 'name-4', 'surname' => new stdClass(), 'has_children' => true];
        $collection = new Collection([$person_1, $person_2, $person_3, $person_4, $person_5]);

        $expected = new Collection([
            $person_5,
            'family-1' => new Collection([$person_1, $person_2]),
            'family-2' => new Collection([$person_3]),
            'family-3' => new Collection([$person_4])
        ]);

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
        $this->assertInstanceOf(Collection::class, $collection->sortBy('name'));
        $this->assertEquals($expected, $collection->sortBy('name')->toArray());
        $this->assertEquals(array_reverse($expected, true), $collection->sortBy('name', true)->toArray());
    }

    function testSortCallback(){
        $person_1 = ['name' => 'name-1', 'surname' => 'family-1', 'has_children' => true];
        $person_2 = ['name' => 'name-2', 'surname' => 'family-1', 'has_children' => false];
        $person_3 = ['name' => 'name-3', 'surname' => 'family-2', 'has_children' => 1];
        $person_4 = ['name' => 'name-4', 'surname' => 'family-3', 'has_children' => true];

        $collection = new Collection(['person3' => $person_3, 'person1' => $person_1, 'person4' => $person_4, 'person2' => $person_2]);

        $expected = ['person1' => $person_1, 'person2' => $person_2, 'person3' => $person_3, 'person4' => $person_4];

        $this->assertEquals($expected, $collection->sortCallback(function ($a, $b) {
            return $a['surname'] > $b['surname'];
        })->toArray());
    }

    function testSortWithNull(){
        $priority1 = ['name' => 'name-4'];
        $priority2 = ['name' => null];
        $priority3 = ['name' => null];
        $priority4 = ['name' => 'name-1'];

        $collection = new Collection([$priority1, $priority2, $priority3, $priority4]);

        $this->assertEquals([3 => $priority4, 0 =>$priority1, 1 => $priority2, 2 => $priority3], $collection->sortBy('name')->toArray());
    }

    function testShuffle(){
        $collection = new Collection();
        $array = ['item_1','item_2'];
        $collection->add($array);
        $collection->add($array);
        $collection->add($array);

        $this->assertInstanceOf(Collection::class, $collection->shuffle());
    }

    function testReverse(){
        $collection = new Collection();
        $array = ['item_1','item_2'];
        $collection->add($array);
        $collection->add($array);
        $collection->add($array);

        $this->assertEquals(new Collection([2 => $array,1=> $array,0 => $array]), $collection->reverse());
    }


    /*
     * Test get values
     */
    function testGetKeyValues()
    {
        $person_1 = ['name' => 'name-1', 'surname' => 'family-1', 'has_children' => true];
        $person_2 = ['name' => 'name-2', 'surname' => 'family-1', 'has_children' => false];
        $person_3 = ['name' => 'name-3', 'surname' => 'family-2', 'has_children' => 1];
        $person_4 = ['name' => 'name-4', 'surname' => 'family-3', 'has_children' => true];

        $collection = new Collection(['person-1' => $person_1, 'person-2' => $person_2, 'person-3' => $person_3, 'person-4' => $person_4]);
        $this->assertEquals(new Collection(['person-1' => 'family-1', 'person-2' => 'family-1', 'person-3' => 'family-2', 'person-4' => 'family-3']), $collection->getKeyValues('surname'));
        $this->assertEquals(new Collection(['family-1', 'family-2', 'family-3']), $collection->getKeyValues('surname', true));
    }

    function testGetKeyValuesDeep()
    {
        $array_deep = [['key11' => ['key21' => 'value21']], ['key11' => ['key21' => 'value22']]];
        $collection = new Collection($array_deep);

        $this->assertEquals(new Collection(['value21', 'value22']), $collection->getKeyValues('key11.key21'));
    }

    function testGetCallbackValues(){
        $person_1 = ['name' => 'name-1', 'surname' => 'family-1', 'has_children' => true];
        $person_2 = ['name' => 'name-2', 'surname' => 'family-1', 'has_children' => false];
        $person_3 = ['name' => 'name-3', 'surname' => 'family-2', 'has_children' => 1];
        $person_4 = ['name' => 'name-4', 'surname' => 'family-3', 'has_children' => true];

        $collection = new Collection(['person-1' => $person_1, 'person-2' => $person_2, 'person-3' => $person_3, 'person-4' => $person_4]);
        $this->assertEquals(new Collection(['person-1' => 'family-1', 'person-2' => 'family-1', 'person-3' => 'family-2', 'person-4' => 'family-3']), $collection->getCallbackValues(function($item){
            return $item['surname'];
        }));

        $this->assertEquals(new Collection(['family-1', 'family-2', 'family-3']), $collection->getCallbackValues(function($item){
            return $item['surname'];
        }, true));
    }

    function testMin(){
        $collection = new Collection();
        $collection->add(['value' => 0]);
        $collection->add(['value' => 6]);
        $collection->add(['value' => 9]);

        $this->assertEquals(0, $collection->min('value'));
        $this->assertNull($collection->min('undefined'));
        $this->assertNull((new Collection())->min('undefined'));
    }

    function testMax(){
        $collection = new Collection();
        $collection->add(['value' => 0]);
        $collection->add(['value' => 6]);
        $collection->add(['value' => 9]);

        $this->assertEquals(9, $collection->max('value'));
        $this->assertNull($collection->max('undefined'));
        $this->assertNull((new Collection())->max('undefined'));
    }

    function testAverage(){
        $collection = new Collection();
        $collection->add(['value' => 0]);
        $collection->add(['value' => 6]);
        $collection->add(['value' => 9]);
        $collection->add(['value' => new stdClass()]);

        $this->assertEquals(5, $collection->average('value'));
        $this->assertNull($collection->average('undefined'));
        $this->assertNull((new Collection())->average('undefined'));
    }

    function testReduce(){
        $collection = new Collection();
        $collection->add(['value' => 0]);
        $collection->add(['value' => 6]);
        $collection->add(['value' => 9]);

        $this->assertEquals(15, $collection->reduce(function($carry, $item){
            $carry += $item['value'];
            return $carry;
        }));
    }

    /*
     * Test sub collection
     */
    function testRandom(){
        $collection = new Collection();
        $array = ['item_1','item_2'];
        $collection->add($array);
        $collection->add($array);
        $collection->add($array);

        $this->assertIsArray($collection->random(1));
        $this->assertInstanceOf(Collection::class,$collection->random(2));

    }
    function testChunk(){
        $collection = new Collection();
        $array = ['item_1','item_2'];
        $collection->add($array);
        $collection->add($array);
        $collection->add($array);

        $this->assertEquals([new Collection([0 => $array, 1=> $array]),new Collection([2=> $array])], $collection->chunk(2));
    }

    function testSplice(){
        $collection = new Collection();
        $array = ['item_1','item_2'];
        $collection->add($array);
        $collection->add($array);
        $collection->add($array);

        $this->assertEquals(new Collection([$array]), $collection->splice(1, 2));
    }
    function testPaginate(){
        $collection = new Collection();
        $array = ['item_1','item_2'];
        $collection->add($array);
        $collection->add($array);
        $collection->add($array);

        $this->assertEquals(new Collection([0 => $array, 1=> $array]), $collection->paginate(1, 2));
        $this->assertEquals(new Collection([2 => $array]), $collection->paginate(2, 2));
    }
    function testMap(){
        $collection = new Collection();
        $array = ['item_1','item_2'];
        $array_sub = ['ITEM_1','item_2'];
        $collection->add($array);
        $collection->add($array);
        $collection->add($array);

        $this->assertEquals(new Collection([$array_sub, $array_sub ,$array_sub]),
            $collection->map(function($item){
                $item[0] = strtoupper($item[0]);
            return $item;
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

    /*
     * Test extend
     */

    function testExtend()
    {
        $collection = new Collection();
        $collection->extend('toUpper', function ($test) {
            $array = [];
            foreach ($this->items as $item){
                $array[] = strtoupper($item).'_'.$test;
            }
            return $array;
        });
        $collection->add('item_1');
        $collection->add('item_2');
        $this->assertEquals(['ITEM_1_test', 'ITEM_2_test'], $collection->toUpper('test'));
    }

    function testExtendWithUndefinedShouldFail()
    {
        $this->expectException(BadMethodCallException::class);
        $collection = new Collection();
        $this->assertEquals(['ITEM_1', 'ITEM_2'], $collection->toUpper());
    }
}
