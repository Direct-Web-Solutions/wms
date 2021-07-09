<?php
/**
 * 
 * 500.php
 * WMS (Website Management System)
 *
 * @category    500 File
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

define('ALLOW_ACCESS', 1);
define('CURRENT_SCRIPT', '500.php');
include("global.php");
$templates->generate_error(500);
