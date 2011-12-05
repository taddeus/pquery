<?php
/**
 * Base for pQuery PHP utility framework.
 * 
 * @package pQuery
 */

/**
 * Common utility class.
 */
class pQuery {
	/**
	 * Name of the utilities folder
	 * 
	 * @var string
	 */
	const UTILS_FOLDER = 'utils/';
	
	/**
	 * Pattern of the alias created for an extending plugin that has defined an alias.
	 * 
	 * @var string
	 */
	const CLASS_ALIAS_PATTERN = '__%s';
	
	/**
	 * Pattern of a plugin's filename.
	 * 
	 * @var string
	 */
	const PLUGIN_FILENAME_PATTERN = 'pquery.%s.php';
	
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
	 * A list of names of plugins that are required to run a plugin.
	 * 
	 * @var array
	 */
	static $require_plugins = array();
	
	/**
	 * Aliases for the variable setter and getter.
	 * 
	 * @var string|array
	 */
	static $variable_alias = array();
	
	/**
	 * The current variable.
	 * 
	 * @var mixed
	 */
	var $variable;
	
	/**
	 * Additional arguments that were passed to the constructor.
	 * 
	 * @var array
	 */
	var $arguments = array();
	
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
		
		if( $alias === null ) {
			self::$plugins[$class_name] = $class_name;
		} else {
			self::$plugins[$alias] = $class_name;
			class_alias($class_name, sprintf(self::CLASS_ALIAS_PATTERN, $alias));
		}
	}
	
	/**
	 * Display an error message if in {@link DEBUG} mode.
	 * 
	 * The optional arguments are passed to {@link printf}, along with $error.
	 * 
	 * @param string $error The error message to display.
	 */
	static function error($error /* , $arg1, $arg2... */) {
		$args = func_get_args();
		$error = call_user_func_array('sprintf', $args);
		
		throw new pQueryException($error);
	}
	
	/**
	 * Constructor.
	 * 
	 * @param string $class_name The class to constuct an object off.
	 * @param mixed $variable The variable to use an utility on.
	 */
	static function create() {
		$args = func_get_args();
		$plugin = array_shift($args);
		
		if( $plugin === null )
			$class_name = 'self';
		elseif( isset(self::$plugins[$plugin]) )
			$class_name = self::$plugins[$plugin];
		elseif( in_array($plugin, self::$plugins) )
			$class_name = $plugin;
		else
			return self::error('Plugin "%s" does not exist.', $plugin);
		
		$obj = new $class_name();
		$obj->arguments = $args;
		$obj->set_variable(array_shift($args));
		
		return $obj;
	}
	
	/**
	 * Try to load one or more utility files.
	 */
	static function load_utils(/* $basename1 $basename2, ... */) {
		$files = func_get_args();
		
		foreach( $files as $basename ) {
			$path = PQUERY_ROOT.self::UTILS_FOLDER.$basename.'.php';
			
			if( !file_exists($path) ) {
				return self::error('Utility "%s" could not be loaded (looked in "%s").',
					$basename, $path);
			}
			
			include_once $path;
		}
	}
	
	/**
	 * Try to load the file containing the utility class for a specific variable type.
	 * 
	 * @param string $type the variable type of the class to load.
	 */
	static function load_plugin($type) {
		$path = PQUERY_ROOT.sprintf(self::PLUGIN_FILENAME_PATTERN, $type);
		
		if( !file_exists($path) )
			return false;
		
		include_once $path;
		
		return true;
	}
	
	/**
	 * Include the nescessary files for the given plugins.
	 */
	static function require_plugins(/* $plugin1 [ , $plugin2, ... ] */) {
		$plugins = func_get_args();
		
		foreach( $plugins as $plugin ) {
			$path = PQUERY_ROOT.sprintf(self::PLUGIN_FILENAME_PATTERN, $plugin);
			
			if( !file_exists($path) ) {
				return self::error('Required plugin "%s" could not be located (looked in "%s").',
					$plugin, $path);
			}
			
			include_once $path;
		}
	}
	
	/**
	 * Parse the type of the given variable, and convert it if needed.
	 * 
	 * @param mixed $variable The variable to parse.
	 * @param bool $force Whether not to check the variables type against the accepted types.
	 */
	function set_variable($variable, $force=false) {
		$this->variable = $variable;
		
		if( $force )
			return;
		
		$type = gettype($variable);
		$class_name = get_class($this);
		$accepts = $class_name::$accepts;
		
		if( isset($accepts[$type]) ) {
			$convert_method = $accepts[$type];
			
			if( !method_exists($this, $convert_method) ) {
				return self::error('Plugin "%s" has no conversion method "%s".',
					$class_name, $convert_method);
			}
			
			$result = $this->$convert_method($variable);
			$result === null || $this->variable = $result;
		} else if( !in_array($type, $accepts) ) {
			return self::error('Variable type "%s" is not accepted by class "%s".',
				$type, $class_name);
		}
	}
	
	/**
	 * Getter for {@link variable}.
	 * 
	 * @see variable_alias
	 */
	function __get($name) {
		$class_name = get_class($this);
		
		if( in_array($name, (array)$class_name::$variable_alias) )
			return $this->variable;
	}
	
	/**
	 * Setter for {@link variable}.
	 * 
	 * @see variable_alias
	 */
	function __set($name, $value) {
		$class_name = get_class($this);
		
		if( in_array($name, (array)$class_name::$variable_alias) )
			$this->variable = $value;
	}
	
	/**
	 * Handler for pQuery exceptions.
	 * 
	 * If the execption is a (@link pQueryException}, exit the script with
	 * its message. Otherwise, throw the exception further.
	 * 
	 * @param Exception $e The exception to handle.
	 */
	function exception_handler($e) {
		if( $e instanceof pQueryException )
			die(nl2br($e->getMessage()));
		
		throw $e;
	}
}

/**
 * Exception class for error throwing
 */
class pQueryException extends Exception {
	
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
	//function __construct();
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
		
		if( pQuery::load_plugin($type) )
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

/*
 * Set an alias for the bas class consistent with plugin aliases.
 */
class_alias('pQuery', '__p');

/*
 * Set the exception handler
 */
set_exception_handler('__p::exception_handler');
 
?>