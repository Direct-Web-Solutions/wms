<?php
/**
 * 
 * core/class_spiders.php
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

class Spiders {

    public $crawlers = array();
    
    public function read($spider_id) {
        global $db;
        if (isset($this->crawlers[$spider_id])) {
            return $this->crawlers[$spider_id];
        }
        $query = $db->simple_select("datacache", "title,cache", "title='spiders'");
        $crawler_cache = $db->fetch_array($query);
        if (!$crawler_cache['title']) {
          $crawler_data = false;
        } else {
          $crawler_data = unserialize($crawler_cache['cache']);
        }
        $this->crawlers[] = $crawler_data;
        if ($crawler_data !== false) {
          return $crawler_data;
        } else {
          return false;
        }
    }

}
