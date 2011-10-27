<?php

/**
 * @todo Documentation
 */
class pQueryArray extends pQuery {
	function get($index) {
		return isset($this->variable[$index]) ? $this->variable[$index] : null;
	}
	
	function count() {
		return count($this->variable);
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
		
		return self::error('Plugin "%s" has no method "%s".', __CLASS__, $method);
	}
}

?>