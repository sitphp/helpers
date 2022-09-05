<?php

namespace SitPHP\Helpers;

use ArrayAccess;
use BadMethodCallException;
use Closure;
use Countable;
use InvalidArgumentException;
use Iterator;

class Collection implements Iterator, ArrayAccess, Countable
{

    protected $items = [];
    protected $extensions = [];


    /**
     * Count items
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Check if item exists
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
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
        if ($offset === null) {
            $this->add($value);
        } else {
            $this->set($offset, $value);
        }
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
    public function valid(): bool
    {
        return key($this->items) !== null;
    }

    function __call($name, array $arguments)
    {
        if (!array_key_exists($name, $this->extensions)) {
            throw new BadMethodCallException('Undefined "' . $name . '" method');
        }
        $extension = $this->extensions[$name];
        return call_user_func_array($extension, $arguments);
    }


    /**
     * Collection constructor.
     *
     * @param array|null $items
     */
    function __construct(array $items = null)
    {
        if ($items === null) {
            return;
        }
        foreach ($items as $key => $value) {
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
    function add($item): Collection
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * Add item at the beginning of the collection
     *
     * @param $item
     * @return Collection
     */
    function prepend($item): Collection
    {
        array_unshift($this->items, $item);
        return $this;
    }

    /**
     * Set item with custom key
     *
     * @param string|int $key
     * @param $item
     * @return Collection
     */
    function set($key, $item): Collection
    {
        if (!is_string($key) && !is_int($key)) {
            throw new InvalidArgumentException('Invalid $key item argument : expected string, int or null');
        }
        $this->items[$key] = $item;
        return $this;
    }

    /**
     * Return item matching given key
     *
     * @param string $key
     * @return mixed|null
     */
    function get(string $key)
    {
        return $this->getItemValue($this->items, $key);
    }

    /**
     * Return collection of items matching given keys
     *
     * @param array $keys
     * @return Collection
     */
    function getIn(array $keys): Collection
    {
        $items = $this->makeSibling();
        foreach ($keys as $key) {
            $item_value = $this->getItemValue($this->items, $key);
            $items->set($key, $item_value);
        }
        return $items;
    }

    /**
     * Return array of names of items
     *
     * @return array
     */
    function getKeys(): array
    {
        return array_keys($this->items);
    }

    /**
     * Return collection of items not matching given keys
     *
     * @param array $keys
     * @param bool $preserve_keys
     * @return Collection
     */
    function getNotIn(array $keys, bool $preserve_keys = true): Collection
    {
        $items = $this->makeSibling();
        foreach ($this->items as $key => $item) {
            if (!in_array($key, $keys, true)) {
                $items->addItem($key, $this->getItemValue($this->items, $key), $preserve_keys);
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
    function shift(): Collection
    {
        array_shift($this->items);
        return $this;
    }

    /**
     * Pop an element off the end of the collection
     *
     * @return $this
     * @see https://www.php.net/manual/function.array-pop.php
     */
    function pop(): Collection
    {
        array_pop($this->items);
        return $this;
    }

    /**
     * Remove item(s) with give name(s)
     *
     * @param array|string $keys
     * @return Collection
     */
    function remove($keys): Collection
    {
        if (is_string($keys)) {
            $keys = [$keys];
        }
        foreach ($keys as $key) {
            unset($this->items[$key]);
        }
        return $this;
    }

    /**
     * Remove items when callback returns true
     *
     * @param callable $callback
     * @return Collection
     */
    function removeCallback(callable $callback): Collection
    {
        foreach ($this->items as $name => $item) {
            if (call_user_func_array($callback, [$item, $name])) {
                unset($this->items[$name]);
            }
        }
        return $this;
    }

    /**
     * Check if item with key exists
     *
     * @param string $key
     * @return bool
     */
    function has(string $key): bool
    {
        return isset($this->items[$key]);
    }

    /**
     * Return all items
     *
     * @return array
     */
    function toArray(): array
    {
        return $this->items;
    }


    /*
     * Key values methods
     */
    /**
     * Return collection of values of the given key
     *
     * @param string $key
     * @param bool $distinct
     * @return Collection
     */
    function getKeyValues(string $key, bool $distinct = false): Collection
    {
        $values = [];
        foreach ($this->items as $item_key => $item) {
            $values[$item_key] = $this->getItemValue($item, $key);
        }
        if ($distinct) {
            $values = array_values(array_unique($values));
        }
        return $this->makeSibling($values);
    }

    /**
     * Return collection of values returned by callback
     *
     * @param callable $call
     * @param bool $distinct
     * @return Collection
     */
    function getCallbackValues(callable $call, bool $distinct = false): Collection
    {
        $values = [];
        foreach ($this->items as $key => $item) {
            $values[$key] = call_user_func_array($call, [$item, $key]);
        }
        if ($distinct) {
            $values = array_values(array_unique($values));
        }
        return $this->makeSibling($values);
    }

    /**
     * Check if item with value exists
     *
     * @param string $key
     * @param null $value
     * @param bool $strict
     * @return bool
     */
    function hasKeyValue(string $key, $value, bool $strict = false): bool
    {
        return (bool)$this->firstWith($key, $value, $strict);
    }


    /**
     * Return min value of given key
     *
     * @param string $key
     * @return mixed|null
     */
    function min(string $key)
    {
        if (empty($this->items)) {
            return null;
        }
        return min($this->getKeyValues($key)->toArray());
    }

    /**
     * Return min value of given key
     *
     * @param string $key
     * @return mixed
     */
    function max(string $key)
    {
        if (empty($this->items)) {
            return null;
        }
        return max($this->getKeyValues($key)->toArray());
    }

    /**
     * Return §rage of key values
     *
     * @param string $key
     * @return mixed
     */
    function average(string $key)
    {
        if (empty($this->items)) {
            return null;
        }
        $item_values = $this->getKeyValues($key)->toArray();
        $key_values = array_filter($item_values, function ($item) {
            if ($item === null) {
                return false;
            }
            if (!is_int($item) && !is_float($item)) {
                return false;
            }
            return true;
        });
        if (empty($key_values)) {
            return null;
        }
        return array_sum($key_values) / count($key_values);

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
    function random(int $num)
    {
        $rand_keys = array_rand($this->items, $num);
        if ($num === 1) {
            return $this->items[$rand_keys];
        }
        $rand_items = $this->makeSibling();
        foreach ($rand_keys as $rand_key) {
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
    function chunk(int $size, bool $preserve_keys = true): array
    {
        $chunks = [];
        foreach (array_chunk($this->items, $size, $preserve_keys) as $array_chunk) {
            $chunks[] = $this->makeSibling($array_chunk);
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
    function splice($offset, int $length = null): Collection
    {
        $items = $this->items;
        array_splice($items, $offset, $length);
        return $this->makeSibling($items);
    }

    /**
     *  Return a collection applying the callback to the items
     *
     * @param callable $callback
     * @return Collection
     * @see https://www.php.net/manual/function.array-map.php
     */
    function map(callable $callback): Collection
    {
        return $this->makeSibling(array_map($callback, $this->items));
    }

    /**
     * Return a collection of items on given page
     *
     * @param int $page
     * @param int $items_per_page
     * @return $this|null
     */
    function paginate(int $page, int $items_per_page): ?Collection
    {
        $chunks = $this->chunk($items_per_page);
        return $chunks[$page - 1] ?? null;
    }

    /**
     * Iteratively reduce items to single value using a callback function
     *
     * @param callable $callback
     * @return mixed
     * @see https://www.php.net/manual/function.array-reduce.php
     */
    function reduce(callable $callback)
    {
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
    function firstWith(string $key, $value, bool $strict = true)
    {
        if ($strict) {
            foreach ($this->items as $item) {
                $item_value = $this->getItemValue($item, $key);
                if ($item_value === $value) {
                    return $item;
                }
            }
        } else {
            foreach ($this->items as $item) {
                $item_value = $this->getItemValue($item, $key);
                if ($item_value == $value) {
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
    function firstIn(string $key, array $values, bool $strict = true)
    {
        $found = null;
        if ($strict) {
            foreach ($this->items as $item) {
                $item_value = $this->getItemValue($item, $key);
                foreach ($values as $value) {
                    if ($value === $item_value) {
                        $found = $item;
                        break 2;
                    }
                }
            }
        } else {
            foreach ($this->items as $item) {
                $item_value = $this->getItemValue($item, $key);
                foreach ($values as $value) {
                    if ($value == $item_value) {
                        $found = $item;
                        break 2;
                    }
                }
            }
        }
        return $found;
    }

    /**
     * Return first item not matching key value
     *
     * @param string $key
     * @param $value
     * @param bool $strict
     * @return bool|mixed
     */
    function firstNotWith(string $key, $value, bool $strict = true)
    {
        $found = null;
        if ($strict) {
            foreach ($this->items as $item) {
                $item_value = $this->getItemValue($item, $key);
                if ($item_value !== $value) {
                    $found = $item;
                    break;
                }
            }
        } else {
            foreach ($this->items as $item) {
                $item_value = $this->getItemValue($item, $key);
                if ($item_value != $value) {
                    $found = $item;
                    break;
                }
            }
        }
        return $found;
    }

    /**
     *  Return first item not matching any key of values
     *
     * @param string $key
     * @param array $values
     * @param bool $strict
     * @return mixed|null
     */
    function firstNotIn(string $key, array $values, bool $strict = true)
    {
        $found = null;
        if ($strict) {
            foreach ($this->items as $item) {
                $item_value = $this->getItemValue($item, $key);
                $not_in = true;
                foreach ($values as $value) {
                    if ($value === $item_value) {
                        $not_in = false;
                    }
                }
                if ($not_in) {
                    $found = $item;
                    break;
                }
            }
        } else {
            foreach ($this->items as $item) {
                $item_value = $this->getItemValue($item, $key);
                $not_in = true;
                foreach ($values as $value) {
                    if ($value == $item_value) {
                        $not_in = false;
                    }
                }
                if ($not_in) {
                    $found = $item;
                    break;
                }
            }
        }
        return $found;
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
    function lastWith(string $key, $value, bool $strict = true)
    {
        $found = null;
        if ($strict) {
            foreach ($this->items as $item) {
                $item_value = $this->getItemValue($item, $key);
                if ($item_value === $value) {
                    $found = $item;
                }
            }
        } else {
            foreach ($this->items as $item) {
                $item_value = $this->getItemValue($item, $key);
                if ($item_value == $value) {
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
    function lastIn(string $key, array $values, bool $strict = true)
    {
        $found = null;
        if ($strict) {
            foreach ($this->items as $item) {
                $item_value = $this->getItemValue($item, $key);
                foreach ($values as $value) {
                    if ($value === $item_value) {
                        $found = $item;
                        break;
                    }
                }
            }
        } else {
            foreach ($this->items as $item) {
                $item_value = $this->getItemValue($item, $key);
                foreach ($values as $value) {
                    if ($value == $item_value) {
                        $found = $item;
                        break;
                    }
                }
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
    function lastNotWith(string $key, $value, bool $strict = true)
    {
        $found = null;
        if ($strict) {
            foreach ($this->items as $item) {
                $item_value = $this->getItemValue($item, $key);
                if ($item_value !== $value) {
                    $found = $item;
                }
            }
        } else {
            foreach ($this->items as $item) {
                $item_value = $this->getItemValue($item, $key);
                if ($item_value != $value) {
                    $found = $item;
                }
            }
        }
        return $found;
    }

    /**
     *  Return last item not matching any values of key
     *
     * @param string $key
     * @param array $values
     * @param bool $strict
     * @return mixed|null
     */
    function lastNotIn(string $key, array $values, bool $strict = true)
    {
        $found = null;
        if ($strict) {
            foreach ($this->items as $item) {
                $item_value = $this->getItemValue($item, $key);
                $not_in = true;
                foreach ($values as $value) {
                    if ($value === $item_value) {
                        $not_in = false;
                    }
                }
                if ($not_in) {
                    $found = $item;
                }
            }
        } else {
            foreach ($this->items as $item) {
                $item_value = $this->getItemValue($item, $key);
                $not_in = true;
                foreach ($values as $value) {
                    if ($value == $item_value) {
                        $not_in = false;
                    }
                }
                if ($not_in) {
                    $found = $item;
                }
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
     * @param string $key
     * @param $value
     * @param bool $strict
     * @param bool $preserve_keys
     * @return Collection
     */
    function filterBy(string $key, $value, bool $strict = true, bool $preserve_keys = false): Collection
    {
        $found = $this->makeSibling();
        if ($strict) {
            foreach ($this->items as $item) {
                if ($this->getItemValue($item, $key) === $value) {
                    $found->addItem($key, $item, $preserve_keys);
                }
            }
        } else {
            foreach ($this->items as $item) {
                if ($this->getItemValue($item, $key) == $value) {
                    $found->addItem($key, $item, $preserve_keys);
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
     * @param bool $preserve_keys
     * @return Collection
     */
    function filterNotBy($key, $value, bool $strict = true, bool $preserve_keys = false): Collection
    {
        $found = $this->makeSibling();
        if ($strict) {
            foreach ($this->items as $item) {
                $item_value = $this->getItemValue($item, $key);
                if ($item_value !== $value) {
                    $found->addItem($key, $item, $preserve_keys);
                }
            }
        } else {
            foreach ($this->items as $item) {
                $item_value = $this->getItemValue($item, $key);
                if ($item_value != $value) {
                    $found->addItem($key, $item, $preserve_keys);
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
     * @param bool $preserve_keys
     * @return Collection
     */
    function filterIn(string $key, array $values, bool $strict = true, bool $preserve_keys = false): Collection
    {
        $found = $this->makeSibling();
        if ($strict) {
            foreach ($this->items as $item) {
                $item_value = $this->getItemValue($item, $key);
                foreach ($values as $value) {
                    if ($item_value === $value) {
                        $found->addItem($key, $item, $preserve_keys);
                    }
                }
            }
        } else {
            foreach ($this->items as $item) {
                $item_value = $this->getItemValue($item, $key);
                foreach ($values as $value) {
                    if ($item_value == $value) {
                        $found->addItem($key, $item, $preserve_keys);
                    }
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
     * @param bool $preserve_keys
     * @return Collection
     */
    function filterNotIn(string $key, array $values, bool $strict = true, bool $preserve_keys = false): Collection
    {
        $found = $this->makeSibling();
        if ($strict) {
            foreach ($this->items as $item) {
                $item_value = $this->getItemValue($item, $key);
                $not_in = true;
                foreach ($values as $value) {
                    if ($item_value === $value) {
                        $not_in = false;
                    }
                }
                if ($not_in) {
                    $found->addItem($key, $item, $preserve_keys);
                }
            }
        } else {
            foreach ($this->items as $item) {
                $item_value = $this->getItemValue($item, $key);
                $not_in = true;
                foreach ($values as $value) {
                    if ($item_value == $value) {
                        $not_in = false;
                    }
                }
                if ($not_in) {
                    $found->addItem($key, $item, $preserve_keys);
                }
            }
        }
        return $found;
    }

    /**
     * Return collection of items matching callback
     *
     * @param callable $callback
     * @param bool $preserve_keys
     * @return Collection
     */
    function filterCallback(callable $callback, bool $preserve_keys = false): Collection
    {
        $found = $this->makeSibling();
        foreach ($this->items as $key => $item) {
            if (call_user_func_array($callback, [$item, $key])) {
                $found->addItem($key, $item, $preserve_keys);

            }
        }
        return $found;
    }

    /*
     * Group methods
     */

    /**
     * Return collection of items grouped by key
     *
     * @param string $key
     * @param bool $preserve_keys
     * @return Collection
     */
    function groupBy(string $key, bool $preserve_keys = false): Collection
    {
        $groups = [];
        foreach ($this->items as $item_key => $item) {
            $value = $this->getItemValue($item, $key);
            if (!is_string($value) && !is_int($value)) {
                $groups[] = $item;
            } else {
                if (!isset($groups[$value])) {
                    $groups[$value] = $this->makeSibling();
                }
                $groups[$value]->addItem($item_key, $item, $preserve_keys);
            }

        }
        return $this->makeSibling($groups);
    }

    /**
     * @param callable $call
     * @return array
     */
    function groupCallback(callable $call): array
    {
        $array = [];
        foreach ($this->items as $key => $item) {
            $real_label = call_user_func_array($call, [$item, $key]);
            if (!isset($array[$real_label])) {
                $array[$real_label] = $this->makeSibling();
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
    function sortBy(string $key, bool $reverse = false): Collection
    {
        $items = $this->items;
        uasort($items, function ($a, $b) use ($key, $reverse) {
            if ($a[$key] === $b[$key]) {
                return 0;
            }
            if ($a[$key] === null) {
                return 1;
            }
            if ($b[$key] === null) {
                return -1;
            }
            if (!$reverse) {
                return ($a[$key] < $b[$key]) ? -1 : 1;
            }
            return ($a[$key] > $b[$key]) ? -1 : 1;
        });
        return $this->makeSibling($items);
    }

    /**
     * User sort collection
     *
     * @param callable $callback
     * @return Collection
     */
    function sortCallback(callable $callback): Collection
    {
        $items = $this->items;
        uasort($items, $callback);
        return $this->makeSibling($items);
    }

    /**
     * Reverse the order of collection items
     *
     * @param bool $preserve_keys
     * @return Collection
     */
    function reverse(bool $preserve_keys = true): Collection
    {
        return $this->makeSibling(array_reverse($this->items, $preserve_keys));
    }

    /**
     * Shuffle the collection
     *
     * @return Collection
     */
    function shuffle(): Collection
    {
        $items = $this->items;
        shuffle($items);
        return $this->makeSibling($items);
    }


    function extend(string $name, callable $callback)
    {
        if ($callback instanceof Closure) {
            $callback = $callback->bindTo($this, static::class);
        }
        $this->extensions[$name] = $callback;
    }

    protected function makeSibling(array $items = null): Collection
    {
        return new static($items);
    }

    /**
     * Return item value
     *
     * @param $item
     * @param string $key
     * @return mixed|null
     */
    protected function getItemValue($item, string $key)
    {
        $value = null;
        $key_parts = explode('.', $key);
        foreach ($key_parts as $key_part) {
            if (is_array($item)) {
                if (!array_key_exists($key_part, $item)) {
                    $value = null;
                    break;
                }
                $value = $item[$key_part];
                $item = $value;
            } else if ($item instanceof Closure) {
                $value = $item($key_part);
                $item = $value;
            } else if (is_object($item)) {
                if (!property_exists($item, $key_part)) {
                    $value = null;
                    break;
                }
                $value = $item->{$key_part};
                $item = $value;
            } else {
                $value = null;
                break;
            }
        }
        return $value;
    }

    protected function addItem($key, $item, bool $with_key = false)
    {
        if ($with_key) {
            $this->set($key, $item);
        } else {
            $this->add($item);
        }
    }

}