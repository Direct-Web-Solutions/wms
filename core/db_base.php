<?php
/**
 * 
 * core/db_base.php
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

interface DB_Base {

	function connect($config);
	function query($string, $hide_errors=0, $write_query=0);
	function write_query($query, $hide_errors=0);
	function fetch_array($query, $resulttype=1);
	function fetch_field($query, $field, $row=false);
	function data_seek($query, $row);
	function num_rows($query);
	function insert_id();
	function close();
	function error_number();
	function error_string();
	function error($string="");
	function affected_rows();
	function num_fields($query);
	function list_tables($database, $prefix='');
	function table_exists($table);
	function field_exists($field, $table);
	function shutdown_query($query, $name='');
	function simple_select($table, $fields="*", $conditions="", $options=array());
	function insert_query($table, $array);
	function insert_query_multiple($table, $array);
	function update_query($table, $array, $where="", $limit="", $no_quote=false);
	function delete_query($table, $where="", $limit="");
	function escape_string($string);
	function free_result($query);
	function escape_string_like($string);
	function get_version();
	function optimize_table($table);
	function analyze_table($table);
	function show_create_table($table);
	function show_fields_from($table);
	function is_fulltext($table, $index="");
	function supports_fulltext($table);
	function index_exists($table, $index);
	function supports_fulltext_boolean($table);
	function create_fulltext_index($table, $column, $name="");
	function drop_index($table, $name);
	function drop_table($table, $hard=false, $table_prefix=true);
	function rename_table($old_table, $new_table, $table_prefix=true);
	function replace_query($table, $replacements=array(), $default_field="", $insert_id=true);
	function drop_column($table, $column);
	function add_column($table, $column, $definition);
	function modify_column($table, $column, $new_definition, $new_not_null=false, $new_default_value=false);
	function rename_column($table, $old_column, $new_column, $new_definition, $new_not_null=false, $new_default_value=false);
	function set_table_prefix($prefix);
	function fetch_size($table='');
	function fetch_db_charsets();
	function fetch_charset_collation($charset);
	function build_create_table_collation();
	function get_execution_time();
	function escape_binary($string);
	function unescape_binary($string);
	
}
