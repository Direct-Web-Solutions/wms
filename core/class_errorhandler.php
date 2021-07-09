<?php
/**
 * 
 * core/class_errorhandler.php
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

define("MANUAL_WARNINGS", 0);

if (!defined("E_RECOVERABLE_ERROR")) {
	define("E_RECOVERABLE_ERROR", 4096);
}

if (!defined("E_DEPRECATED")) {
	define("E_DEPRECATED", 8192);
}

if (!defined("E_USER_DEPRECATED")) {
	define("E_USER_DEPRECATED", 16384);
}

define("WMS_SQL", 20);
define("WMS_TEMPLATE", 30);
define("WMS_GENERAL", 40);

class errorHandler {
    
    public $custom_error_types = array(
		WMS_SQL,
		WMS_TEMPLATE,
		WMS_GENERAL
	);

    public $error_types = array(
        E_ERROR					=> 'Error',
        E_WARNING				=> 'Warning',
        E_PARSE					=> 'Parsing Error',
        E_NOTICE				=> 'Notice',
        E_CORE_ERROR			=> 'Core Error',
        E_CORE_WARNING			=> 'Core Warning',
        E_COMPILE_ERROR			=> 'Compile Error',
        E_COMPILE_WARNING		=> 'Compile Warning',
        E_DEPRECATED			=> 'Deprecated Warning',
        E_USER_ERROR			=> 'User Error',
        E_USER_WARNING			=> 'User Warning',
        E_USER_NOTICE			=> 'User Notice',
        E_USER_DEPRECATED	 	=> 'User Deprecated Warning',
        E_STRICT				=> 'Runtime Notice',
        E_RECOVERABLE_ERROR		=> 'Catchable Fatal Error',
        WMS_SQL 				=> 'WMS SQL Error',
        WMS_TEMPLATE			=> 'WMS Template Error',
        WMS_GENERAL 			=> 'WMS Error'
    );
    
    public $warnings = "";
    public $has_errors = false;
    public $force_display_errors = false;
    
    public $ignore_types = array(
		E_DEPRECATED,
		E_NOTICE,
		E_USER_NOTICE,
		E_STRICT
	);
	
	function __construct() {
		$e_type = E_ALL;
		foreach ($this->ignore_types as $data) {
			$e_type = $e_type & ~$data;
		}
		error_reporting($e_type);
		set_error_handler(array(&$this, "error"), $e_type);
	}
	
	function show_warnings() {
		global $lang, $templates;
		if (empty($this->warnings)) {
			return FALSE;
		}
		if (MANUAL_WARNINGS) {
			echo $this->warnings."<br />";
		}
		if (!$lang->warnings) {
			$lang->warnings = "The following warnings occurred:";
		}
		$template_exists = FALSE;
		if (!is_object($templates) || !method_exists($templates, 'get')) {
			if (@file_exists(ROOT_DIR . "core/class_template.php")) {
				@require_once ROOT_DIR . "core/class_template.php";
				$templates = new templates;
				$template_exists = TRUE;
			}
		} else {
			$template_exists = TRUE;
		}
		$warning = '';
		if ($template_exists == TRUE) {
			eval("\$warning = \"" . $templates->get_template("php_warnings") . "\";");
		}
		return $warning;
	}
    
    function trigger($message="", $type = E_USER_ERROR) {
		global $lang;
		if (!$message) {
			$message = $lang->unknown_user_trigger;
		}
		if (in_array($type, $this->custom_error_types)) {
			$this->error($type, $message);
		} else {
			trigger_error($message, $type);
		}
	}
	
	function error($type, $message, $file = NULL, $line = 0, $allow_output = TRUE) {
		if (error_reporting() == 0) {
			return TRUE;
		}
		if (in_array($type, $this->ignore_types)) {
			return TRUE;
		}
		$file = str_replace(ROOT_DIR, "", $file);
		$this->has_errors = true;
        $this->log_error($type, $message, $file, $line);
        if ($allow_output === true) {
            if ($type == WMS_SQL) {
                $this->output_error($type, $message, $file, $line);
            } else {
                if (sys_strpos(sys_strtolower($this->error_types[$type]), 'warning') === false) {
					$this->output_error($type, $message, $file, $line);
				} else {
				    echo "There was an error loading content. Error Details: <br><pre>";
            	    print_r($message);
            	    echo "</pre><br>Found on line (" . $line . ") in file: " . $file ."<br>\n";
				}
            }
        }
        return true;
	}
	
	function log_error($type, $message, $file, $line) {
	    if ($type == WMS_SQL) {
			$message = "SQL Error: {$message['error_no']} - {$message['error']}\nQuery: {$message['query']}";
		}
		$message = str_replace('<?', '< ?', $message);
        $error_data = "<error>\n";
		$error_data .= "\t<dateline>" . CURRENT_TIME . "</dateline>\n";
		$error_data .= "\t<script>" . $file . "</script>\n";
		$error_data .= "\t<line>" . $line . "</line>\n";
		$error_data .= "\t<type>" . $type . "</type>\n";
		$error_data .= "\t<friendly_type>" . $this->error_types[$type] . "</friendly_type>\n";
		$error_data .= "\t<message>" . $message . "</message>\n";
		$error_data .= "</error>\n\n";
		@error_log($error_data, 0);
	}
	
	function output_error($type, $message, $file, $line) {
	    // TODO: Make a proper error output that will display an error page instead of the broken code.
	    echo "There was an error loading content. Error Details: <br><pre>";
	    print_r($message);
	    echo "</pre><br>Found on line (" . $line . ") in file: " . $file ."<br>\n";
	    exit(0);
	}
	
}
