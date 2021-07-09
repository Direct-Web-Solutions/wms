<?php
/**
 * 
 * index.php
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

// This is required to allow the system to access this page
define('ALLOW_ACCESS', 1);
define('CURRENT_SCRIPT', 'index.php');
include_once("global.php");

// Output your specific headers. This function can be modified
// from the /core/class_template.php file to add or remove some
// header controls. We have added generic ones for now, but for more
// strict content-security rules you may want to adjust or tweak this
$templates->create_headers();

// We will be taking control of our system by implementing a page object. This
// is a database level object consisting of all of the templates as a set that
// will be rendered on this page. If the object doesn't exist, well throw an
// error that it doesn't exist in the system
if ($templates->create_page_object('default_index')) {
    
    // We have loaded into our object, now we can inject variables into the
    // templated before they are rendered. You can preinject everything from
    // here and then render all of it bellow
    $templates->inject_variables("header", array("Index Page", "Description of this page"));
    
    // Now we will render the header template that we just injected into
    $templates->render("header");
    
    // You can add more templates to the page object and inject variables
    // using the above methods and then render them in this section or just print
    // text using standar php echos if you wish.
    echo "Welcome to your new website.\n";
    
    // We are done implementing the page, lets close with the footer template
    // and add the true variable so that it clarifies its the end of the page
    // and doesn't print a \n (new line) character.
    $templates->render("footer", true);
    
    // Finally, lets run the shutdown on WMS command and make sure everything
    // is free and closed, this is the last thing we will do on any page.
    $wms->close();

} else {
    echo "Unable to load the template set from the database.";
}
