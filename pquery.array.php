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
	function get($index) {
		return isset($this->variable[$index]) ? $this->variable[$index] : null;
	}
	
	function is_empty() {
		return !$this->count();
	}
	
	function reverse() {
		$this->variable = array_reverse($this->variable);
		
		return $this;
	}
	
	function __call($method, $args) {
		$function = 'array_'.$method;
		
		if( function_exists($function) ) {
			array_unshift($args, &$this->variable);
			
			return call_user_func_array($function, $args);
		}
		
		if( in_array($method, array('count')) )
			return $method($this->variable);
		
		if( in_array($method, array('shuffle')) ) {
			$method($this->variable);
			return $this;
		}
		
		return self::error('Plugin "%s" has no method "%s".', __CLASS__, $method);
	}
}

/**
 * Shortcut constructor for {@link pQuerySql}.
 * 
 * @returns pQuerySql A new pQuerySql instance.
 * @see pQuerySql::__construct
 */
function _arr($array) {
	return pQuery::create('pQueryArray', $array);
}

pQuery::extend('pQueryArray', 'array');

?>