<?php
/**
 * 
 * core/core_functions-admin.php
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

function admin_login() {
    global $templates, $wms;
    if ($templates->create_page_object('admin_login')) {
        $templates->inject_variables("header", array("Control Panel &mdash; Login", "Login to access your sites data, settings, controls, and more."));
        $remembered_username = "";
        $remembered_toremember = "";
        if (isset($wms->cookies['remember']) && !empty(trim($wms->cookies['remember']))) {
            $remembered_username = htmlspecialchars_uni(trim(sys_strtolower($wms->cookies['remember'])));
            $remembered_toremember = "checked ";
        }
        $templates->inject_variables("login", array($remembered_username, $remembered_toremember));
        $templates->render("header");
        $templates->render("login");
        $templates->render("close", true);
        $wms->close();
    } else {
        die("<strong>CORE ERROR:</strong><br>The login templates have been removed from your template set.<br>You will need to manually rebuild them from file.");
    }
    return null;
}

function admin_no_perms() {
    global $templates, $wms;
    if ($templates->create_page_object('admin_error')) {
        $templates->inject_variables("header", array("Control Panel &mdash; Error", "You do not have permission to view this page."));
        $templates->render("header");
        $templates->render("error");
        $templates->render("close", true);
        $wms->close();
    } else {
        die("<strong>CORE ERROR:</strong><br>The login templates have been removed from your template set.<br>You will need to manually rebuild them from file.");
    }
    return null;
}
