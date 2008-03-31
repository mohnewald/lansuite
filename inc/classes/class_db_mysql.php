<?php

class db {
	var $link_id = 0;
	var $query_id = 0;
	var $record   = array();
	var $print_sql_error;
	var $success = false;
	var $count_query = 0;

    // Konstruktor
	function db() {
		global $lang;

		if (!extension_loaded("mysql")) echo(HTML_FONT_ERROR . $lang['class_db_mysql']['no_mysql'] . HTML_FONT_END);
	}


	function connect($save = false) {
		global $config, $lang;

        $this->querys_count = 0;

		$this->dbserver	= $config["database"]["server"];
		$this->dbuser   = $config["database"]["user"];
		$this->dbpasswd = $config["database"]["passwd"];
		$this->database = $config["database"]["database"];

		$this->link_id=@mysql_connect($this->dbserver,$this->dbuser,$this->dbpasswd);
		if (!$this->link_id) {
			if ($save == true) return false;
			else  {
				echo HTML_FONT_ERROR . $lang['class_db_mysql']['no_connection'] . HTML_FONT_END;
				exit();
			}
   		} elseif (!@mysql_select_db($this->database, $this->link_id)) {
			if ($save == true) return false; 
			else {
				echo HTML_FONT_ERROR . str_replace("%DB%", $this->database, $lang['class_db_mysql']['no_db'])  . HTML_FONT_END;
				exit();
			}
		} else $GLOBALS['db_link_id'] = $this->link_id;

		@mysql_query("/*!40101 SET NAMES utf8_general_ci */;", $GLOBALS['db_link_id']);

		return true;
	}

	function disconnect() {
    mysql_close($this->link_id);
  }

	function query($query_string) {
    // Escape bad mysql
    $query_test_string = str_replace("\'", '', strtolower($query_string)); # Cut out escaped ' and convert to lower string
    $query_test_string = ereg_replace("'[^']*'", "", strtolower($query_test_string)); # Cut out strings within '-quotes
    // No UNION
    if (!strpos($query_test_string, 'union ') === false) $query_string = '___UNION_STATEMENT_IS_FORBIDDEN_WITHIN_LANSUITE___'; 

    // No INTO OUTFILE
    elseif (!strpos($query_test_string, 'into outfile') === false) $query_string = '___INTO OUTFILE_STATEMENT_IS_FORBIDDEN_WITHIN_LANSUITE___'; 

    $this->querys[] = $query_string;
		$this->querys_count++;
		$this->query_id = mysql_query($query_string, $GLOBALS['db_link_id']);
		$this->sql_error = @mysql_error($GLOBALS['db_link_id']);
		$this->count_query++;
		if (!$this->query_id) $this->print_error($this->sql_error, $query_string);
		return $this->query_id;
	}

  function escape($match) {
    global $CurrentArg;

    if ($match[0] == '%int%') return (int)$CurrentArg;
    elseif ($match[0] == '%string%') return "'". mysql_real_escape_string((string)$CurrentArg, $GLOBALS['db_link_id']) ."'";
  }

  function qry() {
    global $config, $CurrentArg;

    $args = func_get_args();
    $query = array_shift($args);
    $query = str_replace('%prefix%', $config['database']['prefix'], $query);
    foreach ($args as $CurrentArg) $query = preg_replace_callback('#(%string%|%int%)#sUi', array('db', 'escape'), $query, 1);
    return $this->query($query);
  }

	function get_affected_rows() {
		return @mysql_affected_rows($GLOBALS['db_link_id']);
	}


	function fetch_array($query_id=-1) {
		if ($query_id != -1) $this->query_id = $query_id;

		$this->record = @mysql_fetch_array($this->query_id);
		return $this->record;
	}


	function free_result($query_id = -1) {
		if ($query_id != -1) $this->query_id = $query_id;

		return @mysql_free_result($this->query_id);
  	}


	function query_first($query_string) {
   		$this->query($query_string);
   		$row = $this->fetch_array($this->query_id);
   		$this->free_result($this->query_id);

   		return $row;
  	}

	function qry_first() {
    global $config, $CurrentArg;

    $args = func_get_args();
    $query = array_shift($args);
    $query = str_replace('%prefix%', $config['database']['prefix'], $query);
    foreach ($args as $CurrentArg) $query = preg_replace_callback('#(%string%|%int%)#sUi', array('db', 'escape'), $query, 1);
 		$this->query($query);

 		$row = $this->fetch_array($this->query_id);
 		$this->free_result($this->query_id);
    return $row;
 	}

  	function query_first_rows($query_string) { // fieldname "number" is reserved
   		$this->query($query_string);
   		$row = $this->fetch_array($this->query_id);
   		$row['number'] = $this->num_rows($this->query_id);
   		$this->free_result($this->query_id);

   		return $row;
  	}


	function num_rows($query_id=-1) {
		if ($query_id!=-1) $this->query_id=$query_id;

		return @mysql_num_rows($this->query_id);
  	}


	function insert_id() {
		return @mysql_insert_id($GLOBALS['db_link_id']);
  	}

  function stat() {
    return @mysql_stat($GLOBALS['db_link_id']);
  }

	function get_host_info() {
		return @mysql_get_host_info($GLOBALS['db_link_id']);
	}


	function print_error($msg, $query_string_with_error) {
		global $func, $config, $auth, $lang;

		if ($config['database']['display_debug_errors']) echo str_replace("%LINE%", __LINE__, str_replace("%ERROR%", $msg, str_replace("%QUERY%", $query_string_with_error, str_replace("%SCRIPT%", $func->internal_referer, $lang['class_db_mysql']['sql_error']))));

		$msg = str_replace("'", "", $msg);
		$post_this_error = str_replace("%LINE%", __LINE__, str_replace("%ERROR%", $msg, str_replace("%QUERY%", $query_string_with_error, str_replace("%SCRIPT%", $_SERVER["REQUEST_URI"], str_replace("%REFERRER%", $func->internal_referer, $lang['class_db_mysql']['sql_error_log'])))));

		$post_this_error = $func->escape_sql($post_this_error);
		
		$current_time = date("U");
		@mysql_query("INSERT INTO {$config["tables"]["log"]} SET date = '$current_time',  userid = '{$auth["userid"]}', type='3', description = '$post_this_error', sort_tag = 'SQL-Fehler'", $GLOBALS['db_link_id']);	
		$this->count_query++;
	}
	
	function field_exist($table,$field) {
    	$fields = mysql_list_fields($this->database, $table);
    	$columns = mysql_num_fields($fields);
    	$found = 0;
    	for ($i = 0; $i < $columns; $i++) {
     	   if ( trim($field) == trim(mysql_field_name($fields, $i)) ) $found = 1;
      	}
       return $found;
	} 
	
	function num_fields() {
    return mysql_num_fields($this->query_id);
  }

	function field_name($pos) {
    return mysql_field_name($this->query_id, $pos);
  }

	function get_mysqli_stmt() {
		$prep = $link_id->stmt_init();
		return $prep;
	}
}
?>
