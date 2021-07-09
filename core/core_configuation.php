<?php
/**
 * 
 * core/core_configuation.php
 * WMS (Website Management System)
 *
 * @category    configuration
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

// Show the access denied error
if (!defined("ALLOW_ACCESS")) {
	die("Direct access of this file is not allowed.<br /><br />Please make sure ALLOW_ACCESS is defined.");
}

class Configuration {

    public $settings = array(
        /**
         * Database
         *  Change your database settings here. This will allow you to connect to an
         *  sql database to store and load content and data from them.
         */
        'database' => array(
            'type' => 'mysqli',                             // Acceptible items: mysqli (more in the future)
            'table_prefix' => 'wms_',
            'info' => array(
                'database' => 'yourdatabase_db',
                'hostname' => 'localhost',
                'username' => 'yourdatabase_dbuser',
                'password' => 'asecuredatabasepassword',
                'encoding' => 'utf8'
            )
        ),
        
        /**
         * Basic Settings
         *  Change your website settings including URL, author, and basic information here
         */
        'general' => array (
            'support_email' => 'info@website.com',
            'base_url' => 'www.website.com',                // The base URL for the website, without path
            'use_cdn' => true,                              // Should we load everything from a cdn? can be pointed at assets directory
            'cdn_url' => 'cdn.website.com',                 // The CDN url to load all files from
            'force_ssl' => 'true',                          // Default: true
            'site_title' => 'Your Site',                    // The title to appear in the header
            'meta_name' =>  'Your Site',                    // The META name, search results use this on Google
            'meta_author' => 'Your Name',                   // The META author, usually company name goes here
            'app_title' => 'Your Site',                     // The title set by default if page is saved as a webapp
            'max_menu_cats_per_colum' => '2',               // Max menu categories per dropdown row
            'revision_code' => '08.07.21.v1',               // The current system revision build code, used for debug (d.m.y.v)
            'language' => 'english',                        // The system language - match case to language folder
            'use_nocache_headers' => true,                  // Should the system use nocache, private headers? default FALSE
            'storeLat' => "50.00000",
            'storeLong' => "-107.00000",
            'mileageRate' => "0.58",
            'store' => array (
                'enabled' => 'false',               // Is the store module installed / enabled
                'default_items_on_page' => '12',    // How many items to show by default on the store page
                'force_fancy_url' => 'false'        // Use the SEO url system (Default: false)
            ),
            'socials' => array (
                'facebook' => '',
                'twitter' => '',
                'google+' => '',
                'linkedin' => ''
            ),
        ),
        
        /**
         * Timezone Settings
         *  Set the time for your servers PHP configuration. This will set what unix time
         *  will be decoded as and what the server TIME is.
         */
        'time' => array (
            'default_timezone' => 'America/Swift_Current'    // The PHP approved timeslot code (Default: America/Swift_Current)
        ),
        
        /**
         * Compression Settings
         *  Set the variables for using GZIP compression settings
         */
        'gzip' => array(
            'enabled'   =>  TRUE,   // Enable or disable GZIP compression
            'level'     =>  4,      // Typically between 2-4 for optimal compression vs load time
        ),
        
        /**
         * Cookie Settings
         *  Set the variables for storing cookies on the system
         */
        'cookies' => array(
            'path'      =>  '/',                // The default path for the cookies
            'prefix'    =>  'wms_',             // Set a prefix for your cookies
            'domain'    =>  '.website.com',     // Set the domain the cookies apply to
            'samesite'  =>  'STRICT',           // Samesite attribute: strict, lax
            'secure'    =>  TRUE                // Use the secure cookie attribute
        ),
        
        /**
         * Admin Settings
         *  General settings for the admin panel
         */
        'admin' => array (
            'ap_directory' => 'panel'   // The folder for your admin control panel
        ),
        
        /**
         * Session
         *  Change your session name for the WMS system here. This will be reflected
         *  throughout all included files. This is important for third party integration
         */
        'session' => array (
            'name' => 'sid',        // Name of the session cookie (Default: wms_sid)
            'timeout' => '31536000' // Life of the session extended on page reload in seconds (Default: 31536000 - 1 year)
        ),
        
        /**
         * Security
         *  Set the password encryption type for the system as well as some cost parameters
         *  WARNING: this system will implement bcrypt or argon2 encryption
         *           over the md5 hashing algo some scripts will run.
         *  NOTICE:  higher cost methods are tougher to crack but will show
         *           performance impacts on your website. Test with your 
         *           server to find middle ground for speed and security.
         *  CAUTION: if you turn use_pepper off or on you will need to reset the passwords
         *           for those accounts that are using it or are not using it if they were
         *           created before the change as they will not be pre-encoded
         */
        'security' => array (
            'encryption_mode'       =>      'argon2id',     // Options: argon2i, argon2id, bcrypt (default: argon2id)
            'bcrypt_cost'           =>      '12',           // Options: Min 4 / Max 31 - Default 12 (and up recommended)
            'argon_cost'            =>      '16',           // Amount in KB of argon memory - Min is 3, 16 is default
            'argon_iterations'      =>      '3',            // Min 1, the more rounds the more secure but slower, default 3
            'argon_threads'         =>      '1',            // Min 1, how many threads to run
            'use_pepper'            =>      true,           // Should the system use a pepper on the password to secure it more?
            'pepper'                =>      'c1isvFdxMDdmjOlvxpecFw'    // Generate your own pepper string to encrypt your passwords
        ),

        /**
         * Shipment Settings
         *  If you have the store module enabled on your WMS, you may choose to ship items with
         *  Canada Post. To do so, enter your API credentials here
         */
        'shipping' => array (
            'enabled' => 'false',
            'local_pickup' => 'false',      // Does your store allow local pickup?
            'canadapost_userid' => '',      // User ID supplied by Canada Post API
            'canadapost_passkey' => '',     // Passkey for the API system
            'canadapost_apikey' => '',      // Private API key from Canada Post
            'canadapost_apiurl' => '',      // The API URL you want to call (Ex: https://ct.soa-gw.canadapost.ca/rs/ship/price)
            'your_postal_code' => '',       // Your Postal Code, no spaces
        ),

        /**
         * Career API Settings
         *  If you have the career module enabled on your WMS, you can configure it to talk
         *  to the Direct Web Solutions API system using this function
         */
        'career_plugin' => array (
            'enabled' => 'false',
            'api_token' => '',
            'allow_online_apps' => 'false'
        )
    );
    
    function __construct() {
        foreach ($this->settings as $key => $value) {
            $this->{$key} = $value;          
        }
    }

}
