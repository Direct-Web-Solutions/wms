<?php
/**
 * 
 * global.php
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
 
$working_directory = dirname(__FILE__);

if (!$working_directory) {
    $working_directory = '.';
}

require_once $working_directory . "/core/initialize.php";
$current_page = sys_strtolower(basename(CURRENT_SCRIPT));
require_once ROOT_DIR . "core/core_functions-users.php";
require_once ROOT_DIR . "core/class_session.php";
$session = new session;
$session->init();
$wms->session = &$session;
