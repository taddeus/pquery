<?php
/**
 * pQuery plugin for executing MySQL queries.
 * 
 * @package pQuery
 */

/**
 * @todo Documentation
 * @property $query The query that is being evaluated.
 */
class pQuerySql extends pQuery implements pQueryExtension {
	static $accepts = array('string' => 'parse_query', 'resource');
	
	/**
	 * The pattern to use for specifying variables in a query.
	 */
	const VARIABLE_PATTERN = '/\[\s*%s\s*\]/';
	
	/**
	 * The default row fetching type, one of 'assoc', 'object' or 'array'.
	 */
	const DEFAULT_FETCH_TYPE = 'assoc';
	
	/**
	 * The MySQL link identifier.
	 * 
	 * @var resource
	 */
	static $link;
	
	/**
	 * @see pQuery::$variable_alias
	 * @var string|array
	 */
	static $variable_alias = 'query';
	
	/**
	 * Database login data, should be an associative array containing
	 * values for 'host', 'username', 'password' and 'dbname'
	 * 
	 * @var array
	 */
	static $login_data = array();
	
	/**
	 * The result of the current query.
	 * 
	 * @var resource|bool
	 */
	var $result;
	
	/**
	 * Indicates whether the query has been executed yet.
	 * 
	 * @var bool
	 */
	var $executed;
	
	/**
	 * Parse the given query string.
	 */
	function parse_query() {
		$args = $this->arguments;
		
		if( !count($args) )
			return;
		
		// Replace variable indices by names equal to their indices
		if( !is_array($args[0]) )
			array_unshift($args, null);
		
		// Replace variables by their escaped values
		$this->set($args);
	}
	
	/**
	 * Replace a set of variables with their (optionally escaped)
	 * values in the current query.
	 * 
	 * @param array $variables The variables to replace.
	 * @param bool $escape Whether to escape the variable values.
	 * @returns pQuerySql The current query object.
	 */
	function replace_variables($variables, $escape) {
		$patterns = array_map('pQuerySql::variable_pattern', array_keys($variables));
		$escape && $variables = array_map('pQuerySql::escape', $variables);
		$this->variable = preg_replace($patterns, $variables, $this->variable);
		$this->executed = false;
		
		return $this;
	}
	
	/**
	 * Replace a set of variables with their escaped values in the current query.
	 * 
	 * @param array $variables The variables to replace.
	 * @returns pQuerySql The current query object.
	 */
	function set($variables) {
		return $this->replace_variables($variables, true);
	}
	
	/**
	 * Replace a set of variables with their non-escaped values in the current query.
	 * 
	 * @param array $variables The variables to replace.
	 * @returns pQuerySql The current query object.
	 */
	function set_unescaped($variables) {
		return $this->replace_variables($variables, false);
	}
	
	/**
	 * Transform a variable name to a regex to be used as a replacement
	 * pattern in a query.
	 * 
	 * @param string $name The variable name to transform.
	 * @returns string The variable's replacement pattern.
	 */
	static function variable_pattern($name) {
		return sprintf(self::VARIABLE_PATTERN, $name);
	}
	
	/**
	 * Execute the current query.
	 * 
	 * @returns pQuerySql The current query object.
	 */
	function execute() {
		self::assert_connection();
		
		$result = mysql_query($this->query, self::$link);
		
		if( !$result )
			return self::mysql_error();
		
		$this->result = $result;
		$this->executed = true;
		
		return $this;
	}
	
	/**
	 * Find the number of resulting rows of the current query.
	 * 
	 * @returns int The number of result rows.
	 */
	function result_count() {
		$this->assert_execution();
		
		if( !$this->result )
			return 0;
		
		return mysql_num_rows($this->result);
	}
	
	/**
	 * Fetch a row from the current result.
	 * 
	 * @param string $type The format of the result row.
	 * @returns mixed The fetched row in the requested format.
	 */
	function fetch($type=self::DEFAULT_FETCH_TYPE) {
		$this->assert_execution();
		
		if( !$this->result )
			return self::error('No valid result to fetch from.');
		
		$func = 'mysql_fetch_'.$type;
		
		if( !function_exists($func) )
			return self::error('Fetch type "%s" is not supported.', $type);
		
		return $func($this->result);
	}
	
	/**
	 * Fetch all rows from the current result.
	 * 
	 * @param string $type The format of the result rows.
	 * @returns array The result set.
	 */
	function fetch_all($type=self::DEFAULT_FETCH_TYPE) {
		$results = array();
		
		while( ($row = $this->fetch($type)) !== false ) {
			$results[] = $row;
		}
		
		return $results;
		
		return $func($this->result);
	}
	
	/**
	 * Assert that the current query has been executed.
	 */
	function assert_execution() {
		$this->executed || $this->execute();
	}
	
	/**
	 * Set database server login data.
	 * 
	 * @param string $host The database server to connect with.
	 * @param string $username The username to login with on the database server.
	 * @param string $password The password to login with on the database server.
	 * @param string $dbname The name of the database to select after connecting to the server.
	 */
	static function set_login_data($host, $username, $password, $dbname) {
		// Close any existing connection
		if( self::$link ) {
			mysql_close(self::$link);
			self::$link = null;
		}
		
		self::$login_data = array_merge(self::$login_data,
			compact('host', 'username', 'password', 'dbname'));
	}
	
	/**
	 * Assert that the database server config has been set.
	 */
	static function assert_login_data_exist() {
		if( !isset(self::$login_data['host']) )
			return self::error('No SQL host specified.');
		
		if( !isset(self::$login_data['username']) )
			return self::error('No SQL username specified.');
		
		if( !isset(self::$login_data['password']) )
			return self::error('No SQL password specified.');
		
		if( !isset(self::$login_data['host']) )
			return self::error('No SQL host specified.');
	}
	
	/**
	 * Assert that the MySQL connection is opened.
	 * 
	 * @uses mysql_connect,mysql_select_db
	 */
	static function assert_connection() {
		// Return if the connection has already been opened
		if( self::$link )
			return;
		
		self::assert_login_data_exist();
		
		// Connect to the database
		$c = self::$login_data;
		$link = @mysql_connect($c['host'], $c['username'], $c['password']);
		
		if( $link === false )
			return self::mysql_error();
		
		self::$link = $link;
		
		// Select the correct database
		if( !@mysql_select_db($c['dbname'], $link) )
			return self::mysql_error();
	}
	
	/**
	 * Echo the latest MySQL error.
	 */
	static function mysql_error() {
		self::error('MySQL error %d: %s.', mysql_errno(), mysql_error());
	}
	
	/**
	 * Extention of {@link pQuery::error}, returning FALSE (useful in result loops).
	 * Also, the current query is printed in debug mode.
	 * 
	 * @returns bool FALSE
	 */
	static function error() {
		parent::error('MySQL error %d: %s.', mysql_errno(), mysql_error());
		
		if( PQUERY_DEBUG )
			echo $this->query;
		
		return false;
	}
	
	/**
	 * Escape a string for safe use in a query.
	 * 
	 * @param string $value The string to escape.
	 * @uses mysql_real_escape_string
	 */
	static function escape($value) {
		self::assert_connection();
		
		return mysql_real_escape_string($value, self::$link);
	}
}

/**
 * Shortcut constructor for {@link pQuerySql}.
 * 
 * @param string $query A MySQL query to evaluate.
 * @returns pQuerySql A new SQL query instance.
 */
function _sql($query /* [ , $arg1, ... ] */) {
	$args = func_get_args();
	$query = array_shift($args);
	array_unshift($args, 'sql', $query);
	
	return call_user_func_array('pQuery::create', $args);
}

pQuery::extend('pQuerySql', 'sql');

?>