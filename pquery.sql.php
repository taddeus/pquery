<?php
/**
 * pQuery plugin for executing MySQL queries.
 * 
 * @package pQuery
 */

/**
 * pQuery extension class for the 'sql' plugin.
 * 
 * @property $query The query that is being evaluated.
 */
class pQuerySql extends pQuery {
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
		
		// Parse arguments as variables. Arrays and 
		// Replace variable indices by names equal to their indices
		$variables = array();
		
		foreach( $args as $i => $argument ) {
			if( is_array($argument) )
				$variables = array_merge($variables, $argument);
			else
				$variables[$i] = $argument;
		}
		
		// Replace variables by their escaped values
		$this->set($variables);
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
			return self::mysql_error($this->query);
		
		$this->result = $result;
		$this->executed = true;
		
		return $this;
	}
	
	/**
	 * Find the number of resulting rows of the current query.
	 * 
	 * @returns int The number of result rows.
	 * @uses mysql_num_rows
	 */
	function num_rows() {
		$this->assert_execution();
		
		return is_resource($this->result) ? mysql_num_rows($this->result) : 0;
	}
	
	/**
	 * Find the number of rows affected by the current query.
	 * 
	 * @returns int The number of affected rows.
	 * @uses mysql_affected_rows
	 */
	function affected_rows() {
		$this->assert_execution();
		
		return mysql_affected_rows(self::$link);
	}
	
	/**
	 * Fetch a row from the current result.
	 * 
	 * @param string $type The format of the result row.
	 * @returns mixed The fetched row in the requested format.
	 */
	function fetch($type=self::DEFAULT_FETCH_TYPE) {
		$this->assert_execution();
		
		if( !is_resource($this->result) )
			return self::error('Query result is not a resource.');
		
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
		
		while( ($row = $this->fetch($type)) !== false )
			$results[] = $row;
		
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
		self::disconnect();
		
		self::$login_data = array_merge(self::$login_data,
			compact('host', 'username', 'password', 'dbname'));
	}
	
	/**
	 * Assert that the database server config has been set.
	 */
	static function assert_login_data_exist() {
		if( !isset(self::$login_data['host']) )
			return self::error('No MySQL database server host is specified.');
		
		if( !isset(self::$login_data['username']) )
			return self::error('No username is specified for the MySQL server.');
		
		if( !isset(self::$login_data['password']) )
			return self::error('No password is specified for the MySQL server.');
		
		if( !isset(self::$login_data['host']) )
			return self::error('No MySQL database name is specified.');
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
	 * Close the current connection, if any.
	 * 
	 * @uses mysql_close
	 */
	static function disconnect() {
		// Return if the connection has already been closed
		if( !self::$link )
			return;
		
		mysql_close(self::$link);
		self::$link = null;
	}
	
	/**
	 * Echo the latest MySQL error.
	 * If a query is specified and debug mode is on, add the query to the error message.
	 * 
	 * @param string $query The query that was executed, if any.
	 * @codeCoverageIgnore
	 */
	static function mysql_error($query='') {
		$error = sprintf('MySQL error %d: %s.', mysql_errno(), mysql_error());
		PQUERY_DEBUG && $error .= "\nQuery: ".$query;
		
		self::error($error);
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
	
	/**
	 * Select all records from the given table that match the constraints.
	 * 
	 * @param string $table The table to select from.
	 * @param array $constraints Column names pointing to their values.
	 * @param bool $escape Whether to escape the constraint values. Defaults to TRUE.
	 * @returns pQuerySql The created query instance.
	 */
	static function select($table, $columns, $constraints=array(), $escape=true) {
		return _sql("SELECT [columns] FROM `[table]` WHERE [constraints];")
			->set_unescaped(array(
				'columns' => self::parse_columns($columns),
				'table' => $table,
				'constraints' => self::parse_constraints($constraints, $escape)
			));
	}
	
	/**
	 * Apply the given changes to all records in the given table that
	 * match the constraints.
	 * 
	 * @param string $table The table to update in.
	 * @param array $changes Column names pointing to their new values.
	 * @param array $constraints Column names pointing to their values.
	 * @param bool $escape Whether to escape the changed values and the
	 *                     constraint values. Defaults to TRUE.
	 * @returns pQuerySql The created query instance.
	 */
	static function update($table, $changes, $constraints=array(), $escape=true) {
		// Parse changes
		$escaped_changes = array();
		
		foreach( $changes as $column => $value ) {
			$column = self::escape_column($column);
			$value = self::escape_value($value);
			$escaped_changes[] = "$column = $value";
		}
		
		return _sql("UPDATE `[table]` SET [changes] WHERE [constraints];")
			->set_unescaped(array(
				'table' => $table,
				'changes' => implode(", ", $escaped_changes),
				'constraints' => self::parse_constraints($constraints, $escape)
			));
	}
	
	/**
	 * Insert a record in the given table.
	 * 
	 * @param string $table The table to insert into.
	 * @param array $values The values to insert, pointed to by their column names.
	 * @param bool $escape Whether to escape the values. Defaults to TRUE.
	 * @returns pQuerySql The created query instance.
	 */
	static function insert_row($table, $values, $escape=true) {
		$columns = array_keys($values);
		$escape && $values = array_map('pQuerySql::escape', $values);
		
		return _sql("INSERT INTO `[table]`([columns]) VALUES([values]);")
			->set_unescaped(array(
				'table' => $table,
				'columns' => "`".implode("`, `", $columns)."`",
				'values' => "'".implode("', '", $values)."'"
			));
	}
	
	/**
	 * Delete all records from the given table that match the constraints.
	 * 
	 * @param string $table The table to delete from.
	 * @param array $constraints Column names pointing to their values.
	 * @param bool $escape Whether to escape the constraint values. Defaults to TRUE.
	 * @returns pQuerySql The created query instance.
	 */
	static function delete($table, $constraints, $escape=true) {
		return _sql("DELETE FROM `[table]` WHERE [constraints];")
			->set_unescaped(array(
				'table' => $table,
				'constraints' => self::parse_constraints($constraints, $escape)
			));
	}
	
	/**
	 * Parse a list of column names.
	 * 
	 * @param string|array $columns One of:
	 *     - '*': Returns itself.
	 *     - string: Treated as a column name or aggregate function, and
	 *               escaped as such with backticks.
	 *     - array: 
	 * @returns string The parsed columns.
	 */
	static function parse_columns($columns) {
		if( $columns == '*' )
			return '*';
		
		if( is_string($columns) )
			return self::escape_column($columns);
		
		if( !is_array($columns) )
			return self::error('Unknown columns type.');
		
		$escaped_columns = array();
		
		foreach( $columns as $key => $value ) {
			if( is_numeric($key) ) {
				// Simple column name
				$escaped_columns[] = self::escape_column($value);
			} else {
				// MySQL 'AS' construction
				$escaped_columns[] = self::escape_column($key)." AS `".$value."`";
			}
		}
		
		return implode(", ", $escaped_columns);
	}
	
	/**
	 * Escape a column name to be safely used (and in a tidy manner) in a column list.
	 * 
	 * @param string $column The column name to escape.
	 * @returns string The escaped column.
	 */
	static function escape_column($column) {
		if( preg_match('/^`.*?`$/', $column) ) {
			// `column` -> `column`
			return $column;
		} elseif( preg_match('/^(\w+)\.(\w+)$/', $column, $m) ) {
			// table.column -> `table`.`column`
			list($table, $column) = array_slice($m, 1);
			return "`$table`.`$column`";
		} elseif( preg_match('/^(\w+)\(([^)]+)\)$/', $column, $m) ) {
			// function(name) -> FUNCTION(`name`)
			// function(`name`) -> FUNCTION(`name`)
			list($aggregate_function, $column) = array_slice($m, 1);
			return strtoupper($aggregate_function)."(".self::escape_column($column).")";
		}
		
		// column -> `column`
		return "`$column`";
	}
	
	/**
	 * Escape a value so that it can be saved safely.
	 * 
	 * @param string $value The value to escape.
	 * @returns string The escaped value.
	 */
	static function escape_value($value) {
		if( preg_match("/^'[^']*'$/", $value) ) {
			// 'value' -> 'value'
			return $value;
		}
		
		// value -> 'value'
		return "'$value'";
	}
	
	/**
	 * Parse a list of constraints.
	 * 
	 * @param mixed $constraints One of:
	 *     - A variable that evaluates as "empty", which will yield the string '1'.
	 *     - A string, which will be returned unchanged.
	 *     - A list of column names pointing to their values. A value may be
	 *       a list, wich will yield a query with the MySQL 'IN' selector.
	 * @param bool $escape Whether to escape the values.
	 * @returns string The parsed constraints.
	 */
	static function parse_constraints($constraints, $escape) {
		if( empty($constraints) )
			return "1";
		
		if( is_string($constraints) )
			return $constraints;
		
		if( !is_array($constraints) )
			return self::error('Unknown constraints type.');
		
		$conditions = array();
		
		foreach( $constraints as $column => $value ) {
			$condition = "`$column` ";
			
			if( is_array($value) ) {
				$escape && $value = array_map('pQuerySql::escape', $value);
				$value = array_map('pQuerySql::escape_value', $value);
				$condition .= "IN (".implode(", ", $value).")";
			} else {
				$escape && $value = self::escape($value);
				$condition .= "= ".self::escape_value($value);
			}
			
			$conditions[] = $condition;
		}
		
		return implode(" AND ", $conditions);
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