<?php
/**
 * Sybil Framework
 * (c) 2015 Grégory Bellencontre
 */

namespace Sybil;

use ArrayAccess;
use IteratorAggregate;
use ArrayIterator;

/**
 * The collection class provides methods for
 * an easy array management.
 *
 * @author Grégory Bellencontre
 */
final class Collection implements ArrayAccess, IteratorAggregate
{
	private $data;
	private $cursor = -1;
	private $parent_cursor = -1;
	private $parent_length = 0;
	private $key = null;
	
	/*
	 * Instanciates a Collection object with an array.
	 *
	 * @param array $array Array of elements
	 * @param mixed $escape Sanitize values or not
	 * @param object $parent Parent Collection object or null
	 * @param mixed $key Parent key
	 */
	 
	public function __construct($data = array(), $escape = true, $parent = null,$key=null)
	{
		$this->data = is_array($data) ? $data : [];
		
		if ($escape === true) {
			$this->sanitize();
		}
		
		$this->toCollection();
		
		$this->parent_cursor = is_object($parent) ? $parent->getCursor() : -1;
		$this->parent_length = is_object($parent) ? $parent->length() : 0;
		$this->key = $key;
	}
	
	/*
	 * Converts data sub-elements into Collection objects.
	 */
	
	public function toCollection()
	{
		$data = [];
		
		foreach($this->data as $key=>$value) {
			if (is_array($value)) {
				$data[$key] = new Collection($value);
			}
			else {
				$data[$key] = $value;
			}
		}
		
		$this->data = $data;
	}
	
	/*
	 * Converts the current object sub-elements into array.
	 *
	 * @return array Data array.
	 */
	
	public function toArray()
	{
		$array = [];
		
		foreach($this->data as $key=>$value) {
			if (is_object($value)) {
				$array[$key] = $value->toArray();
			}
			else {
				$array[$key] = $value;
			}
		}
		
		return $array;
	}
	
	/*
	 * Returns the string representation of the object (current value).
	 *
	 * @return string String representation
	 */
	
	public function __toString() 
	{
		return (string) current($this->data);
	}
	
	/*
	 * Tests if a key exists in the array.
	 * No method way. Ex: isset($object->property)
	 *
	 * @param mixed $key Key to test
	 *
	 * @return bool true or false
	 */
	
	public function __isset($key)
	{
		return array_key_exists($key, $this->data);
	}
	
	/*
	 * Returns an array value using its key.
	 * No method way. Ex: $object->property
	 *
	 * @param mixed $key The given key
	 *
	 * @return mixed The value
	 */
		
	public function __get($key)
	{
		return $this->has($key) ? (is_array($this->data[$key]) ? new Collection($this->data[$key]) : $this->data[$key]) : null;
	}
	
	/*
	 * Adds data inside the array.
	 * No method way. Ex: $object->property = 'value'
	 *
	 * @param mixed $key The key (or the value, if $value is null)
	 * @param mixed $value The value for the given key
	 */
	
	public function __set($key,$value='undefined')
	{
		if ($value == 'undefined') {
			$this->data[] = $key;
		}
		else {
			$this->data[$key] = $value;
		}
		
		return true;
	}
	
	/*
	 * Removes an element in the array.
	 * No method way. Ex: unset($object->property)
	 *
	 * @param mixed $key The key to use
	 */
	
	public function __unset($key)
	{
		if ($this->has($key)) {
			unset($this->data[$key]);
			return true;
		}
		
		return false;
	}
	
	/*
	 * Tests if a key exists in the array.
	 *
	 * @param mixed $key Key to test
	 *
	 * @return bool true or false
	 */
	
	public function has($key)
	{
		return array_key_exists($key, $this->data);
	}
	
	/*
	 * Returns an array value using its key.
	 *
	 * @param mixed $key The given key
	 *
	 * @return mixed The value
	 */
	
	public function get($key)
	{
		return $this->has($key) ? (is_array($this->data[$key]) ? new Collection($this->data[$key]) : $this->data[$key]) : null;
	}
	
	/*
	 * Adds data inside the array.
	 *
	 * @param mixed $key The key (or the value, if $value is null)
	 * @param mixed $value The value for the given key
	 */
	
	public function set($key,$value=null)
	{
		if ($value == null) {
			$this->data[] = $key;
		}
		else {
			$this->data[$key] = $value;
		}
		
		return true;
	}
	
	/*
	 * Removes an element in the array.
	 *
	 * @param mixed $key The key to use
	 */
	
	public function remove($key)
	{
		if ($this->has($key)) {
			unset($this->data[$key]);
			return true;
		}
		
		return false;
	}
	
	/*
	 * Returns the current key.
	 *
	 * @return string The current key
	 */
	
	public function key()
	{
		return $this->key != null ? $this->key : key($this->data);
	}
	
	/*
	 * Returns the length of the array.
	 *
	 * @return int Array length
	 */
	
	public function length()
	{
		return count($this->data);
	}
	
	/*
	 * Checks if the array is empty or not.
	 *
	 * @return bool true or false
	 */
	
	public function isEmpty()
	{
		return empty($this->data);
	}
	
	/*
	 * Checks if the current element is a collection (array).
	 *
	 * @return bool true or false
	 */
	
	public function isCollection()
	{
		return count($this->data) > 1;
	}
	
	/*
	 * Joins array elements with a string.
	 *
	 * @param string $glue The glue parameter (default: coma)
	 *
	 * @return string String representation of the array, separated by the glue.
	 */
	
	public function implode($glue=',')
	{
		return implode($glue,$this->data);
	}
	
	/*
	 * Extracts and creates single variables using the array keys.
	 */
	
	public function extract()
	{
		extract($this->data);
	}
	
	/*
	 * Displays a value and a separator.
	 *
	 * @param string $separator The glue parameter
	 *
	 * @return string The value with its separator
	 */
	
	public function separate($separator)
	{
		return $this->isLast() === false ? current($this->data) . $separator : current($this->data);
	}
	
	/*
	 * Displays a string using a pattern.
	 *
	 * Available variables:
	 * - :key		The current key
	 * - :value		The current value
	 *
	 * @param string $pattern The pattern string
	 * @param string $separator The glue parameter if needed
	 *
	 * @return string The string with replaced values
	 */
	
	public function display($pattern,$separator='')
	{
		$string = preg_replace('/:key/',$this->key(),$pattern);
		$string = preg_replace('/:value/',current($this->data),$string);
		
		return $string . (!$this->isLast() ? $separator : '');
	}
	
	/*
	 * Cleans every value in the array.
	 */
	 
	private function sanitize() 
	{
		if (is_array($this->data)) {
			$clean_recursive = function(&$item, $key) {
			    $item = is_string($item) ? htmlspecialchars($item) : $item;
			};
	
			array_walk_recursive($this->data, $clean_recursive);
		}
		else {
			$this->data = htmlspecialchars($this->data);
		}
		
		unset($clean_recursive);
	}
	
	/*
	 * Removes every elements in the array.
	 */
	
	public function clean()
	{
		$this->data = array();
	}
	
	/*
	 * Resets the cursor position.
	 */
	
	public function reset() 
	{
		$this->cursor = -1;
	}
	
	/*
	 * Returns the the cursor position.
	 */
	
	public function getCursor()
	{
		return $this->cursor;
	}
	
	/*
	 * Returns the first element of the array.
	 *
	 * @return mixed Array first element
	 */
	
	public function first()
	{
		$element = array_slice($this->data,0,1);
			
		return is_array($element[0]) ? new Collection($element[0]) : $element[0];
	}
	
	/*
	 * Returns the last element of the array.
	 *
	 * @return mixed Array last element
	 */
	
	public function last()
	{
		$element = array_slice($this->data,-1,1);
			
		return is_array($element[0]) ? new Collection($element[0]) : $element[0];
	}
	
	/*
	 * Checks if element is the first one of its parent array.
	 *
	 * @return bool true or false
	 */
	
	public function isFirst()
	{
		return $this->parent_cursor == 0;
	}
	
	/*
	 * Checks if element is the last one of its parent array.
	 *
	 * @return bool true or false
	 */
	
	public function isLast()
	{
		return $this->parent_cursor == $this->parent_length - 1;
	}
	
	/*
	 * Moves the cursor of one position backward.
	 */
	
	public function prev()
	{
		$this->cursor = $this->cursor - 1;
	}
	
	/*
	 * Moves the cursor of one position forward.
	 */
	
	public function next()
	{
		$this->cursor = $this->cursor + 1;
	}
	
	/*
	 * Returns the next element in the array.
	 *
	 * @return mixed Array element or false
	 */
	
	public function fetch()
	{
		$this->next();
		
		if ($this->length() >= $this->cursor+1) {
			$element = array_slice($this->data,$this->cursor,1);
			
			if (is_array($element)) {
				$key = key($element);
				
				if (is_array($element[$key])) {
					return new Collection($element[$key],false,$this,$key);
				}
				else {
					$collection = !is_string($element[$key]) ? $element[$key]->toArray() : $element[$key];
					return new Collection($collection,false,$this,$key);
				}
			}
			else {
				return new Collection($element,false,$this->cursor);
			}
		}
		else {
			$this->cursor = -1;
			return false;
		}
	}
	
	/*
	 * Tests if the parent cursor value is not pair.
	 *
	 * @return bool true or false
	 */
	
	public function isOdd()
	{
		return $this->parent_cursor%2 == 1;
	}
	
	/*
	 * Tests if the parent cursor value is pair.
	 *
	 * @return bool true or false
	 */
	
	public function isEven()
	{
		return $this->parent_cursor%2 == 0;
	}
	
	/*
	 * Tests if a key exists in the array.
	 * [ArrayAccess method]
	 *
	 * @param mixed $offset Key to test
	 *
	 * @return function Collection "has" method
	 */
	
	public function offsetExists($offset)
	{
		return $this->has($offset);
	}
	
	/*
	 * Returns an array value using its key.
	 * [ArrayAccess method]
	 *
	 * @param mixed $offset The given key
	 *
	 * @return function Collection "get" method
	 */
	
	public function offsetGet($offset)
	{
		return $this->get($offset);
	}
	
	/*
	 * Adds data inside the array.
	 * [ArrayAccess method]
	 *
	 * @param mixed $offset The given key
	 * @param mixed $value The value for the given key
	 *
	 * @return function Collection "set" method
	 */
	
	public function offsetSet($offset,$value)
	{
		return $this->set($offset,$value);
	}
	
	/*
	 * Removes an element from the array.
	 * [ArrayAccess method]
	 *
	 * @param mixed $key The given key to remove
	 *
	 * @return function Collection "remove" method
	 */
	
	public function offsetUnset($offset)
	{
		return $this->remove($offset);
	}
	
	/*
	 * Returns an external iterator.
	 * [IteratorAggregate method]
	 *
	 * @return object ArrayIterator object
	 */
	
	public function getIterator()
	{
		return new ArrayIterator($this->data);
	}
}