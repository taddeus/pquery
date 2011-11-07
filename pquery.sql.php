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
	const VARIABLE_PATTERN = '/\[\s*%s\s*\]/';
	
	static $accepts = array('string' => 'parse_query', 'resource');
	
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
	function set_plain($variables) {
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
		
		//debug('query:', $this->query);
		$result = mysql_query($this->query, self::$link);
		
		if( !$result )
			return self::mysql_error();
		
		$this->result = $result;
		$this->executed = true;
		
		return $this;
	}
	
	/**
	 * Fetch a row from the current result.
	 * 
	 * @param string $type The format of the result row.
	 * @returns mixed The fetched row in the requested format.
	 */
	function fetch($type) {
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
	function fetch_all($type) {
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
	 * Assert that the MySQL connection is opened.
	 * 
	 * @uses mysql_connect,mysql_select_db
	 */
	static function assert_connection() {
		// Return if the connection has already been opened
		if( self::$link )
			return;
		
		if( !isset(pQueryConfig::$sql) )
			return self::error('Could not connect to database: no MySQL config found.');
		
		// Connect to the database
		$c = pQueryConfig::$sql;
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
	 * Also, the current query is printed in DEBUG mode.
	 * 
	 * @returns bool FALSE
	 */
	static function error() {
		parent::error('MySQL error %d: %s.', mysql_errno(), mysql_error());
		
		if( DEBUG )
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