<?php
/**
 * 
 * core/class_core.php
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

class WMS {
    
    public $version = "2.0.0";
    public $version_code = 2000;
    public $cwd = ".";
    public $input = array();
    public $cookies = array();
    public $user = array();
	public $usergroup = array();
	public $config = array();
	public $request_method = "";
	public $cache;
	public $asset_url = null;
	public $magicquotes = 0;
	
	const INPUT_STRING = 0;
	const INPUT_INT = 1;
	const INPUT_ARRAY = 2;
	const INPUT_FLOAT = 3;
	const INPUT_BOOL = 4;
	
	public $ignore_clean_variables = array();
	public $clean_variables = array(
		"int" => array(
			"tid", "pid", "uid",
			"eid", "pmid", "fid",
			"aid", "rid", "sid",
			"vid", "cid", "bid",
			"hid", "gid", "mid",
			"wid", "lid", "iid",
			"did", "qid", "id"
		),
		"pos" => array(
			"page", "perpage"
		),
		"a-z" => array(
			"sortby", "order"
		)
	);
	
	function __construct() {
	    $protected = array("_GET", "_POST", "_SERVER", "_COOKIE", "_FILES", "_ENV", "GLOBALS");
	    foreach ($protected as $var) {
			if (isset($_POST[$var]) || isset($_GET[$var]) || isset($_COOKIE[$var]) || isset($_FILES[$var])) {
				die("Hacking attempt");
			}
		}
		if (defined("IGNORE_CLEAN_VARS")) {
			if (!is_array(IGNORE_CLEAN_VARS)) {
				$this->ignore_clean_variables = array(IGNORE_CLEAN_VARS);
			} else {
				$this->ignore_clean_variables = IGNORE_CLEAN_VARS;
			}
		}
		if (version_compare(PHP_VERSION, '6.0', '<')) {
			if (@get_magic_quotes_gpc()) {
				$this->magicquotes = 1;
				$this->strip_slashes_array($_POST);
				$this->strip_slashes_array($_GET);
				$this->strip_slashes_array($_COOKIE);
			}
			@set_magic_quotes_runtime(0);
			@ini_set("magic_quotes_gpc", 0);
			@ini_set("magic_quotes_runtime", 0);
		}
		$this->parse_incoming($_GET);
		$this->parse_incoming($_POST);
		if ($_SERVER['REQUEST_METHOD'] == "POST") {
			$this->request_method = "post";
		} else if ($_SERVER['REQUEST_METHOD'] == "GET") {
			$this->request_method = "get";
		}
		if (@ini_get("register_globals") == 1) {
			$this->unset_globals($_POST);
			$this->unset_globals($_GET);
			$this->unset_globals($_FILES);
			$this->unset_globals($_COOKIE);
		}
		$this->clean_input();
		if (isset($this->input['intcheck']) && $this->input['intcheck'] == 1) {
			die("&#077;&#089;&#066;&#066;");
		}
	}
	
	function parse_incoming($array) {
		if (!is_array($array)) {
			return;
		}
		foreach ($array as $key => $val) {
			$this->input[$key] = $val;
		}
	}
	
	function parse_cookies() {
	    global $config;
		if (!is_array($_COOKIE)) {
			return;
		}
		$prefix_length = strlen($config->cookies['prefix']);
		foreach ($_COOKIE as $key => $val) {
			if ($prefix_length && substr($key, 0, $prefix_length) == $config->cookies['prefix']) {
				$key = substr($key, $prefix_length);
				if (isset($this->cookies[$key])) {
					unset($this->cookies[$key]);
				}
			}
			if (empty($this->cookies[$key])) {
				$this->cookies[$key] = $val;
			}
		}
	}
	
	function strip_slashes_array(&$array) {
		foreach ($array as $key => $val) {
			if (is_array($array[$key])) {
				$this->strip_slashes_array($array[$key]);
			} else {
				$array[$key] = stripslashes($array[$key]);
			}
		}
	}
	
	function unset_globals($array) {
		if (!is_array($array)) {
			return;
		}
		foreach(array_keys($array) as $key) {
			unset($GLOBALS[$key]);
			unset($GLOBALS[$key]);
		}
	}
	
	function clean_input() {
		foreach ($this->clean_variables as $type => $variables) {
			foreach ($variables as $var) {
				if (in_array($var, $this->ignore_clean_variables)) {
					continue;
				}
				if (isset($this->input[$var])) {
					switch($type) {
						case "int":
							$this->input[$var] = $this->get_input($var, WMS::INPUT_INT);
							break;
						case "a-z":
							$this->input[$var] = preg_replace("#[^a-z\.\-_]#i", "", $this->get_input($var));
							break;
						case "pos":
							if (($this->input[$var] < 0 && $var != "page") || ($var == "page" && $this->input[$var] != "last" && $this->input[$var] < 0))
								$this->input[$var] = 0;
							break;
					}
				}
			}
		}
	}
	
	function get_input($name, $type = WMS::INPUT_STRING) {
	    switch($type) {
	        case WMS::INPUT_ARRAY:
				if (!isset($this->input[$name]) || !is_array($this->input[$name])) {
					return array();
				}
				return $this->input[$name];
			case WMS::INPUT_INT:
				if (!isset($this->input[$name]) || !is_numeric($this->input[$name])) {
					return 0;
				}
				return (int) $this->input[$name];
			case WMS::INPUT_FLOAT:
				if (!isset($this->input[$name]) || !is_numeric($this->input[$name])) {
					return 0.0;
				}
				return (float) $this->input[$name];
			case WMS::INPUT_BOOL:
				if (!isset($this->input[$name]) || !is_scalar($this->input[$name])) {
					return false;
				}
				return (bool) $this->input[$name];
			default:
				if (!isset($this->input[$name]) || !is_scalar($this->input[$name])) {
					return '';
				}
				return $this->input[$name];
	    }
	}
	
	public function get_asset_url($path = '', $use_cdn = true) {
	    global $config;
		$path = (string) $path;
		$path = ltrim($path, '/');
		if (substr($path, 0, 4) != 'http') {
			if (substr($path, 0, 2) == './') {
				$path = substr($path, 2);
			}
			if ($use_cdn && $config->general['use_cdn'] && !empty($config->general['cdn_url'])) {
				$base_path = rtrim($config->general['cdn_url'], '/');
			} else {
				$base_path = rtrim($config->general['base_url'], '/');
			}
			$url = $base_path;
			if (!empty($path)) {
				$url = $base_path . '/' . $path;
			}
		} else {
			$url = $path;
		}
		return $url;
	}
	
	function trigger_generic_error($code) {
		global $error_handler;
		switch($code) {
			case "custom_error_code":
				$message = "You can set your own error codes to be called in the event they are required here.";
				$error_code = WMS_CUSTOM_ERROR;
				break;
			default:
				$message = "WMS has experienced an internal error.";
				$error_code = WMS_GENERAL;
		}
		$error_handler->trigger($message, $error_code);
	}
	
	function close() {
	    global $db, $templates;
	    unset($templates->cache);
	    if (is_resource($db)) {
	        $db->close();
	    }
	    die();
	}
	
	function __destruct() {
		if (function_exists("run_shutdown")) {
			run_shutdown();
		}
	}
	
}
