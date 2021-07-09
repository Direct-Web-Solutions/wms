<?php
/**
 * 
 * process.php
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

if (!defined("CURRENT_SCRIPT")) {
    define('CURRENT_SCRIPT', 'process.php');
}
if (!defined("ALLOW_ACCESS")) {
    define('ALLOW_ACCESS', 1);
}
include_once("global.php");
if (isset($_GET['action'])) {
    if ($_GET['action'] == "login") {
        if (isset($_POST['login']) && isset($_POST['username']) && isset($_POST['password'])) {
            $posted_username = trim($_POST['username']);
            $posted_password = $_POST['password'];
            if (isset($_POST['remember'])) {
                $posted_remember = FALSE;
                $posted_remember = filter_var($_POST['remember'], FILTER_VALIDATE_BOOLEAN);
            } else {
                $posted_remember = FALSE;
            }
            if($wms->user['uid'] != 0) {
                http_response_code(412);
                die("You are already logged in");
            }
            if (is_array($login_lockout = check_login_attempt_exceeded())) {
                http_response_code(412);
                die("You have exceeded login attempts");
            }
            $login_user = get_user_by_username($posted_username);
            if ($login_user) {
                $user['loginattempts'] = (int) $login_user['loginattempts'];
                $login_exceeded = check_login_attempt_exceeded($login_user['uid']);
                if ($login_exceeded) {
                    http_response_code(412);
                    die("You have exceeded login attempts");
                }
                $db->update_query("users", array('loginattempts' => 'loginattempts+1'), "uid='" . (int) $login_user['uid'] . "'", 1, true);
                $user['loginattempts'] = (int) $login_user['loginattempts'];
                if (validate_password($login_user['username'], $posted_password)) {
                    if ($posted_remember) {
                        sys_setcookie("remember", $login_user['username']);
                    } else {
                        if (isset($wms->cookies['remember'])) {
                            sys_unsetcookie('remember');
                        }
                    }
            		sys_setcookie($config->session['name'], $session->sid, -1, true);
            		$newsession = array(
            			"uid" => $login_user['uid'],
            		);
            		$db->update_query("sessions", $newsession, "sid = '{$session->sid}'");
            		$db->update_query("users", array("loginattempts" => 0, "loginlockoutexpiry" => 0), "uid = '{$login_user['uid']}'");
            		sys_setcookie("userinfo", $login_user['uid']."_".$login_user['login_key'], NULL, true, "lax");
            		http_response_code(200);
            		die();
                } else {
                    http_response_code(412);
                    die("Invalid username or password");
                }
            } else {
                http_response_code(412);
                die("Invalid username or password");
            }
        }
    }
    if ($_GET['action'] == "logout") {
        $db->update_query("sessions", array("uid" => 0, "user_data" => "SET_NULL"), "sid = '{$session->sid}'");
        if (isset($wms->cookies['userinfo'])) {
            sys_unsetcookie('userinfo');
        }
        header("location: /");
        die();
    }
    $templates->generate_error(403);
} else {
    $templates->generate_error(403);
}
