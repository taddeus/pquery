<?php
/**
 * pQuery plugin for composing sets of CSS stylesheets.
 * The plugin has built-in cache control and uses the CssParser class.
 * 
 * @package pQuery
 */

__p::require_plugins('cache');
__p::load_utils('CssParser');

/**
 * pQuery extension class for the 'css' plugin.
 */
class pQueryCss extends pQueryCache {
	static $accepts = array('array' => 'add_extensions', 'string' => 'make_array');
	
	var $minify_config = array(
			'replace_shorthands' => true,
			'sort_rules' => true,
			'minify' => true,
			'compress_measurements' => true,
			'compress_colors' => true
		);
	
	/**
	 * Make a single file into an array.
	 * 
	 * @param string $file The file to put in an array.
	 */
	function make_array($file) {
		return $this->add_extensions(array($file));
	}
	
	/**
	 * 
	 * 
	 * @param array $files 
	 */
	function add_extensions($files) {
		foreach( $files as $i => $file )
			if( !preg_match('/\.css$/', $file) )
				$files[$i] = $file.'.css';
		
		return $this->get_modification_dates($files);
	}
	
	/**
	 * 
	 */
	function minify() {
		$this->content = CssParser::minify($this->content, $this->minify_config);
		
		return $this;
	}
	
	/**
	 * 
	 */
	function set_headers() {
		header('Content-Type: text/css');
		
		return $this;
	}
}

/**
 * Shortcut constructor for {@link pQueryCss}.
 * 
 * @param array|string $stylesheets 
 * @returns pQueryCss A new stylesheet cache instance.
 */
function _css($stylesheets) {
	return pQuery::create('css', $stylesheets);
}

/*
 * Add plugin to pQuery
 */
__p::extend('pQueryCss', 'css');

?>