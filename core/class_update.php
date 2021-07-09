<?php
/**
 * 
 * core/class_update.php
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

class UpdateTaskManager {
    
    public $version_information;
    public $needs_update = false;
    
    public function read() {
        global $db;
        $query = $db->simple_select("datacache", "title,cache", "title='update'");
		$cache = $db->fetch_array($query);
		if (!$cache['title']) {
			$data = false;
		} else {
			$data = array_values(unserialize($cache['cache']));
			$this->version_information = (object) $data[0];
		}
		if ($data !== false) {
			return $data;
		} else {
			return false;
		}
    }
    
    // default the version code to 2000 since this method was not created
    // until version code 2000 was released.
    public function check_for_new_version() {
        global $wms, $db;
        $ch = curl_init();
        $options = [
            CURLOPT_SSL_VERIFYPEER  =>  true,
            CURLOPT_RETURNTRANSFER  =>  true,
            CURLOPT_TIMEOUT         =>  30,
            CURLOPT_CONNECTTIMEOUT  =>  30,
            CURLOPT_URL             =>  "https://www.directwebsolutions.ca/wms/latest-version.json"
        ];
        curl_setopt_array($ch, $options);
        $data = json_decode(curl_exec($ch));
        curl_close($ch);
        if ($data) {
            $this->version_information->new_version = $data->currentversion;
            $this->version_information->new_versioncode = $data->revisioncode;
            $this->version_information->new_version_released_on = $data->releasedon;
            $this->version_information->new_version_minimum_dependency_code = $data->minrevision;
        } else {
            return false;
        }
        if ($wms->version_code != $this->version_information->new_versioncode) {
            $this->needs_update = true;
        }
        if ($this->version_information->currentversion != $this->version_information->new_version) {
            $this->needs_update = true;
        }
        $un_compiled = array("sid" => 1, "currentversion" => $this->version_information->currentversion, "lastchecked" => time());
        $compiled = serialize(array($un_compiled));
        $db->update_query("datacache", array("cache" => $compiled), "title='update'");
        return NULL;
    }
    
}
