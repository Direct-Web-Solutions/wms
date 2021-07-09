<?php
/**
 * 
 * panel/index.php
 * WMS (Website Management System)
 *
 * @category    Admin Panel
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
define('CURRENT_SCRIPT', 'panel/index.php');
define('ADMIN_PANEL', true);

// Import our base dependencies and initiate the global header variables
include_once dirname(dirname(__FILE__)) . "/global.php";
include_once dirname(dirname(__FILE__)) . "/core/core_functions-admin.php";
$templates->create_headers(true, true);

// If they are signed in and are an admin, we can show the admin panel
if ($session->is_admin) {

    // This is our update class, we will check current version on file,
    // the store config variable, and then check our update service for
    // what the current version should be in case there is an update.
    include_once dirname(dirname(__FILE__)) . "/core/class_update.php";
    $updateTaskManager = new UpdateTaskManager;
    $updateTaskManager->read();

    // If the version on cache and the version on core are not the same we
    // have an issue and need to deal with it
    if (!$wms->version == $updateTaskManager->version_information->currentversion) {
        die("<b>Core error</b>:: <br>There is a major conflict with the current system version and the database version on file.<br>You should never see this error unless things have gone horribly sideways!");
    }

    // If it's been more than 5 days since the last check of the system to
    // see if it's up to date, lets go ahead and run the check for a new
    // edition of the WMS script
    if ((CURRENT_TIME - $updateTaskManager->version_information->lastchecked) >= 432000) {
        $updateTaskManager->check_for_new_version();
    }
    
    if (isset($_GET['page']) || isset($_GET['pid'])) {
        
        // Use this format to add additional pages to the admin panel. There are
        // better ways to add and check for scripts and such but we will manually
        // add them like this for this version of the project and maybe do some
        // dynamic loading of page assets and such in version 3.0
        if (isset($_GET['page']) && $_GET['page'] == "templates" || isset($_GET['pid']) && $_GET['pid'] == "1") {
            
            
            
            if (isset($_GET['template_id']) && is_numeric($_GET['template_id'])) {
                $template_id = (int) $_GET['template_id'];
                switch ($template_id) {
                    case "1":
                        $templates->add_cached_template("create_new_page_object", "HTML", "static/admin/templates");
                        $templates->inject_variables("create_new_page_object", array(0, ""));
                        $templates->render("create_new_page_object");
                        break;
                    case "2":
                        echo "Template ID 2";
                        break;
                    case "3":
                        echo "Template ID 3";
                        break;
                    case "4":
                        echo "Template ID 4";
                        break;
                    default:
                        echo "Error loading template.";
                        break;
                }
                die();
            }
            
            // 1) :::: TEMPLATES SCRIPT
            if ($templates->create_page_object('panel_core')) {
                
                // Add your own template here and inject into it
                $templates->add_cached_template("main", "HTML", "static/admin/templates");
                $page_variables = array($templates->generate_acp_menu(2), "There are no templates objects at this time.", "There are no templates at this time.", "There are no templates at this time.");
                $templates->inject_variables("main", $page_variables);
                
                $templates->inject_variables("header", $templates->load_page_information("panel_templates"));
                $templates->inject_variables("footer", array(date("Y-m-d, g:i A", CURRENT_TIME)));
                $templates->render("header");
                $templates->render("navigation");
                
                // Do your logic here since the core doesnt include any of that nonsense
                // You need to cache your templates here and inject variables as well or
                // just render them as needed.
                $templates->render("main");
                
                $templates->render("footer");
                $templates->render("close", true);
                $wms->close();
            } else {
                echo "Unable to load the template set from the database.";
            }
            
        } else {
            if ($templates->create_page_object('panel_error')) {
                $templates->inject_variables("header", $templates->load_page_information());
                $templates->inject_variables("not_found", $templates->generate_acp_menu(0));
                $templates->inject_variables("footer", array(date("Y-m-d, g:i A", CURRENT_TIME)));
                $templates->render("header");
                $templates->render("navigation");
                $templates->render("not_found");
                $templates->render("footer");
                $templates->render("close", true);
                $wms->close();
            } else {
                echo "Unable to load the template set from the database.";
            }
        }
    } else {
        // Output the admin panel index page object
        if ($templates->create_page_object('panel_index')) {
            $templates->inject_variables("header", $templates->load_page_information("panel_dashboard"));
            $templates->inject_variables("main", $templates->generate_acp_menu(1));
            $templates->inject_variables("footer", array(date("Y-m-d, g:i A", CURRENT_TIME)));
            $templates->render("header");
            if ($updateTaskManager->needs_update) {
                $templates->add_cached_template("update_banner");
                $templates->inject_variables("update_banner", array($updateTaskManager->version_information->currentversion, $wms->version_code, $updateTaskManager->version_information->new_version, $updateTaskManager->version_information->new_versioncode));
                $templates->render("update_banner");
            }
            $templates->render("navigation");
            $templates->render("main");
            $templates->render("footer");
            $templates->render("close", true);
            $wms->close();
        } else {
            echo "Unable to load the template set from the database.";
        }
    }
} else {
    // They are not signed in as an admin so show login or give them
    // an error that they don't have permissions to view the page.
    if ($wms->user['usergroup'] == 0) {
        admin_login();
    } else {
        admin_no_perms();
    }
}
