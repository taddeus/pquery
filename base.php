<?php
/**
 * Base for pQuery PHP utility framework.
 * 
 * @package pQuery
 */

/**
 * Indicates whether the framework is in debug mode.
 * 
 * @var bool
 */
defined('DEBUG') || define('DEBUG', true);

/**
 * The root location of the pQuery framework folder.
 * 
 * @var string
 */
define('PQUERY_ROOT', 'D:/xampp/htdocs/pquery/');

/**
 * Common utility class.
 */
class pQuery {
	/**
	 * The minimum php version required to use the framework.
	 * 
	 * @var string
	 */
	static $REQUIRED_PHP_VERSION = '5.3';
	
	/**
	 * A list of all plugins currently included.
	 * 
	 * @var array
	 */
	static $plugins = array();
	
	/**
	 * The variable types accepted by the parser.
	 * 
	 * @var array
	 * @see set_variable()
	 */
	static $accepts = array('boolean', 'integer', 'double', 'string', 'array', 'object', 'NULL');
	
	/**
	 * The current variable.
	 * 
	 * @var mixed
	 */
	var $variable;
	
	/**
	 * Extend pQuery with a plugin.
	 * 
	 * @param string $class_name The name of the plugin's base class.
	 * @param string $alias The alias to save for the plugin (defaults to $class_name).
	 * @see $plugins
	 */
	static function extend($class_name, $alias=null) {
		// Assert plugin existance
		if( !class_exists($class_name) )
			return self::error('Plugin "%s" does not exist.', $class_name);
		
		// Assert that the plugin extend the base clas properly
		if( !in_array('pQueryExtension', class_implements($class_name)) )
			return self::error('Plugin "%s" does not implement pQueryExtension.', $class_name);
		
		// Assert that the required PHP version is installed
		if( isset($class_name::$REQUIRED_PHP_VERSION)
				&& version_compare(PHP_VERSION, $class_name::$REQUIRED_PHP_VERSION, '<') ) {
			return self::error('Plugin "%s" requires PHP version %s.',
				$class_name, $class_name::$REQUIRED_PHP_VERSION);
		}
		
		self::$plugins[$alias === null ? $class_name : $alias] = $class_name;
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
			//echo debug_backtrace();
		}
	}
	
	/**
	 * Constructor.
	 * 
	 * @param mixed $variable The variable to use an utility on.
	 */
	function __construct($variable) {
		$this->set_variable($variable);
	}
	
	/**
	 * Parse the type of the given variable, and convert it if needed.
	 * 
	 * @param mixed $variable The variable to parse.
	 * @param bool $force Whether not to check the variables type against the accepted types.
	 */
	function set_variable($variable, $force=false) {
		if( !$force ) {
			$type = gettype($variable);
			$class_name = get_class($this);
			$accepts = $class_name::$accepts;
			
			if( isset($accepts[$type]) ) {
				$convert_method = $accepts[$type];
				
				if( !method_exists($this, $convert_method) )
					return self::error('Plugin "%s" has no conversion method "%s".', $class_name, $convert_method);
				
				$result = $this->$convert_method($variable);
				$result === null || $variable = $result;
			} else if( !in_array($type, $accepts) ) {
				return self::error('Variable type "%s" is not accepted by class "%s".', $type, $class_name);
			}
		}
		
		$this->variable = $variable;
	}
	
	/**
	 * Load the file containing the utility class for a specific variable type.
	 * 
	 * @param mixed $typoe the variable type of the class to load.
	 */
	static function load_type_class($type) {
		$file = PQUERY_ROOT.$type.'.php';
		
		if( !file_exists($file) )
			return false;
		
		include_once $file;
		
		return true;
	}
}

/**
 * Interface used for extending the jQuery class.
 */
interface pQueryExtension {
	/**
	 * Constructor.
	 * 
	 * @param mixed $variable The variable to use an utility on.
	 */
	function __construct($variable);
}

/**
 * Shortcut constructor for {@link pQuery}.
 * 
 * @param mixed $variable The variable to use an utility on.
 * @param string $plugin The name of an utility plugin to use (optional).
 * @returns pQuery A new pQuery (or descendant) instance.
 */
function _p($variable, $plugin=null) {
	$class_name = 'pQuery';
	
	if( $plugin === null ) {
		// Use custom class for this variable type
		$type = gettype($variable);
		
		if( pQuery::load_type_class($type) )
			$class_name .= ucfirst($type);
	} else {
		// Use custom plugin class
		if( isset(pQuery::$plugins[$plugin]) )
			$class_name = pQuery::$plugins[$plugin];
		else if( DEBUG )
			pQuery::error('Plugin "%s" does not exist.', $plugin);
	}
	
	return new $class_name($variable);
}
 
?>