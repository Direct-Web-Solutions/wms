<?php
/**
 * 
 * core/class_datahandler.php
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

class DataHandler {

	public $data = array();
	public $is_validated = false;
	public $errors = array();
	public $admin_override = false;
	public $method;
	public $language_prefix = '';

	function __construct($method = "insert") {
		if ($method != "update" && $method != "insert" && $method != "get" && $method != "delete") {
			die("A valid method was not supplied to the data handler.");
		}
		$this->method = $method;
	}

	function set_data($data) {
		if (!is_array($data)) {
			return false;
		}
		$this->data = $data;
		return true;
	}

	function set_error($error, $data = '') {
		$this->errors[$error] = array(
			"error_code" => $error,
			"data" => $data
		);
	}

	function get_errors() {
		return $this->errors;
	}

	function get_friendly_errors() {
		global $lang;
		if ($this->language_file) {
			$lang->load($this->language_file, true);
		}
		$errors = array();
		foreach ($this->errors as $error) {
			$lang_string = $this->language_prefix . '_' . $error['error_code'];
			if (!$lang->$lang_string) {
				$errors[] = $error['error_code'];
				continue;
			}
			if (!empty($error['data']) && !is_array($error['data'])) {
				$error['data'] = array($error['data']);
			}
			if (is_array($error['data'])) {
				array_unshift($error['data'], $lang->$lang_string);
				$errors[] = call_user_func_array(array($lang, "sprintf"), $error['data']);
			} else {
				$errors[] = $lang->$lang_string;
			}
		}
		return $errors;
	}

	function set_validated($validated = true) {
		$this->is_validated = $validated;
	}

	function get_validated() {
		if ($this->is_validated == true) {
			return true;
		} else {
			return false;
		}
	}

	function verify_yesno_option(&$options, $option, $default = 1) {
		if ($this->method == "insert" || array_key_exists($option, $options)) {
			if (isset($options[$option]) && $options[$option] != $default && $options[$option] != "") {
				if ($default == 1) {
					$options[$option] = 0;
				} else {
					$options[$option] = 1;
				}
			} else if (@array_key_exists($option, $options) && $options[$option] == '') {
				$options[$option] = 0;
			} else {
				$options[$option] = $default;
			}
		}
	}

}
