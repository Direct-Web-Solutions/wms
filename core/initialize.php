<?php
/**
 * 
 * core/initialize.php
 * WMS (Website Management System)
 *
 * @category    core files
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

if (!defined("ALLOW_ACCESS")) {
    http_response_code(403);
	die("Direct access of this file is not allowed.");
}

if (!defined("ROOT_DIR")) {
	define("ROOT_DIR", dirname(dirname(__FILE__)) . "/");
}

if (!defined("CURRENT_SCRIPT")) {
	define("CURRENT_SCRIPT", 'undefined');
}

if (file_exists(ROOT_DIR . "core/core_configuration.php")) {
    require_once ROOT_DIR . "core/core_configuration.php";
	$config = new Configuration;
} else {
    die("Configuration file has been deleted or moved and the website engine cannot run.");
}

if (function_exists('date_default_timezone_set')) {
    if (isset($config->time['default_timezone'])) {
	    date_default_timezone_set($config->time['default_timezone']); 
    } else {
        if (!ini_get('date.timezone')) {
            date_default_timezone_set('GMT');
        }
    }
}

define("CURRENT_TIME", time());

if (file_exists(ROOT_DIR . "core/install.lock")) {
    die("The system has not been installed.");
}

require_once ROOT_DIR . "core/class_errorhandler.php";
$error_handler = new errorHandler();
$error_handler->force_display_errors = TRUE;

if (!function_exists('json_encode') || !function_exists('json_decode')) {
	require_once ROOT_DIR . "core/core_json.php";
}

require_once ROOT_DIR . "core/class_timer.php";
$primary_timer = new timer();

require_once ROOT_DIR . "core/class_function.php";
require_once ROOT_DIR . "core/class_core.php";
$wms = new WMS;

if (empty($config->admin['ap_directory'])) {
	$config->admin['ap_directory'] = "panel";
}

require_once ROOT_DIR . "core/class_datahandler.php";
require_once ROOT_DIR . "core/db_base.php";
require_once ROOT_DIR . "core/db_pdodriver.php";
require_once ROOT_DIR . "core/db_" . $config->database['type'] . ".php";

// Once we add more database support in v2.1.0 we will change this section
// to add selection and initialize new class types by selected dB type
$db = new DB_MySQLi;

if (!extension_loaded($db->engine)) {
	$wms->trigger_generic_error("sql_load_error");
}

require_once ROOT_DIR . "core/class_template.php";
$templates = new templates;

define("TABLE_PREFIX", $config->database['table_prefix']);
$db->connect($config->database['info']);
$db->set_table_prefix(TABLE_PREFIX);
$db->type = $config->database['type'];

require_once ROOT_DIR . "core/class_spiders.php";
$spiders = new Spiders;

require_once ROOT_DIR . "core/class_language.php";
$lang = new Lang;
$lang->set_path(ROOT_DIR . "core/language");
$lang->set_language($config->general['language']);

$wms->parse_cookies();
$wms->asset_url = $wms->get_asset_url();

$date_formats = array(
	1 => "m-d-Y",
	2 => "m-d-y",
	3 => "m.d.Y",
	4 => "m.d.y",
	5 => "d-m-Y",
	6 => "d-m-y",
	7 => "d.m.Y",
	8 => "d.m.y",
	9 => "F jS, Y",
	10 => "l, F jS, Y",
	11 => "jS F, Y",
	12 => "l, jS F, Y",
	13 => "Y-m-d"
);

$time_formats = array(
	1 => "h:i a",
	2 => "h:i A",
	3 => "H:i"
);
