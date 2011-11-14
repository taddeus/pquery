<?php
/**
 * pQuery plugin for parsing templates.
 * 
 * @package pQuery
 */

/**
 * @todo Documentation
 * @property string $files 
 */
class pQueryCache extends pQuery implements pQueryExtension {
	const CACHE_FOLDER = 'cache/';
	const ADMINISTRATION_FILE = 'administration.php';
	
	static $accepts = array('array' => 'get_modification_dates', 'string' => 'make_array');
	
	/**
	 * @see pQuery::$variable_alias
	 * @var string|array
	 */
	static $variable_alias = 'files';
	
	/**
	 * A list of latest known modification timestamps of all files currently in the cache.
	 * 
	 * @var array
	 */
	static $admin;
	
	/**
	 * A list of actual modification timestamps of the current file list.
	 * 
	 * @var array
	 */
	var $modification_dates;
	
	/**
	 * Reduced script content.
	 * 
	 * @var string
	 */
	var $content = '';
	
	/**
	 * Make a single file into an array.
	 * 
	 * @param string $file The file to put in an array.
	 */
	function make_array($file) {
		return $this->get_modification_dates(array($file));
	}
	
	/**
	 * 
	 */
	function get_modification_dates($files) {
		// Assert existence of all files
		foreach( $files as $file )
			file_exists($file) || self::error('File "%s" does not exist.', $file);
		
		$timestamps = array_map('filemtime', $files);
		$this->modification_dates = array_combine($files, $timestamps);
		
		return $files;
	}
	
	/**
	 * 
	 * 
	 * @returns bool Whether the file list is in the cache and not updated.
	 */
	function admin_updated() {
		self::assert_admin_exists();
		
		foreach( $this->modification_dates as $file => $timestamp )
			if( !isset(self::$admin[$file]) || self::$admin[$file] !== $timestamp )
				return true;
		
		return false;
	}
	
	/**
	 * 
	 */
	function concatenate() {
		$this->content = implode("\n", array_map('file_get_contents', $this->files));
		
		return $this;
	}
	
	/**
	 * 
	 */
	function filename() {
		return str_replace('/', '-', implode('-', $this->files));
	}
	
	/**
	 * 
	 */
	function output() {
		$last_modified = max($this->modification_dates);
		header('Last-Modified: '.date('r', $last_modified));
		header('Expires: '.date('r', $last_modified + 60 * 60 * 24 * 365));
		header('Cache-Control: private');
		method_exists($this, 'set_headers') && $this->set_headers();
		
		if( $admin_updated = $this->admin_updated() || !isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ) {
			$this->save();
		} elseif( isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ) {
			$if_modified_since = strtotime(preg_replace('/;.*$/', '', $_SERVER['HTTP_IF_MODIFIED_SINCE']));
			
			if( $if_modified_since >= $last_modified ) {
				// Not modified
				header((php_sapi_name() == 'CGI' ? 'Status:' : 'HTTP/1.0').' 304 Not Modified');
				exit;
			}
		}
		
		die($this->content);
	}
	
	/**
	 * 
	 */
	function save() {
		$this->concatenate();
		self::assert_cache_folder_exists();
		method_exists($this, 'minify') && $this->minify();
		file_put_contents(self::CACHE_FOLDER.$this->filename(), $this->content);
		self::$admin = array_merge(self::$admin, $this->modification_dates);
		self::save_administration();
	}
	
	/**
	 * 
	 */
	static function save_administration() {
		$handle = fopen(self::CACHE_FOLDER.self::ADMINISTRATION_FILE, 'w');
		fwrite($handle, "<?php\n\npQueryCache::\$admin = array(\n");
		
		foreach( self::$admin as $file => $timestamp )
			fwrite($handle, "\t'$file' => $timestamp,\n");
		
		fwrite($handle, ");\n\n?>");
		fclose($handle);
	}
	
	/**
	 * Assert existence of the administration list by including the administration
	 * file if it exists, and assigning an empty array otherwise.
	 */
	static function assert_admin_exists() {
		if( self::$admin !== null )
			return;
		
		$path = self::CACHE_FOLDER.self::ADMINISTRATION_FILE;
		
		if( file_exists($path) )
			include_once $path;
		else
			self::$admin = array();
	}
	
	/**
	 * Assert existence of the cache folder.
	 */
	static function assert_cache_folder_exists() {
		is_dir(self::CACHE_FOLDER) || mkdir(self::CACHE_FOLDER, 0777, true);
	}
}

/**
 * Shortcut constructor for {@link pQueryCache}.
 * 
 * @param array|string $files 
 * @returns pQueryCache A new cache instance.
 */
function _cache($scripts) {
	return pQuery::create('cache', $scripts);
}

/*
 * Add plugin to pQuery
 */
__p::extend('pQueryCache', 'cache');

?>