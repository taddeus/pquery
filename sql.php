<?php

/**
 * @todo Documentation
 */
class pQuerySql extends pQuery implements pQueryExtension {
	static $accepts = array('string' => 'parse_query', 'resource');
	
	function parse_query($query) {
		$this->query = $query;
	}
}

/**
 * Shortcut constructor for {@link pQuerySql}.
 * 
 * @returns pQuerySql A new pQuerySql instance.
 * @see pQuerySql::__construct
 */
function _s($query) {
	return _p($query, 'sql');
}

pQuerySql::extend('pQuerySql', 'sql');
debug(pQuery::$plugins);

?>