<?php
/**
 * Base for pQuery package.
 * 
 * @package pQuery
 */

namespace pQuery;

/**
 * Shortcut constructor for {@link pQuery}.
 * 
 * @returns pQuery A new pQuery instance.
 */
function _() {
	$args = func_get_args();
	
	return call_user_func_array('pQuery::__construct', $args);
}

/**
 * Indicates whether the framework is in debug mode.
 * 
 * @var bool
 */
defined('DEBUG') || define('DEBUG', true);

/**
 * Common utility class.
 */
class pQuery {
	/**
	 * @see pQueryExtension::REQUIRED_PHP_VERSION
	 */
	const REQUIRED_PHP_VERSION = '5.3';
	
	/**
	 * A list of all plugins currently included.
	 * 
	 * @var array
	 */
	static $plugins = array();
	
	/**
	 * The current variable.
	 * 
	 * @var mixed
	 */
	var $variable;
	
	/**
	 * Extend pQuery with a plugin.
	 * 
	 * @param mixed $variable The variable to parse.
	 * @see $plugins
	 */
	static function extend($class_name, $alias=null) {
		if( !class_exists($class_name) )
			return self::error('Class "%s" does not exist.', $class_name);
		
		if( !($class_name instanceof pQueryExtension) )
			return self::error('Class "%s" does not implement pQueryExtension.', $class_name);
		
		if( $class_name )
			return self::error('Class "%s" does not implement pQueryExtension.', $class_name);
		
		self::$plugins[$alias === null ? $class_name : $alias ] = $class_name;
	}
	
	/**
	 * Display an error message if in {@link DEBUG} mode.
	 * 
	 * The optional arguments are passed to {@link printf}, along with $error.
	 * 
	 * @param string $error The error message to display.
	 */
	static function error($error/*, $arg1, $arg2...*/) {
		$args = func_get_args();
		
		if( DEBUG ) {
			call_user_func_array('printf', $args);
			echo debug_backtrace();
		}
	}
	
	/**
	 * Constructor.
	 * 
	 * @param mixed $variable The variable to use an utility on.
	 * @param string $plugin The name of an utility plugin to use (optional).
	 */
	function __construct($variable, $plugin=null) {
		if( $plugin !== null ) {
			if( isset($plugins[$plugin]) ) {
				$class_name = $plugins[$plugin];
				
				return new $class_name($variable);
			} else if( DEBUG ) {
				self::error('Plugin "%s" does not exist.', $plugin);
			}
		}
		
		$this->parse_variable($variable);
	}
	
	/**
	 * Parse the type of the given variable, and convert it if needed.
	 * 
	 * @param mixed $variable The variable to parse.
	 * @todo Type and conversion
	 */
	function parse_variable($variable) {
		
		
		$this->variable = $variable;
	}
}

/**
 * Interface used for extending the jQuery class.
 */
interface pQueryExtension {
	/**
	 * The minimum php version required to use the package.
	 * 
	 * @var string
	 */
	const REQUIRED_PHP_VERSION;
	
	/**
	 * Constructor.
	 * 
	 * @param mixed $variable The variable to use an utility on.
	 */
	function __construct($variable);
}
 
?>