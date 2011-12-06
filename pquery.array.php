<?php
/**
 * pQuery plugin for executing common array functions.
 * 
 * @package pQuery
 */

/**
 * @todo Documentation
 */
class pQueryArray extends pQuery implements pQueryExtension {
	static $accepts = array('array');
	
	/**
	 * Get the value of an array alement at the given index.
	 * 
	 * @param int|string $index The index to get the element of.
	 * @returns mixed The array value at the given index if it exists, or NULL otherwise.
	 */
	function get($index) {
		return isset($this->variable[$index]) ? $this->variable[$index] : null;
	}
	
	/**
	 * Check if the array is empty.
	 * 
	 * @returns bool Whether the array is empty.
	 */
	function is_empty() {
		return !$this->count();
	}
	
	/**
	 * Get the number of elementsin the array.
	 * 
	 * @returns int The number of elements.
	 */
	function count() {
		return count($this->variable);
	}
	
	/**
	 * Reverse the array.
	 * 
	 * @returns pQueryArray The current object.
	 */
	function reverse() {
		$this->variable = array_reverse($this->variable);
		
		return $this;
	}
	
	/**
	 * Execute an existing array function on the array.
	 * 
	 * @var $method string A (part of the) function name to execute.
	 * @var method $args Additional arguments to pass to the called function.
	 * @returns mixed Either the current object, or the return value
	 *                of the called array function.
	 */
	function __call($method, $args) {
		$function = 'array_'.$method;
		
		if( function_exists($function) ) {
			array_unshift($args, &$this->variable);
			
			return call_user_func_array($function, $args);
		}
		
		if( in_array($method, array('shuffle', 'sort')) ) {
			$method($this->variable);
			return $this;
		}
		
		return self::error('Plugin "%s" has no method "%s".', __CLASS__, $method);
	}
}

/**
 * Shortcut constructor for {@link pQueryArray}.
 * 
 * @returns pQueryArray A new pQueryArray instance.
 */
function _arr($array) {
	return pQuery::create('array', $array);
}

pQuery::extend('pQueryArray', 'array');

?>