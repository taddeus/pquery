<?php
/**
 * pQuery plugin for parsing templates.
 * 
 * @package pQuery
 */

__p::load_util('block');

/**
 * @todo Documentation
 * @property string $content The template's content.
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
	static $variable_alias = 'content';
	
	/**
	 * The path the template was found in.
	 * 
	 * @var string
	 */
	var $path;
	
	/**
	 * Nested variable values.
	 * 
	 * @var Block
	 */
	var $data;
	
	/**
	 * Constructor
	 * 
	 * Create a new nested data block object for variable values.
	 * 
	 * @see data
	 */
	function __construct() {
		$this->data = new Block();
	}
	
	/**
	 * Open the given template filename in the current variable.
	 */
	function open_template_file() {
		$found = false;
		
		foreach( self::$include_path as $root ) {
			$path = $root.$this->variable;
			
			if( is_file($path) ) {
				$this->path = $path;
				$this->content = file_get_contents($path);
				return;
			}
		}
		
		self::error("Could not find template file \"%s\", looked in folders:\n%s",
			$this->variable, implode("\n", self::$include_path));
	}
	
	/**
	 * Replace blocks and variables in the template's content.
	 * 
	 * @returns string The template's content, with replaced variables.
	 */
	function parse() {
		$lines = array('-');
		$index = 0;
		
		// Loop through the content character by character
		for( $i = 0, $l = strlen($this->content); $i < $l; $i++ ) {
			$c = $this->content[$i];
			
			if( $c == '{' ) {
				// Possible variable container found, add marker
				$lines[] = '+';
				$index++;
			} elseif( $c == '}' ) {
				// Variable container closed, add closure marker
				$lines[] = '-';
				$index++;
			} else {
				// Add character to the last line
				$lines[$index] .= $c;
			}
		}
		
		$line_count = 1;
		$block = $root = new Block;
		$block->children = array();
		$in_block = false;
		
		// Loop through the parsed lines, building the block tree
		foreach( $lines as $line ) {
			$marker = $line[0];
			
			if( $marker == '+' ) {
				if( strpos($line, 'block:') === 1 ) {
					// Block start
					$block = $block->add('block', array('name' => substr($line, 7)));
				} elseif( strpos($line, 'end') === 1 ) {
					// Block end
					if( $block->parent === null ) {
						return self::error('Unexpected "{end}" at line %d in template "%s".',
							$line_count, $this->path);
					}
					
					$block = $block->parent;
				} else {
					// Variable enclosure
					$block->add('variable', array('content' => substr($line, 1)));
				}
			} else {
				$block->add('', array('content' => substr($line, 1)));
			}
			
			// Count lines for error messages
			$line_count += substr_count($line, "\n");
		}
		
		// Use recursion to parse all blocks from the root level
		return self::parse_block($root, $this->data);
	}
	
	/**
	 * Replace a variable name if it exists within a given data scope.
	 * 
	 * Apply any of the following helper functions:
	 * - Translation: <code>{_:name[:count_var_name]}</code>
	 * - Default: <code>{var_name[:func1:func2:...]}</code>
	 * 
	 * @param string $variable The variable to replace.
	 * @param Block $data The data block to search in for the value.
	 * @returns string The variable's value if it exists, the original string otherwise.
	 * @todo Implement translations
	 */
	static function parse_variable($variable, $data) {
		$parts = explode(':', $variable);
		$name = $parts[0];
		$parts = array_slice($parts, 1);
		
		switch( $name ) {
			case '_':
				return '--translation--';
				break;
			default:
				$value = $data->get($name);
				
				// Don't continue if the variable name is not foudn in the data block
				if( $value === null )
					break;
				
				// Apply existing PHP functions to the variable's value
				foreach( $parts as $func ) {
					if( !is_callable($func) )
						return self::error('Function "%s" does not exist.', $func);
					
					$value = $func($value);
				}
				
				return $value;
		}
		
		return '{'.$variable.'}';
	}
	
	/**
	 * Parse a single block, recursively parsing its sub-blocks with a given data scope.
	 * 
	 * @param Block $variable The block to parse.
	 * @param Block $data the data block to search in for the variable values.
	 * @returns string The parsed block.
	 * @uses parse_variable
	 */
	static function parse_block($block, $data) {
		$content = '';
		
		foreach( $block->children as $child ) {
			switch( $child->name ) {
				case 'block':
					$block_name = $child->get('name');
					
					foreach( $data->find($block_name) as $block_data ) {
						$content .= self::parse_block($child, $block_data);
					}
					
					break;
				case 'variable':
					$content .= self::parse_variable($child->content, $data);
					break;
				default:
					$content .= $child->content;
			}
		}
		
		return $content;
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

/*
 * Add plugin to pQuery
 */
__p::extend('pQueryTemplate', 'tpl');

/*
 * Set initial root to pQuery root folder
 */
__tpl::set_root('');

?>