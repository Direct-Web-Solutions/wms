<?php
/**
 * 
 * core/db_mysqli.php
 * WMS (Website Management System)
 *
 * @category    database
 * @package     wms
 * @author      Darryn Fehr
 * @copyright   2018 Direct Web Solutions
 * @license     https://www.directwebsolutions.ca/wms/license
 * @version     2.0.0
 * @release     June 5, 2021
 * @link        https://www.directwebsolutions.ca/wms/latest
 * @since       File available since Release 2.0.0
 * @deprecated  File deprecated in Release 3.0.0
 * 
**/

class DB_MySQLi implements DB_Base {
    
    public $title = "MySQLi";
	public $short_title = "MySQLi";
	public $type;
	public $query_count = 0;
	public $querylist = array();
	public $error_reporting = 1;
	public $read_link;
	public $write_link;
	public $current_link;
	public $database;
	public $version;
	public $table_type = "myisam";
	public $table_prefix;
	public $engine = "mysqli";
	public $can_search = true;
	public $db_encoding = "utf8";
	public $query_time = 0;
	public $rows_affected_on_last_query = 0;
	protected $last_query_type = 0;

	function connect($config) {
		if (array_key_exists('hostname', $config)) {
			$connections['read'][] = $config;
		} else {
			if (!array_key_exists('read', $config))	{
				foreach ($config as $key => $settings) {
					if (is_int($key)) {
						$connections['read'][] = $settings;
					}
				}
			} else {
				$connections = $config;
			}
		}
		$this->db_encoding = $config['encoding'];
		foreach (array('read', 'write') as $type) {
			if (!isset($connections[$type]) || !is_array($connections[$type])) {
				break;
			}
			if (array_key_exists('hostname', $connections[$type])) {
				$details = $connections[$type];
				unset($connections[$type]);
				$connections[$type][] = $details;
			}
			shuffle($connections[$type]);
			foreach($connections[$type] as $single_connection) {
				$connect_function = "mysqli_connect";
				$persist = "";
				if (!empty($single_connection['pconnect']) && version_compare(PHP_VERSION, '5.3.0', '>=')) {
					$persist = 'p:';
				}
				$link = "{$type}_link";
				get_execution_time();
				$port = 0;
				if (strstr($single_connection['hostname'],':')) {
					list($hostname, $port) = explode(":", $single_connection['hostname'], 2);
				}
				if ($port) {
					$this->$link = @$connect_function($persist.$hostname, $single_connection['username'], $single_connection['password'], "", $port);
				} else {
					$this->$link = @$connect_function($persist.$single_connection['hostname'], $single_connection['username'], $single_connection['password']);
				}
				$time_spent = get_execution_time();
				$this->query_time += $time_spent;
				if ($this->$link) {
					$this->connections[] = "[".strtoupper($type)."] {$single_connection['username']}@{$single_connection['hostname']} (Connected in ".format_time_duration($time_spent).")";
					break;
				} else {
					$this->connections[] = "<span style=\"color: red\">[FAILED] [".strtoupper($type)."] {$single_connection['username']}@{$single_connection['hostname']}</span>";
				}
			}
		}
		if (!array_key_exists('write', $connections)) {
			$this->write_link = &$this->read_link;
		}
		if (!$this->read_link) {
			$this->error("[READ] Unable to connect to MySQL server");
			return false;
		} else if(!$this->write_link) {
			$this->error("[WRITE] Unable to connect to MySQL server");
			return false;
		}
		if (!$this->select_db($config['database']))	{
			return -1;
		}
		$this->current_link = &$this->read_link;
		return $this->read_link;
	}

	function select_db($database) {
		$this->database = $database;
		$master_success = @mysqli_select_db($this->read_link, $database) or $this->error("[READ] Unable to select database", $this->read_link);
		if ($this->write_link) {
			$slave_success = @mysqli_select_db($this->write_link, $database) or $this->error("[WRITE] Unable to select slave database", $this->write_link);
			$success = ($master_success && $slave_success ? true : false);
		} else {
			$success = $master_success;
		}
		if ($success && $this->db_encoding) {
			@mysqli_set_charset($this->read_link, $this->db_encoding);
			if ($slave_success && count($this->connections) > 1) {
				@mysqli_set_charset($this->write_link, $this->db_encoding);
			}
		}
		return $success;
	}

	function query($string, $hide_errors=0, $write_query=0) {
		global $wms;
		get_execution_time();
		if (($write_query || $this->last_query_type) && $this->write_link) {
			$this->current_link = &$this->write_link;
			$query = @mysqli_query($this->write_link, $string);
		} else {
			$this->current_link = &$this->read_link;
			$query = @mysqli_query($this->read_link, $string);
		}
		if ($this->error_number() && !$hide_errors) {
			$this->error($string);
			exit;
		}
		if ($write_query) {
			$this->last_query_type = 1;
		} else {
			$this->last_query_type = 0;
		}
		$query_time = get_execution_time();
		$this->query_time += $query_time;
		$this->query_count++;
		return $query;
	}

	function write_query($query, $hide_errors=0) {
		return $this->query($query, $hide_errors, 1);
	}

	function fetch_array($query, $resulttype = MYSQLI_ASSOC) {
		switch ($resulttype) {
			case MYSQLI_NUM:
			case MYSQLI_BOTH:
				break;
			default:
				$resulttype = MYSQLI_ASSOC;
				break;
		}
		$array = mysqli_fetch_array($query, $resulttype);
		return $array;
	}

	function fetch_field($query, $field, $row = FALSE) {
		if ($row !== FALSE)	{
			$this->data_seek($query, $row);
		}
		$array = $this->fetch_array($query);
		if ($array !== NULL)	{
			return $array[$field];
		}
		return NULL;
	}

	function data_seek($query, $row) {
		return mysqli_data_seek($query, $row);
	}

	function num_rows($query) {
		return mysqli_num_rows($query);
	}

	function insert_id() {
		$id = mysqli_insert_id($this->current_link);
		return $id;
	}

	function close() {
		@mysqli_close($this->read_link);
		if ($this->write_link) {
			@mysqli_close($this->write_link);
		}
	}

	function error_number()	{
		if ($this->current_link) {
			return mysqli_errno($this->current_link);
		} else {
			return mysqli_connect_errno();
		}
	}

	function error_string() {
		if ($this->current_link) {
			return mysqli_error($this->current_link);
		} else {
			return mysqli_connect_error();
		}
	}

	function error($string = "") {
		if ($this->error_reporting) {
			if (class_exists("errorHandler")) {
				global $error_handler;
				if (!is_object($error_handler))	{
					require_once ROOT_DIR . "core/class_errorhandler.php";
					$error_handler = new errorHandler();
				}
				$error = array(
					"error_no" => $this->error_number(),
					"error" => $this->error_string(),
					"query" => $string
				);
				$error_handler->error(WMS_SQL, $error);
			} else {
				trigger_error("<strong>[SQL] [".$this->error_number()."] ".$this->error_string()."</strong><br />{$string}", E_USER_ERROR);
			}
			return true;
		} else {
			return false;
		}
	}

	function affected_rows() {
		return mysqli_affected_rows($this->current_link);
	}

	function num_fields($query)	{
		return mysqli_num_fields($query);
	}

	function list_tables($database, $prefix = '') {
		if ($prefix) {
			if (version_compare($this->get_version(), '5.0.2', '>=')) {
				$query = $this->query("SHOW FULL TABLES FROM `$database` WHERE table_type = 'BASE TABLE' AND `Tables_in_$database` LIKE '".$this->escape_string($prefix)."%'");
			} else {
				$query = $this->query("SHOW TABLES FROM `$database` LIKE '".$this->escape_string($prefix)."%'");
			}
		} else {
			if (version_compare($this->get_version(), '5.0.2', '>=')) {
				$query = $this->query("SHOW FULL TABLES FROM `$database` WHERE table_type = 'BASE TABLE'");
			} else {
				$query = $this->query("SHOW TABLES FROM `$database`");
			}
		}
		$tables = array();
		while (list($table) = mysqli_fetch_array($query)) {
			$tables[] = $table;
		}
		return $tables;
	}

	function table_exists($table) {
		if (version_compare($this->get_version(), '5.0.2', '>=')) {
			$query = $this->query("SHOW FULL TABLES FROM `".$this->database."` WHERE table_type = 'BASE TABLE' AND `Tables_in_".$this->database."` = '{$this->table_prefix}$table'");
		} else {
			$query = $this->query("SHOW TABLES LIKE '{$this->table_prefix}$table'");
		}
		$exists = $this->num_rows($query);
		if ($exists > 0) {
			return true;
		} else {
			return false;
		}
	}

	function field_exists($field, $table) {
		$query = $this->write_query("SHOW COLUMNS FROM {$this->table_prefix}$table LIKE '$field'");
		$exists = $this->num_rows($query);
		if ($exists > 0) {
			return true;
		} else {
			return false;
		}
	}

	function shutdown_query($query, $name = "") {
		global $shutdown_queries;
		if ($name) {
			$shutdown_queries[$name] = $query;
		} else {
			$shutdown_queries[] = $query;
		}
	}
	
	function prepared_select($fromTable, $conditions = NULL, $bindVariable = NULL, $toSelect = "*", $use_prefix = TRUE) {
	    if (!is_array($bindVariable)) {
	        return FALSE;
	    }
	    if (empty($conditions)) {
	        return FALSE;
	    }
	    if (!empty($conditions)) {
	        $conditions = " " . $conditions;
	    }
	    $bind_stmt = "";
	    $count = 0;
	    foreach ($bindVariable as $variable_to_bind) {
            if (is_float($variable_to_bind)) {
                $bind_stmt .= "d";
            } else if (is_int($variable_to_bind)) {
                $bind_stmt .= "i";
            } else if (is_object($variable_to_bind)) {
                $bind_stmt .= "b";
            } else {
                $bind_stmt .= "s";
            }
            $count++;
        }
        $bind_itemset[] = $bind_stmt;
        if ($use_prefix) {
            $query = "SELECT " . $toSelect . " FROM " . $this->table_prefix . $fromTable . $conditions;
        } else {
            $query = "SELECT " . $toSelect . " FROM " . $fromTable . $conditions;
        }
        $this->current_link = &$this->read_link;
        if (($query || $this->last_query_type) && $this->write_link) {
			$this->current_link = &$this->write_link;
			$link_class = $this->write_link;
		} else {
			$this->current_link = &$this->read_link;
			$link_class = $this->read_link;
		}
		if ($this->error_number()) {
			return $this->error($stmt);
		}
        if ($stmt = @mysqli_prepare($link_class, $query)) {
            for ($i = 0; $i < count($bindVariable); $i++) {
                $bind_item = 'bind' . $i;
                $$bind_item = $bindVariable[$i];
                $bind_itemset[] = &$$bind_item;
            }
            call_user_func_array(array($stmt, 'bind_param'), $bind_itemset);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = @mysqli_num_rows($result);
            $result_set = NULL;
            if ($count > 1) {
                $result_set = $result->fetch_all(MYSQLI_ASSOC);
            } else {
                $result_set = $result->fetch_assoc();
            }
            $result->free();
            return $result_set;
        } else {
            return FALSE;
        }
	}

	function simple_select($table, $fields = "*", $conditions = "", $options = array()) {
		$query = "SELECT " . $fields . " FROM " . $this->table_prefix . $table;
		if ($conditions != "") {
			$query .= " WHERE ".$conditions;
		}
		if (isset($options['group_by'])) {
			$query .= " GROUP BY ".$options['group_by'];
		}
		if (isset($options['order_by'])) {
			$query .= " ORDER BY ".$options['order_by'];
			if (isset($options['order_dir'])) {
				$query .= " " . sys_strtoupper($options['order_dir']);
			}
		}
		if (isset($options['limit_start']) && isset($options['limit'])) {
			$query .= " LIMIT ".$options['limit_start'].", ".$options['limit'];
		} else if(isset($options['limit'])) {
			$query .= " LIMIT ".$options['limit'];
		}
		return $this->query($query);
	}

	function insert_query($table, $array) {
		global $wms;
		if(!is_array($array)) {
			return false;
		}
		foreach ($array as $field => $value) {
			if (isset($wms->binary_fields[$table][$field]) && $wms->binary_fields[$table][$field]) {
				if ($value[0] != 'X')  {
					$value = $this->escape_binary($value);
				}
				$array[$field] = $value;
			} else {
				$array[$field] = $this->quote_val($value);
			}
		}
		$fields = "`".implode("`,`", array_keys($array))."`";
		$values = implode(",", $array);
		$this->write_query("INSERT INTO {$this->table_prefix}{$table} (" . $fields . ") VALUES (" . $values . ")");
		return $this->insert_id();
	}

	function insert_query_multiple($table, $array) {
		global $wms;
		if (!is_array($array)) {
			return;
		}
		$fields = array_keys($array[0]);
		$fields = "`".implode("`,`", $fields)."`";
		$insert_rows = array();
		foreach ($array as $values) {
			foreach ($values as $field => $value) {
				if (isset($wms->binary_fields[$table][$field]) && $wms->binary_fields[$table][$field]) {
					if ($value[0] != 'X') {
						$value = $this->escape_binary($value);
					}
					$values[$field] = $value;
				} else {
					$values[$field] = $this->quote_val($value);
				}
			}
			$insert_rows[] = "(" . implode(",", $values) . ")";
		}
		$insert_rows = implode(", ", $insert_rows);
		$this->write_query("INSERT INTO {$this->table_prefix}{$table} ({$fields}) VALUES {$insert_rows}");
	}

	function update_query($table, $array, $where = "", $limit = "", $no_quote = FALSE) {
		global $wms;
		if (!is_array($array)) {
			return FALSE;
		}
		$comma = "";
		$query = "";
		$quote = "'";
		if ($no_quote == TRUE) {
			$quote = "";
		}
		foreach ($array as $field => $value) {
			if (isset($wms->binary_fields[$table][$field]) && $wms->binary_fields[$table][$field]) {
				if ($value[0] != 'X') {
					$value = $this->escape_binary($value);
				}
				$query .= $comma."`".$field."`={$value}";
			} else {
			    if ($value == "SET_NULL") {
			        $quoted_value = "NULL";
			    } else {
				    $quoted_value = $this->quote_val($value, $quote);
			    }
				$query .= $comma . "`" . $field . "`={$quoted_value}";
			}
			$comma = ', ';
		}
		if (!empty($where)) {
			$query .= " WHERE $where";
		}
		if (!empty($limit)) {
			$query .= " LIMIT $limit";
		}
		$update_query = $this->write_query("UPDATE {$this->table_prefix}$table SET $query");
		$this->rows_affected_on_last_query = $this->affected_rows($update_query);
		return $update_query;
	}

	private function quote_val($value, $quote = "'") {
		if (is_int($value)) {
			$quoted = $value;
		} else {
			$quoted = $quote . $value . $quote;
		}
		return $quoted;
	}

	function delete_query($table, $where = "", $limit = "") {
		$query = "";
		if (!empty($where)) {
			$query .= " WHERE $where";
		}
		if (!empty($limit)) {
			$query .= " LIMIT $limit";
		}
		return $this->write_query("DELETE FROM {$this->table_prefix}$table $query");
	}

	function escape_string($string) {
		if ($this->db_encoding == 'utf8') {
			$string = validate_utf8_string($string, false);
		} else if ($this->db_encoding == 'utf8mb4') {
			$string = validate_utf8_string($string);
		}
		if (function_exists("mysqli_real_escape_string") && $this->read_link) {
			$string = mysqli_real_escape_string($this->read_link, $string);
		} else {
			$string = addslashes($string);
		}
		return $string;
	}

	function free_result($query) {
		mysqli_free_result($query);
		return true;
	}

	function escape_string_like($string) {
		return $this->escape_string(str_replace(array('\\', '%', '_') , array('\\\\', '\\%' , '\\_') , $string));
	}

	function get_version() {
		if ($this->version) {
			return $this->version;
		}
		$query = $this->query("SELECT VERSION() as version");
		$ver = $this->fetch_array($query);
		$version = $ver['version'];
		if ($version) {
			$version = explode(".", $version, 3);
			$this->version = (int) $version[0] . "." . (int) $version[1] . "." . (int) $version[2];
		}
		return $this->version;
	}

	function optimize_table($table) {
		$this->write_query("OPTIMIZE TABLE " . $this->table_prefix . $table . "");
	}

	function analyze_table($table) {
		$this->write_query("ANALYZE TABLE " . $this->table_prefix . $table . "");
	}

	function show_create_table($table) {
		$query = $this->write_query("SHOW CREATE TABLE " . $this->table_prefix . $table . "");
		$structure = $this->fetch_array($query);
		return $structure['Create Table'];
	}

	function show_fields_from($table) {
		$query = $this->write_query("SHOW FIELDS FROM " . $this->table_prefix . $table . "");
		$field_info = array();
		while ($field = $this->fetch_array($query)) {
			$field_info[] = $field;
		}
		return $field_info;
	}

	function is_fulltext($table, $index = "") {
		$structure = $this->show_create_table($table);
		if ($index != "") {
			if (preg_match("#FULLTEXT KEY (`?)$index(`?)#i", $structure)) {
				return true;
			} else {
				return false;
			}
		}
		if (preg_match('#FULLTEXT KEY#i', $structure)) {
			return true;
		}
		return false;
	}

	function supports_fulltext($table) {
		$version = $this->get_version();
		$query = $this->write_query("SHOW TABLE STATUS LIKE '{$this->table_prefix}$table'");
		$status = $this->fetch_array($query);
		$table_type = my_strtoupper($status['Engine']);
		if (version_compare($version, '3.23.23', '>=') && ($table_type == 'MYISAM' || $table_type == 'ARIA')) {
			return true;
		} else if (version_compare($version, '5.6', '>=') && $table_type == 'INNODB') {
			return true;
		}
		return false;
	}

	function supports_fulltext_boolean($table) {
		$version = $this->get_version();
		$supports_fulltext = $this->supports_fulltext($table);
		if (version_compare($version, '4.0.1', '>=') && $supports_fulltext == true) {
			return true;
		}
		return false;
	}

	function index_exists($table, $index) {
		$index_exists = false;
		$query = $this->write_query("SHOW INDEX FROM {$this->table_prefix}{$table}");
		while ($ukey = $this->fetch_array($query)) {
			if ($ukey['Key_name'] == $index) {
				$index_exists = true;
				break;
			}
		}
		if ($index_exists) {
			return true;
		}
		return false;
	}

	function create_fulltext_index($table, $column, $name = "") {
		$this->write_query("ALTER TABLE {$this->table_prefix}$table ADD FULLTEXT $name ($column)");
	}

	function drop_index($table, $name) {
		$this->write_query("ALTER TABLE {$this->table_prefix}$table DROP INDEX $name");
	}

	function drop_table($table, $hard = false, $table_prefix = true) {
		if ($table_prefix == false) {
			$table_prefix = "";
		} else {
			$table_prefix = $this->table_prefix;
		}
		if ($hard == false) {
			$this->write_query('DROP TABLE IF EXISTS ' . $table_prefix . $table);
		} else {
			$this->write_query('DROP TABLE ' . $table_prefix . $table);
		}
	}

	function rename_table($old_table, $new_table, $table_prefix = true) {
		if ($table_prefix == false) {
			$table_prefix = "";
		} else {
			$table_prefix = $this->table_prefix;
		}
		return $this->write_query("RENAME TABLE {$table_prefix}{$old_table} TO {$table_prefix}{$new_table}");
	}

	function replace_query($table, $replacements=array(), $default_field = "", $insert_id = true) {
		global $wms;
		$values = '';
		$comma = '';
		foreach ($replacements as $column => $value) {
			if (isset($wms->binary_fields[$table][$column]) && $wms->binary_fields[$table][$column]) {
				if ($value[0] != 'X') {
					$value = $this->escape_binary($value);
				}
				$values .= $comma."`".$column."`=".$value;
			} else {
				$values .= $comma."`".$column."`=".$this->quote_val($value);
			}
			$comma = ',';
		}
		if (empty($replacements)) {
			 return false;
		}
		return $this->write_query("REPLACE INTO {$this->table_prefix}{$table} SET {$values}");
	}

	function drop_column($table, $column) {
		$column = trim($column, '`');
		return $this->write_query("ALTER TABLE {$this->table_prefix}{$table} DROP `{$column}`");
	}

	function add_column($table, $column, $definition) {
		$column = trim($column, '`');
		return $this->write_query("ALTER TABLE {$this->table_prefix}{$table} ADD `{$column}` {$definition}");
	}

	function modify_column($table, $column, $new_definition, $new_not_null = false, $new_default_value = false) {
		$column = trim($column, '`');
		if ($new_not_null !== false) {
			if (strtolower($new_not_null) == "set") {
				$not_null = "NOT NULL";
			} else {
				$not_null = "NULL";
			}
		} else {
			$not_null = '';
		}
		if ($new_default_value !== false) {
			$default = "DEFAULT " . $new_default_value;
		} else {
			$default = '';
		}
		return (bool) $this->write_query("ALTER TABLE {$this->table_prefix}{$table} MODIFY `{$column}` {$new_definition} {$not_null} {$default}");
	}

	function rename_column($table, $old_column, $new_column, $new_definition, $new_not_null = false, $new_default_value = false) {
		$old_column = trim($old_column, '`');
		$new_column = trim($new_column, '`');
		if ($new_not_null !== false) {
			if (strtolower($new_not_null) == "set") {
				$not_null = "NOT NULL";
			} else {
				$not_null = "NULL";
			}
		} else {
			$not_null = '';
		}
		if ($new_default_value !== false) {
			$default = "DEFAULT " . $new_default_value;
		} else {
			$default = '';
		}
		return (bool) $this->write_query("ALTER TABLE {$this->table_prefix}{$table} CHANGE `{$old_column}` `{$new_column}` {$new_definition} {$not_null} {$default}");
	}

	function set_table_prefix($prefix) {
		$this->table_prefix = $prefix;
	}

	function fetch_size($table='') {
		if ($table != '') {
			$query = $this->query("SHOW TABLE STATUS LIKE '".$this->table_prefix.$table."'");
		} else {
			$query = $this->query("SHOW TABLE STATUS");
		}
		$total = 0;
		while ($table = $this->fetch_array($query)) {
			$total += $table['Data_length']+$table['Index_length'];
		}
		return $total;
	}

	function fetch_db_charsets() {
		if ($this->write_link && version_compare($this->get_version(), "4.1", "<")) {
			return false;
		}
		return array(
			'big5' => 'Big5 Traditional Chinese',
			'dec8' => 'DEC West European',
			'cp850' => 'DOS West European',
			'hp8' => 'HP West European',
			'koi8r' => 'KOI8-R Relcom Russian',
			'latin1' => 'ISO 8859-1 Latin 1',
			'latin2' => 'ISO 8859-2 Central European',
			'swe7' => '7bit Swedish',
			'ascii' => 'US ASCII',
			'ujis' => 'EUC-JP Japanese',
			'sjis' => 'Shift-JIS Japanese',
			'hebrew' => 'ISO 8859-8 Hebrew',
			'tis620' => 'TIS620 Thai',
			'euckr' => 'EUC-KR Korean',
			'koi8u' => 'KOI8-U Ukrainian',
			'gb2312' => 'GB2312 Simplified Chinese',
			'greek' => 'ISO 8859-7 Greek',
			'cp1250' => 'Windows Central European',
			'gbk' => 'GBK Simplified Chinese',
			'latin5' => 'ISO 8859-9 Turkish',
			'armscii8' => 'ARMSCII-8 Armenian',
			'utf8' => 'UTF-8 Unicode',
			'utf8mb4' => '4-Byte UTF-8 Unicode (requires MySQL 5.5.3 or above)',
			'ucs2' => 'UCS-2 Unicode',
			'cp866' => 'DOS Russian',
			'keybcs2' => 'DOS Kamenicky Czech-Slovak',
			'macce' => 'Mac Central European',
			'macroman' => 'Mac West European',
			'cp852' => 'DOS Central European',
			'latin7' => 'ISO 8859-13 Baltic',
			'cp1251' => 'Windows Cyrillic',
			'cp1256' => 'Windows Arabic',
			'cp1257' => 'Windows Baltic',
			'geostd8' => 'GEOSTD8 Georgian',
			'cp932' => 'SJIS for Windows Japanese',
			'eucjpms' => 'UJIS for Windows Japanese',
		);
	}

	function fetch_charset_collation($charset) {
		$collations = array(
			'big5' => 'big5_chinese_ci',
			'dec8' => 'dec8_swedish_ci',
			'cp850' => 'cp850_general_ci',
			'hp8' => 'hp8_english_ci',
			'koi8r' => 'koi8r_general_ci',
			'latin1' => 'latin1_swedish_ci',
			'latin2' => 'latin2_general_ci',
			'swe7' => 'swe7_swedish_ci',
			'ascii' => 'ascii_general_ci',
			'ujis' => 'ujis_japanese_ci',
			'sjis' => 'sjis_japanese_ci',
			'hebrew' => 'hebrew_general_ci',
			'tis620' => 'tis620_thai_ci',
			'euckr' => 'euckr_korean_ci',
			'koi8u' => 'koi8u_general_ci',
			'gb2312' => 'gb2312_chinese_ci',
			'greek' => 'greek_general_ci',
			'cp1250' => 'cp1250_general_ci',
			'gbk' => 'gbk_chinese_ci',
			'latin5' => 'latin5_turkish_ci',
			'armscii8' => 'armscii8_general_ci',
			'utf8' => 'utf8_general_ci',
			'utf8mb4' => 'utf8mb4_general_ci',
			'ucs2' => 'ucs2_general_ci',
			'cp866' => 'cp866_general_ci',
			'keybcs2' => 'keybcs2_general_ci',
			'macce' => 'macce_general_ci',
			'macroman' => 'macroman_general_ci',
			'cp852' => 'cp852_general_ci',
			'latin7' => 'latin7_general_ci',
			'cp1251' => 'cp1251_general_ci',
			'cp1256' => 'cp1256_general_ci',
			'cp1257' => 'cp1257_general_ci',
			'geostd8' => 'geostd8_general_ci',
			'cp932' => 'cp932_japanese_ci',
			'eucjpms' => 'eucjpms_japanese_ci',
		);
		if($collations[$charset]) {
			return $collations[$charset];
		}
		return false;
	}

	function build_create_table_collation() {
		if (!$this->db_encoding) {
			return '';
		}
		$collation = $this->fetch_charset_collation($this->db_encoding);
		if (!$collation) {
			return '';
		}
		return " CHARACTER SET {$this->db_encoding} COLLATE {$collation}";
	}

	function get_execution_time() {
		return get_execution_time();
	}

	function escape_binary($string) {
		return "X" . $this->escape_string(bin2hex($string));
	}

	function unescape_binary($string) {
		return $string;
	}
    
}
