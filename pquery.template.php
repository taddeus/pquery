<?php
/**
 * pQuery plugin for parsing templates.
 * 
 * @package pQuery
 */

/**
 * @todo Documentation
 * @property $ Alias for {@link pQuery::variable}.
 */
class pQueryTemplate extends pQuery implements pQueryExtension {
	static $accepts = array('string' => 'open_template_file');
	
	/**
	 * Root folders from which template files will be included.
	 * 
	 * @var string
	 */
	static $include_path = array();
	
	/**
	 * @see pQuery::$variable_alias
	 * @var string|array
	 */
	static $variable_alias = 'template';
	
	/**
	 * Open the given template filename in the current variable.
	 */
	function open_template_file() {
		$found = false;
		
		foreach( self::$include_path as $root ) {
			$path = $root.$this->variable;
			
			if( is_file($path) ) {
				$found = true;
				break;
			}
		}
		
		if( !$found ) {
			return self::error("Could not find template file \"%s\", looked in folders:\n%s",
				$this->variable, implode("\n", self::$include_path));
		}
		
		$this->content = file_get_contents($path);
	}
	
	/**
	 * Replace all include paths by a single new one.
	 * 
	 * @param str $path The path to set.
	 * @param bool $relative Indicates whether the path is relative to the document root.
	 */
	static function set_root($path, $relative=true) {
		self::$include_path = array();
		self::add_root($path, $relative);
	}
	
	/**
	 * Add a new include path.
	 * 
	 * @param str $path The path to add.
	 * @param bool $relative Indicates whether the path is relative to the document root.
	 */
	static function add_root($path, $relative=true) {
		$relative && $path = PQUERY_ROOT.$path;
		preg_match('%/$%', $path) || $path .= '/';
		
		if( !is_dir($path) )
			return self::error('"%s" is not a directory.', $path);
		
		self::$include_path[] = $path;
	}
}

/**
 * Shortcut constructor for {@link pQueryTemplate}.
 * 
 * @param string $path The path to a template file.
 * @returns pQueryTemplate A new template instance.
 */
function _tpl($path) {
	return pQuery::create('tpl', $path);
}

__p::extend('pQueryTemplate', 'tpl');

__tpl::set_root('');

?>