<?php

namespace Simexis\Modulator\Modules;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection AS BaseCollection;
use Simexis\Modulator\Modules\Exceptions\MethodIsNotAllowed;

class Collection extends BaseCollection {
	
    /**
     * Remove an item from the collection by key.
     *
     * @param  string|array  $keys
     * @return $this
     */
    public function forget($keys)
    {
        foreach ((array) $keys as $key) {
            $this->offsetUnset($key);
        }

        return $this;
    }

    /**
     * Get an item from the collection by key.
     *
     * @param  mixed  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if ($this->offsetExists($key)) {
            return $this->offsetGet($key);
        }

        return value($default);
    }

    /**
     * Determine if an item exists in the collection by key.
     *
     * @param  mixed  $key
     * @return bool
     */
    public function has($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Push an item onto the end of the collection.
     *
     * @param  mixed  $value
     * @return $this
     */
    public function push($value)
    {
        throw new MethodIsNotAllowed('Push is disabled!');
    }

    /**
     * Get the collection of items as a plain array.
     *
     * @return array
     */
    public function toArray()
    {
        $items = array_map(function ($value) {
            if ($value instanceof Module) {
                return config($value->getSlugName());
            }

            return $value instanceof Arrayable ? $value->toArray() : $value;

        }, $this->items);
		
		$results = array();
		foreach($items AS $key => $value) {
			Arr::set($results, $key, $value);
		}
		return $results;
		
    }

    /**
     * Determine if an item exists at an offset.
     *
     * @param  mixed  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return Arr::has($this->convert(), $key);
    }

    /**
     * Get an item at a given offset.
     *
     * @param  mixed  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        $items = Arr::get($this->convert(), $key);
		if(is_array($items))
			return new static($items);
		return $items;
    }

    /**
     * Set the item at a given offset.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
		Arr::set($this->items, $key, $value);
    }

    /**
     * Unset the item at a given offset.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {	
		$result = $this->convert();
		Arr::forget($result, $key);
        $this->items = Arr::dot($result);
    }

    /**
     * Unset the item at a given offset.
     *
     * @param  string  $key
     * @return void
     */
    public function convert()
    {
		$results = array();
		foreach($this->items AS $key => $value) {
			Arr::set($results, $key, $value);
		}
		return $results;
    }
	
}