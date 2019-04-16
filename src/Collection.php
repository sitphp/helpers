<?php

namespace SitPHP\Helpers;

use ArrayAccess;
use Countable;
use InvalidArgumentException;
use Iterator;

class Collection implements Iterator, ArrayAccess, Countable
{

    protected $items = [];
    protected $position;


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
        $this->position = 0;
    }

    /**
     * Return current item
     *
     * @return mixed|null
     */
    public function current()
    {
        return $this->get($this->position);
    }

    /**
     * Return current position
     *
     * @return mixed
     */
    public function key()
    {
        return $this->position;
    }

    /**
     *  Increment position
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * Check if item exists
     *
     * @return bool
     */
    public function valid()
    {
        return $this->get($this->position) !== null;
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

    /**
     * Add item
     *
     * @param $item
     */
    function add($item)
    {
        $this->validateItem($item);

        $this->items[] = $item;
    }

    /**
     * Set item with custom key
     *
     * @param string $key
     * @param $item
     */
    function set($key, $item)
    {
        $this->validateItem($item);

        if(!isset($key)){
            $this->add($item);
            return;
        }
        if(!is_string($key) && !is_int($key)){
            throw new InvalidArgumentException('Invalid $key item argument : expected string, int or null');
        }
        $this->items[$key] = $item;
    }

    /**
     * Return item
     *
     * @param string $key
     * @return mixed|null
     */
    function get(string $key)
    {
        return $this->getItemValue($this->items, $key);
    }


    /**
     * Return all items
     *
     * @return array
     */
    function toArray(){
        return $this->items;
    }

    /**
     * Remove item
     *
     * @param $name
     */
    function remove($name){
        unset($this->items[$name]);
    }

    /**
     * Return array of items key values
     *
     * @param string $key
     * @param bool $distinct
     * @return Collection
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
     * Return array of items mapp
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
    function hasKeyValue($key, $value, $strict = false)
    {
        return $this->firstWith($key, $value, $strict) ? true : false;
    }

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
        $found = new self();
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
     * Return collection of items matching any of key values
     *
     * @param string $key
     * @param array $values
     * @param bool $strict
     * @return Collection
     */
    function filterIn(string $key, array $values, bool $strict = true)
    {
        $found = new self();
        foreach ($this->items as $item) {
            if (in_array($this->getItemValue($item, $key) ,$values, $strict)) {
                $found->add($item);
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
        $found = new self();
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
     * Return collection of items not matching any of key values
     *
     * @param string $key
     * @param array $values
     * @param bool $strict
     * @return Collection
     */
    function filterNotIn(string $key, array $values, bool $strict = true)
    {
        $found = new self();
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
        $found = new self();
        foreach ($this->items as $key => $item) {
            if (call_user_func_array($callback, [$item, $key])) {
                $found->add($item);
            }
        }
        return $found;
    }


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
                $array[$this->getItemValue($item, $key)] = new self();
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
                $array[$real_label] = new self();
            }
            $array[$real_label]->add($item);
        }
        return $array;
    }

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
        return new self($items);
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
        return new self($items);
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