<?php

namespace SitPHP\Helpers;

use ArrayAccess;
use Countable;
use InvalidArgumentException;
use Iterator;

class Collection implements Iterator, ArrayAccess, Countable
{

    protected $items = [];


    /**
     * Count items
     *
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * Check if item exists
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->get($offset) !== null;
    }

    /**
     * Ge item
     *
     * @param mixed $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Set item
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Remove item
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    /**
     * Rewind
     */
    public function rewind()
    {
        reset($this->items);
    }

    /**
     * Return current item
     *
     * @return mixed|null
     */
    public function current()
    {
        return current($this->items);
    }

    /**
     * Return current position
     *
     * @return mixed
     */
    public function key()
    {
        return key($this->items);
    }

    /**
     *  Increment position
     */
    public function next()
    {
        next($this->items);
    }

    /**
     * Check if item exists
     *
     * @return bool
     */
    public function valid()
    {
        return key($this->items) !== null;
    }

    /**
     * Collection constructor.
     *
     * @param array $items
     */
    function __construct(array $items = [])
    {
        foreach ($items as $key => $value){
            $this->set($key, $value);
        }
    }

    /*
     * Get/Set methods
     */

    /**
     * Add item at the end of the collection
     *
     * @param $item
     * @return Collection
     */
    function add($item)
    {
        $this->validateItem($item);
        $this->items[] = $item;
        return $this;
    }

    /**
     * Add item at the beginning of the collection
     *
     * @param $item
     * @return Collection
     */
    function prepend($item){
        $this->validateItem($item);
        array_unshift($this->items, $item);
        return $this;
    }

    /**
     * Set item with custom name
     *
     * @param string $name
     * @param $item
     * @return Collection|void
     */
    function set($name, $item)
    {
        $this->validateItem($item);

        if(!isset($name)){
            $this->add($item);
            return;
        }
        if(!is_string($name) && !is_int($name)){
            throw new InvalidArgumentException('Invalid $key item argument : expected string, int or null');
        }
        $this->items[$name] = $item;
        return $this;
    }

    /**
     * Return item matching given name
     *
     * @param string $name
     * @return mixed|null
     */
    function get(string $name)
    {
        return $this->getItemValue($this->items, $name);
    }

    /**
     * Return collection of items matching given names
     *
     * @param array $names
     * @return Collection
     */
    function getIn(array $names){
        $items = new static();
        foreach ($names as $key){
            $items[$key] = $this->getItemValue($this->items, $key);
        }
        return $items;
    }

    /**
     * Return array of names of items
     *
     * @return array
     */
    function getNames(){
        return array_keys($this->items);
    }

    /**
     * Return collection of items not matching given names
     *
     * @param array $names
     * @return Collection
     */
    function getNotIn(array $names){
        $items = new static();
        foreach ($this->items as $key => $item) {
            if(!in_array($key, $names, true)){
                $items[$key] = $this->getItemValue($this->items, $key);
            }
        }
        return $items;
    }


    /**
     * Shift an element off the beginning of the collection
     *
     * @return $this
     * @see https://www.php.net/manual/function.array-shift.php
     */
    function shift(){
        array_shift($this->items);
        return $this;
    }

    /**
     * Pop an element off the end of the collection
     *
     * @return $this
     * @see https://www.php.net/manual/function.array-pop.php
     */
    function pop(){
        array_pop($this->items);
        return $this;
    }

    /**
     * Remove item(s) with give name(s)
     *
     * @param $names
     * @return Collection
     */
    function remove($names){
        foreach ((array) $names as $name){
            unset($this->items[$name]);
        }
        return $this;
    }

    /**
     * Remove items when callback returns true
     *
     * @param callable $callback
     * @return Collection
     */
    function removeCallback(callable $callback){
        foreach($this->items as $name => $item){
            if(call_user_func_array($callback, [$item, $name])){
                unset($this->items[$name]);
            }
        }
        return $this;
    }

    /**
     * Check if item with name exists
     *
     * @param string $name
     * @return bool
     */
    function has(string $name){
        return isset($this->items[$name]);
    }

    /**
     * Return all items
     *
     * @return array
     */
    function toArray(){
        return $this->items;
    }


    /*
     * Key values methods
     */
    /**
     * Return array of values of the given key
     *
     * @param string $key
     * @param bool $distinct
     * @return array
     */
    function getKeyValues(string $key, bool $distinct = false)
    {
        $values = [];
        foreach ($this->items as $item_key => $item) {
            $values[$item_key] = $this->getItemValue($item, $key);
        }
        if ($distinct) {
            $values = array_values(array_unique($values));
        }
        return $values;
    }

    /**
     * Return array of values returned by callback
     *
     * @param callable $call
     * @param bool $distinct
     * @return Collection
     */
    function getCallbackValues(callable $call, bool $distinct = false){
        $values = [];
        foreach ($this->items as $key => $item) {
            $values[$key] =  call_user_func_array($call,[$item, $key]);
        }
        if ($distinct) {
            $values = array_values(array_unique($values));
        }
        return $values;
    }

    /**
     * Check if item with value exists
     *
     * @param $key
     * @param null $value
     * @param bool $strict
     * @return bool
     */
    function hasKeyValue(string $key, $value, $strict = false)
    {
        return $this->firstWith($key, $value, $strict) ? true : false;
    }


    /**
     * Return min value of given key
     *
     * @param string $key
     * @return mixed|null
     */
    function min(string $key){
        if(empty($this->items)){
            return null;
        }
        return min($this->getKeyValues($key));
    }

    /**
     * Return min value of given key
     *
     * @param string $key
     * @return mixed
     */
    function max(string $key){
        if(empty($this->items)){
            return null;
        }
        return max($this->getKeyValues($key));
    }

    /**
     * Return §rage of key values
     *
     * @param string $key
     * @return mixed
     */
    function average(string $key){
        if(empty($this->items)){
            return null;
        }
        $key_values = array_filter($this->getKeyValues($key), function ($item){
            return $item !== null;
        });

        if(empty($key_values)){
            return null;
        }
        return array_sum($key_values)/$this->count();

    }

    /*
     * Collection methods
     */

    /**
     * Return one or more random items
     *
     * @param int $num
     * @return mixed
     */
    function random(int $num){
        $rand_keys = array_rand($this->items, $num);
        if($num === 1){
            return $this->items[$rand_keys];
        }
        $rand_items = new static();
        foreach($rand_keys as $rand_key){
            $rand_items[$rand_key] = $this->items[$rand_key];
        }
        return $rand_items;
    }


    /**
     * Return array of collections of given size
     *
     * @param int $size
     * @param bool $preserve_keys
     * @return array
     */
    function chunk(int $size , bool $preserve_keys = true){
        $chunks = [];
        foreach($array_chunks = array_chunk($this->items, $size, $preserve_keys) as $array_chunk){
            $chunks[] = new static($array_chunk);
        }
        return $chunks;
    }

    /**
     * Remove a portion of the array
     *
     * @param $offset
     * @param int|null $length
     * @return Collection
     */
    function splice($offset, int $length = null){
        $items = $this->items;
        array_splice($items,$offset, $length);
        return new static($items);
    }

    /**
     *  Return a collection applying the callback to the items
     *
     * @param callable $callback
     * @return Collection
     * @see https://www.php.net/manual/function.array-map.php
     */
    function map(callable $callback){
        return new static(array_map($callback, $this->items));
    }

    /**
     * Return a collection of items on given page
     *
     * @param int $page
     * @param int $items_per_page
     * @return $this|null
     */
    function paginate(int $page, int $items_per_page){
        $chunks = $this->chunk($items_per_page);
        return $chunks[$page - 1] ?? null;
    }

    /**
     * Iteratively reduce items to single value using a callback function
     *
     * @param string $key
     * @param callable $callback
     * @return mixed
     * @see https://www.php.net/manual/function.array-reduce.php
     */
    function reduce(callable $callback){
        return array_reduce($this->items, $callback);
    }

    /*
     * First methods
     */

    /**
     * Return first item matching key value
     *
     * @param string $key
     * @param $value
     * @param bool $strict
     * @return bool|mixed
     */
    function firstWith(string $key, $value, $strict = true)
    {
        if ($strict) {
            foreach ($this->items as $item) {
                if ($this->getItemValue($item, $key) === $value) {
                    return $item;
                }
            }
        } else {
            foreach ($this->items as $item) {
                if ($this->getItemValue($item, $key) == $value) {
                    return $item;
                }
            }
        }
        return null;
    }

    /**
     * Return first item matching any key of values
     *
     * @param string $key
     * @param array $values
     * @param bool $strict
     * @return bool|mixed
     */
    function firstIn(string $key, array $values, $strict = true)
    {
        foreach ($this->items as $item) {
            if (in_array($this->getItemValue($item, $key), $values, $strict)) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Return first item not matching key value
     *
     * @param string $key
     * @param $value
     * @param bool $strict
     * @return bool|mixed
     */
    function firstNotWith(string $key, $value, $strict = true)
    {
        if ($strict) {
            foreach ($this->items as $item) {
                if ($this->getItemValue($item, $key) !== $value) {
                    return $item;
                }
            }
        } else {
            foreach ($this->items as $item) {
                if ($this->getItemValue($item, $key) != $value) {
                    return $item;
                }
            }
        }
        return null;
    }

    /**
     *  Return first item not matching any key of values
     *
     * @param string $key
     * @param array $values
     * @param bool $strict
     * @return mixed|null
     */
    function firstNotIn(string $key, array $values, $strict = true){
        foreach ($this->items as $item) {
            if (!in_array($this->getItemValue($item, $key), $values, $strict)) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Return first item matching callback
     *
     * @param callable $callback
     * @return bool|mixed
     */
    function firstCallback(callable $callback)
    {
        foreach ($this->items as $key => $item) {
            if (call_user_func_array($callback, [$item, $key])) {
                return $item;
            }
        }
        return null;
    }

    /*
     * Last methods
     */

    /**
     * Return last item matching key value
     *
     * @param string $key
     * @param $value
     * @param bool $strict
     * @return bool|mixed
     */
    function lastWith(string $key, $value, $strict = true)
    {
        $found = null;
        if ($strict) {
            foreach ($this->items as $item) {
                if ($this->getItemValue($item, $key) === $value) {
                    $found = $item;
                }
            }
        } else {
            foreach ($this->items as $item) {
                if ($this->getItemValue($item, $key) == $value) {
                    $found = $item;
                }
            }
        }
        return $found;
    }

    /**
     * Return last item matching any key of values
     *
     * @param string $key
     * @param array $values
     * @param bool $strict
     * @return bool|mixed
     */
    function lastIn(string $key, array $values, $strict = true)
    {
        $found = null;
        foreach ($this->items as $item) {
            if (in_array($this->getItemValue($item, $key), $values, $strict)) {
                $found = $item;
            }
        }
        return $found;
    }

    /**
     * Return last item not matching key value
     *
     * @param string $key
     * @param $value
     * @param bool $strict
     * @return bool|mixed
     */
    function lastNotWith(string $key, $value, $strict = true)
    {
        $found = null;
        if ($strict) {
            foreach ($this->items as $item) {
                if ($this->getItemValue($item, $key) !== $value) {
                    $found = $item;
                }
            }
        } else {
            foreach ($this->items as $item) {
                if ($this->getItemValue($item, $key) != $value) {
                    $found = $item;
                }
            }
        }
        return $found;
    }

    /**
     *  Return last item not matching any key of values
     *
     * @param string $key
     * @param array $values
     * @param bool $strict
     * @return mixed|null
     */
    function lastNotIn(string $key, array $values, $strict = true){
        $found = null;
        foreach ($this->items as $item) {
            if (!in_array($this->getItemValue($item, $key), $values, $strict)) {
                $found = $item;
            }
        }
        return $found;
    }

    /**
     * Return last item matching callback
     *
     * @param callable $callback
     * @return bool|mixed
     */
    function lastCallback(callable $callback)
    {
        $found = null;
        foreach ($this->items as $key => $item) {
            if (call_user_func_array($callback, [$item, $key])) {
                $found = $item;
            }
        }
        return $found;
    }

    /*
     * Filter methods
     */

    /**
     * Return collection of items matching key value
     *
     * @param $key
     * @param $value
     * @param bool $strict
     * @return Collection
     */
    function filterBy(string $key, $value, bool $strict = true)
    {
        $found = new static();
        if($strict){
            foreach ($this->items as $item) {
                if ($this->getItemValue($item, $key) === $value) {
                    $found->add($item);
                }
            }
        } else {
            foreach ($this->items as $item) {
                if ($this->getItemValue($item, $key) == $value) {
                    $found->add($item);
                }
            }
        }
        return $found;
    }

    /**
     * Return collection of items not matching key value
     *
     * @param $key
     * @param $value
     * @param bool $strict
     * @return Collection
     */
    function filterNotBy($key, $value, bool $strict = true){
        $found = new static();
        if($strict){
            foreach ($this->items as $item) {
                if ($this->getItemValue($item, $key) !== $value) {
                    $found->add($item);
                }
            }
        } else {
            foreach ($this->items as $item) {
                if ($this->getItemValue($item, $key) != $value) {
                    $found->add($item);
                }
            }
        }
        return $found;
    }

    /**
     * Return collection of items matching any of key values
     *
     * @param string $key
     * @param array $values
     * @param bool $strict
     * @return Collection
     */
    function filterIn(string $key, array $values, bool $strict = true)
    {
        $found = new static();
        foreach ($this->items as $item) {
            if (in_array($this->getItemValue($item, $key) ,$values, $strict)) {
                $found->add($item);
            }
        }
        return $found;
    }


    /**
     * Return collection of items not matching any of key values
     *
     * @param string $key
     * @param array $values
     * @param bool $strict
     * @return Collection
     */
    function filterNotIn(string $key, array $values, bool $strict = true)
    {
        $found = new static();
        foreach ($this->items as $item) {
            if (!in_array($this->getItemValue($item, $key) ,$values, $strict)) {
                $found->add($item);
            }
        }
        return $found;
    }

    /**
     * Return collection of items matching callback
     *
     * @param callable $callback
     * @return Collection
     */
    function filterCallback(callable $callback)
    {
        $found = new static();
        foreach ($this->items as $key => $item) {
            if (call_user_func_array($callback, [$item, $key])) {
                $found->add($item);
            }
        }
        return $found;
    }

    /*
     * Group methods
     */

    /**
     * Return
     *
     * @param $key
     * @return array
     */
    function groupBy(string $key)
    {
        $array = [];
        foreach ($this->items as $item) {
            if (!isset($array[$this->getItemValue($item, $key)])) {
                $array[$this->getItemValue($item, $key)] = new static();
            }
            $array[$this->getItemValue($item, $key)]->add($item);
        }
        return $array;
    }

    /**
     * @param callable $call
     * @return array
     */
    function groupCallback(callable $call){
        $array = [];
        foreach ($this->items as $key => $item) {
            $real_label = call_user_func_array($call, [$item, $key]);
            if (!isset($array[$real_label])) {
                $array[$real_label] = new static();
            }
            $array[$real_label]->add($item);
        }
        return $array;
    }

    /*
     * Sort methods
     */

    /**
     * Sort collection by key
     *
     * @param string $key
     * @param bool $reverse
     * @return Collection
     */
    function sortBy(string $key, bool $reverse = false){
        $items = $this->items;
        uasort($items, function($a, $b) use($key, $reverse){
            if ($a[$key] === $b[$key]) {
                return 0;
            }
            if($a[$key] === null){
                return 1;
            }
            if($b[$key] === null){
                return -1;
            }
            if(!$reverse){
                return ($a[$key] < $b[$key]) ? -1 : 1;
            }
            return ($a[$key] > $b[$key]) ? -1 : 1;
        });
        return new static($items);
    }

    /**
     * User sort collection
     *
     * @param callable $callback
     * @return Collection
     */
    function sortCallback(callable $callback){
        $items = $this->items;
        uasort($items, $callback);
        return new static($items);
    }

    /**
     * Reverse the order of collection items
     *
     * @param bool $preserve_keys
     * @return Collection
     */
    function reverse(bool $preserve_keys = true){
        return new static(array_reverse($this->items, $preserve_keys));
    }

    /**
     * Shuffle the collection
     *
     * @return Collection
     */
    function shuffle(){
        $items = $this->items;
        shuffle($items);
        return new static($items);
    }


    /**
     * Return item value
     *
     * @param $item
     * @param string $key
     * @return mixed|null
     */
    protected function getItemValue($item, string $key){
        $value = null;
        $key_parts = explode('.', $key);
        foreach ($key_parts as $key_part) {
            if (!isset($item[$key_part])) {
                return null;
            }
            $value = $item[$key_part];
            $item = $item[$key_part];
        }
        return $value;
    }

    protected function validateItem($item){
        if(!is_array($item) && !is_a($item, ArrayAccess::class)){
            throw new InvalidArgumentException('Invalid $item argument : expected array or instance of '. ArrayAccess::class);
        }
    }

}