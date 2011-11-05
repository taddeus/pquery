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
 * The root location of the pQuery framework folder, used for file inclusions.
 * 
 * @var string
 */
define('PQUERY_ROOT', 'D:/xampp/htdocs/pquery/');

/**
 * Indicates whether errors occuring in the framework will
 * terminate the script.
 * 
 * @var bool
 */
define('ERROR_IS_FATAL', true);

/**
 * Abstract class containing plugin configs.
 */
abstract class Config {
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