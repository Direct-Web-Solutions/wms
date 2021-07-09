<?php
/**
 * 
 * core/class_language.php
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

class Lang {
    
    public $path;
    public $language;
    public $fallback = 'english';
    public $settings;
    
    function set_path($path) {
		$this->path = $path;
	}
	
	function language_exists($language) {
		$language = preg_replace("#[^a-z0-9\-_]#i", "", $language);
		if (file_exists($this->path . "/" . $language . "/core_language.php")) {
			return true;
		} else {
			return false;
		}
	}
	
	function set_language($language = "") {
		global $config;
		$language = preg_replace("#[^a-z0-9\-_]#i", "", $language);
		if ($language == "") {
			$language = $config->general['language'];
		}
		if (!$this->language_exists($language)) {
			die("Language $language ($this->path/$language) is not installed");
		}
		$this->language = $language;
		require $this->path . "/" . $language . "/core_language.php";
		$this->settings = $langinfo;
	}
	
	function sprintf($string) {
		$arg_list = func_get_args();
		$num_args = count($arg_list);
		for ($i = 1; $i < $num_args; $i++) {
			$string = str_replace('{'.$i.'}', $arg_list[$i], $string);
		}
		return $string;
	}
	
	function parse($contents) {
		$contents = preg_replace_callback("#<lang:([a-zA-Z0-9_]+)>#", array($this, 'parse_replace'), $contents);
		return $contents;
	}

	function parse_replace($matches) {
		return $this->{$matches[1]};
	}

}
