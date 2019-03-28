<?php

namespace SitPHP\Helpers\Tests;

use SitPHP\Helpers\Collection;

class CollectionTest extends \Doublit\TestCase
{
    /*
     * Test basics
     */
    function testGetAddSet(){
        $collection = new Collection();
        $array = ['item_1', 'item_2'];
        $collection->add($array);
        $collection->set('name', $array);
        $this->assertEquals($array, $collection->get(0));
        $this->assertEquals($array, $collection->get('name'));
    }
    function testAddWithInvalidItemShouldFail(){
        $this->expectException(\InvalidArgumentException::class);
        $collection = new Collection();
        $collection->add(new \stdClass());
    }
    function testSetWithInvalidKeyShouldFail(){
        $this->expectException(\InvalidArgumentException::class);
        $collection = new Collection();
        $collection->set(new \stdClass(), ['value']);
    }
    function testSetWithInvalidItemShouldFail(){
        $this->expectException(\InvalidArgumentException::class);
        $collection = new Collection();
        $collection->set('item', new \stdClass());
    }
    function testCollectionShouldBehaveAsArray(){
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
        $this->assertEquals([0=>$array_2], current($collection));
    }
    function testCollectionShouldBeIterable(){
        $collection = new Collection();
        $array_1 = ['item_1', 'item_2'];
        $array_2 = ['item_3', 'item_4'];
        $collection->add($array_1);
        $collection->add($array_2);

        $test = [];
        foreach($collection as $key => $item){
            $test[$key] = $item;
        }
        $this->assertEquals([$array_1, $array_2], $test);
    }
    function testCollectionShouldBeCountable(){
        $collection = new Collection();
        $array_1 = ['item_1', 'item_2'];
        $array_2 = ['item_3', 'item_4'];
        $collection->add($array_1);
        $collection->add($array_2);

        $this->assertEquals(2, count($collection));
    }

    function testHasValue(){
        $array_1 = ['item_1', 'item_2'];
        $array_2 = ['item_3', 'item_4'];
        $collection = new Collection([$array_1, $array_2]);

        $this->assertTrue($collection->hasKeyValue(0, 'item_1'));
        $this->assertFalse($collection->hasKeyValue(0, 'item_2'));
    }

    /*
     * Test filter
     */
    function testFilter(){
        $person_1 = ['name' => 'name-1', 'surname' => 'family-1', 'has_children' => true];
        $person_2 = ['name' => 'name-2', 'surname' => 'family-1', 'has_children' => false];
        $person_3 = ['name' => 'name-3', 'surname' => 'family-2', 'has_children' => 1];
        $person_4 = ['name' => 'name-4', 'surname' => 'family-3', 'has_children' => true];
        $collection = new Collection([$person_1, $person_2, $person_3, $person_4]);

        $filtered_on = $collection->filterOn('surname', 'family-1');
        $filtered_in = $collection->filterIn('surname', ['family-1', 'family-2']);
        $filtered_callback = $collection->filterCallback(function ($item){
            return $item['name'] === 'name-1' || $item['name'] === 'name-2';
        });
        $filtered_on_strict = $collection->filterOn('has_children', true);
        $filtered_on_not_strict = $collection->filterOn('has_children', true, false);
        $filtered_in_not_strict = $collection->filterIn('has_children', [true], false);

        $this->assertEquals([$person_1, $person_2], $filtered_on->toArray());
        $this->assertEquals([$person_1, $person_2, $person_3], $filtered_in->toArray());
        $this->assertEquals([$person_1, $person_2], $filtered_callback->toArray());
        $this->assertEquals([$person_1, $person_4], $filtered_on_strict->toArray());
        $this->assertEquals([$person_1, $person_3 ,$person_4], $filtered_on_not_strict->toArray());
        $this->assertEquals([$person_1, $person_3 ,$person_4], $filtered_in_not_strict->toArray());
    }

    /*
     * Test find
     */
    function testFindOn(){
        $person_1 = ['name' => 'name-1', 'surname' => 'family-1', 'has_children' => false];
        $person_2 = ['name' => 'name-2', 'surname' => 'family-1', 'has_children' => 1];
        $person_3 = ['name' => 'name-3', 'surname' => 'family-2', 'has_children' => true];
        $person_4 = ['name' => 'name-4', 'surname' => 'family-3', 'has_children' => true];
        $collection = new Collection([$person_1, $person_2, $person_3, $person_4]);

        $this->assertEquals($person_2, $collection->findOn('name', 'name-2'));
        $this->assertEquals($person_2, $collection->findOn('has_children', true, false));
        $this->assertEquals($person_3, $collection->findOn('has_children', true));
        $this->assertNull($collection->findOn('has_children', 'undefined'));
        $this->assertNull($collection->findOn('undefined', 'undefined'));
    }
    function testFindCallback(){
        $person_1 = ['name' => 'name-1', 'surname' => 'family-1', 'has_children' => false];
        $person_2 = ['name' => 'name-2', 'surname' => 'family-1', 'has_children' => 1];
        $person_3 = ['name' => 'name-3', 'surname' => 'family-2', 'has_children' => true];
        $person_4 = ['name' => 'name-4', 'surname' => 'family-3', 'has_children' => true];
        $collection = new Collection(['person-1' => $person_1, 'person-2' => $person_2, 'person-3' => $person_3, 'person-4' => $person_4]);

        $this->assertEquals($person_2, $collection->findCallback(function($item, $key){
            return $key === 'person-2';
        }));
        $this->assertEquals($person_4, $collection->findCallback(function($item, $key){
            return $item['name'] === 'name-4';
        }));
        $this->assertNull($collection->findCallback(function($item){
            return $item['name'] === 'undefined';
        }));
    }

    /*
     * Test group by
     */
    function testGroupBy(){

        $person_1 = ['name' => 'name-1', 'surname' => 'family-1', 'has_children' => true];
        $person_2 = ['name' => 'name-2', 'surname' => 'family-1', 'has_children' => false];
        $person_3 = ['name' => 'name-3', 'surname' => 'family-2', 'has_children' => 1];
        $person_4 = ['name' => 'name-4', 'surname' => 'family-3', 'has_children' => true];
        $collection = new Collection([$person_1, $person_2, $person_3, $person_4]);

        $expected = [
            'family-1' => new Collection([$person_1, $person_2]),
            'family-2' => new Collection([$person_3]),
            'family-3' =>  new Collection([$person_4])
        ];

        $this->assertEquals($expected, $collection->groupBy('surname'));
    }

    function testGroupCallback(){

        $person_1 = ['name' => 'name-1', 'surname' => 'family-1', 'has_children' => true];
        $person_2 = ['name' => 'name-2', 'surname' => 'family-1', 'has_children' => false];
        $person_3 = ['name' => 'name-3', 'surname' => 'family-2', 'has_children' => 1];
        $person_4 = ['name' => 'name-4', 'surname' => 'family-3', 'has_children' => true];
        $collection = new Collection(['person-1'=>$person_1, 'person-2'=>$person_2, 'person-3'=>$person_3, 'person-4'=>$person_4]);

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

        $this->assertEquals($expected_1, $collection->groupCallback(function ($item, $key){
            return substr($item['surname'], -1);
        }));
        $this->assertEquals($expected_2, $collection->groupCallback(function ($item, $key){
            return substr($key,0, 7).$item['surname'];
        }));
    }

    /*
     * Test sort
     */
    function testSort(){
        $person_1 = ['name' => 'name-1', 'surname' => 'family-1', 'has_children' => true];
        $person_2 = ['name' => 'name-2', 'surname' => 'family-1', 'has_children' => false];
        $person_3 = ['name' => 'name-3', 'surname' => 'family-2', 'has_children' => 1];
        $person_4 = ['name' => 'name-4', 'surname' => 'family-3', 'has_children' => true];

        $collection = new Collection(['person3' => $person_3, 'person1' => $person_1, 'person4' => $person_4, 'person2' => $person_2]);

        $expected = ['person1' => $person_1, 'person2' => $person_2, 'person3' => $person_3, 'person4' => $person_4];
        $this->assertEquals($expected, $collection->sortBy('name')->toArray());
        $this->assertEquals($expected, $collection->sortCallback(function($a, $b){
            return $a['surname'] > $b['surname'];
        })->toArray());
    }


    /*
     * Test get values
     */
    function testGetValues(){
        $person_1 = ['name' => 'name-1', 'surname' => 'family-1', 'has_children' => true];
        $person_2 = ['name' => 'name-2', 'surname' => 'family-1', 'has_children' => false];
        $person_3 = ['name' => 'name-3', 'surname' => 'family-2', 'has_children' => 1];
        $person_4 = ['name' => 'name-4', 'surname' => 'family-3', 'has_children' => true];

        $collection = new Collection([$person_1, $person_2, $person_3, $person_4]);
        $this->assertEquals(['family-1', 'family-1', 'family-2', 'family-3'] ,$collection->getValuesByKey('surname'));
        $this->assertEquals(['family-1', 'family-2', 'family-3'] ,$collection->getValuesByKey('surname', true));
        $this->assertEquals(['FAMILY-1', 'FAMILY-1', 'FAMILY-2', 'FAMILY-3'] ,$collection->getValuesByCallback(function ($item){
            return strtoupper($item['surname']);
        }));
    }
}
