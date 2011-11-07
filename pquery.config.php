<?php
/**
 * Base and plugin config for pQuery utility framework.
 * 
 * @package pQuery
 */

/**
 * Indicates whether the framework is in debug mode, which right
 * now only means that occuring errors will be displayed or not.
 * 
 * @var bool
 */
define('DEBUG', true);

/**
 * The root location of the pQuery framework folder, used for file
 * inclusions. The path is absolute and should end with a slash ('/').
 * 
 * @var string
 */
define('PQUERY_ROOT', 'D:/xampp/htdocs/pquery/');

/**
 * Abstract class containing plugin configs.
 */
abstract class pQueryConfig {
	/**
	 * Indicates whether errors occuring in the framework will
	 * terminate the script.
	 * 
	 * @var bool
	 */
	const ERROR_IS_FATAL = true;
	
	/**
	 * Name of the utilities folder
	 * 
	 * @var string
	 */
	const UTILS_FOLDER = 'utils/';
	
	/**
	 * Config for MySQL plugin.
	 * 
	 * @var array
	 */
	static $sql = array(
		'host' => 'localhost',
		'username' => 'root',
		'password' => '',
		'dbname' => 'tcms2'
	);
}

?>