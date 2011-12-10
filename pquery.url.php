<?php
/**
 * pQuery plugin for parsing templates.
 * 
 * @package pQuery
 */

/**
 * @todo Documentation
 * @property string $content The template's content.
 */
class pQueryUrl extends pQuery {
	static $accepts = array('string' => 'parse_url');
	
	/**
	 * @see pQuery::$variable_alias
	 * @var string|array
	 */
	static $variable_alias = 'url';
	
	/**
	 * 
	 * 
	 * @var string
	 */
	static $handlers = array();
	
	/**
	 * Remove slashes at the begin and end of the URL.
	 * 
	 * @param string $url The URL to parse.
	 */
	function parse_url($url) {
		return preg_replace('%(^/|/$)%', '', $url);
	}
	
	/**
	 * Add a handler function to a URL match.
	 * 
	 * @param string $pattern The URL pattern to match.
	 * @param callback $handler The handler to execute when the pattern is matched.
	 */
	static function add_handler($pattern, $handler) {
		is_callable($handler) || self::error('Handler "%s" is not callable.', $handler);
		self::$handlers["%$pattern%"] = $handler;
	}
	
	/**
	 * Add a list of handler functions to regexes.
	 * 
	 * @param array $handlers The list of handlers to add, with regexes as keys.
	 */
	static function add_handlers($handlers) {
		foreach( $handlers as $pattern => $handler )
			self::add_handler($pattern, $handler);
	}
	
	/**
	 * Execute the handler of the first matching URL regex.
	 * 
	 * @param string $path The path to add.
	 * @param bool $relative Indicates whether the path is relative to the document root.
	 */
	function handler() {
		foreach( self::$handlers as $pattern => $handler )
			if( preg_match($pattern, $this->url, $matches) )
				return call_user_func_array($handler, array_slice($matches, 1));
		
		self::error('URL "%s" has no handler.', $this->url);
		// @codeCoverageIgnoreStart
	}
	// @codeCoverageIgnoreEnd
}

/**
 * Shortcut constructor for {@link pQueryUrl}.
 * 
 * @param string $url 
 * @returns pQueryUrl A new URL instance.
 */
function _url($url) {
	return pQuery::create('url', $url);
}

/*
 * Add plugin to pQuery
 */
__p::extend('pQueryUrl', 'url');

?>