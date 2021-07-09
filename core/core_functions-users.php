<?php
/**
 * 
 * core/core_functions-users.php
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

function user_exists($uid) {
	global $db;
	$query = $db->simple_select("users", "COUNT(*) as user", "uid='" . (int) $uid . "'", array('limit' => 1));
	if ($db->fetch_field($query, 'user') == 1) {
		return true;
	} else {
		return false;
	}
}

function username_exists($username) {
	return (bool) get_user_by_username($username, true);
}

function get_user_by_username($username, $return_bool = false) {
    global $db;
    $username = sys_strtolower($username);
    $query = $db->prepared_select("users", "WHERE username = ? OR email = ? LIMIT 1", array($username, $username));
    if ($return_bool) {
        if ($query) {
            return true;
        } else {
            return false;
        }
    }
    return $query;
}

function logout($regenerate_login_key = true) {
    global $wms, $db, $session;
    $user_id = $wms->user['uid'];
    $session = $session->sid;
    if (isset($wms->cookies['userinfo'])) {
        sys_unsetcookie('userinfo');
    }
    if (isset($wms->cookies['sid'])) {
        sys_unsetcookie('sid');
    }
    if (isset($wms->cookies['loginattempts'])) {
        sys_unsetcookie('loginattempts');
    }
    $db->delete_query("sessions", "sid='{$session}'");
    if ($regenerate_login_key) {
        update_loginkey($user_id);
    }
    $wms->user = NULL;
}

function validate_password($username, $password) {
	global $db, $config;
	$username = sys_strtolower($username);
	$user = $db->prepared_select("users", "WHERE username = ? OR email = ? LIMIT 1", array($username, $username));
    if (isset($config->security['use_pepper']) && $config->security['use_pepper']) {
        $password = hash_hmac("sha256", $password, $config->security['pepper']);
    }
	if (!$user["login_key"]) {
		$login_key = generate_loginkey();
		$sql_array = array(
			"login_key" => $login_key
		);
		$db->update_query("users", $sql_array, "uid = " . $user['uid']);
	}
	if (password_verify($password, $user['password'])) {
		return true;
	} else {
		return false;
	}
}

function check_login_attempt_exceeded($uid = 0, $fatal = true) {
	global $wms, $db, $lang;
	$attempts = array();
	$uid = (int) $uid;
	$now = CURRENT_TIME;
	if ($uid > 0) {
		$query = $db->simple_select("users", "loginattempts, loginlockoutexpiry", "uid='{$uid}'", 1);
		$attempts = $db->fetch_array($query);
		if ($attempts['loginattempts'] <= 0) {
			return false;
		}
	} else if (!empty($wms->cookies['lockoutexpiry']) && $wms->cookies['lockoutexpiry'] > $now) {
		if ($fatal) {
			$secsleft = (int) ($wms->cookies['lockoutexpiry'] - $now);
			$hoursleft = floor($secsleft / 3600);
			$minsleft = floor(($secsleft / 60) % 60);
			$secsleft = floor($secsleft % 60);
			return array($hoursleft, $minsleft, $secsleft);
		}
		return true;
	}
	if (isset($attempts['loginattempts']) && $attempts['loginattempts'] >= 5) {
		if ($attempts['loginlockoutexpiry'] == 0) {
			$attempts['loginlockoutexpiry'] = $now + ((int) 1000 * 60);
			sys_setcookie('lockoutexpiry', $attempts['loginlockoutexpiry']);
			$db->update_query("users", array("loginlockoutexpiry" => $attempts['loginlockoutexpiry']), "uid='{$uid}'");
		}
		if (empty($wms->cookies['lockoutexpiry'])) {
			$failedtime = $attempts['loginlockoutexpiry'];
		} else {
			$failedtime = $wms->cookies['lockoutexpiry'];
		}
		if ($attempts['loginlockoutexpiry'] > $now) {
			if ($fatal) {
				$secsleft = (int)($attempts['loginlockoutexpiry'] - $now);
				$hoursleft = floor($secsleft / 3600);
				$minsleft = floor(($secsleft / 60) % 60);
				$secsleft = floor($secsleft % 60);
				return array($hoursleft, $minsleft, $secsleft);
			}
			return true;
		} else {
			if ($uid > 0) {
				$db->update_query("users", array("loginattempts" => 0, "loginlockoutexpiry" => 0), "uid='{$uid}'");
			}
			sys_unsetcookie('lockoutexpiry');
			return false;
		}
	}
	return false;
}

function create_password($plain_text, $encrypt_overide = NULL) {
    global $config;
    if (isset($config->security['use_pepper']) && $config->security['use_pepper']) {
        $encoded_password = hash_hmac("sha256", $plain_text, $config->security['pepper']);
    } else {
        $encoded_password = $plain_text;
    }
    if (isset($config->security['encryption_mode'])) {
        $encryption_mode = sys_strtolower($config->security['encryption_mode']);
        $bcrypt_cost = 12;
        $argon_cost = 16;
        $argon_iterations = 3;
        $argon_threads = 1;
        if (isset($config->security['bcrypt_cost']) && is_int($config->security['bcrypt_cost'])) { $bcrypt_cost = (int) $config->security['bcrypt_cost']; }
        if (isset($config->security['argon_cost']) && is_int($config->security['argon_cost'])) { $argon_cost = (int) $config->security['argon_cost']; }
        if (isset($config->security['argon_iterations']) && is_int($config->security['argon_iterations'])) { $argon_iterations = (int) $config->security['argon_iterations']; }
        if (isset($config->security['argon_threads']) && is_int($config->security['argon_threads'])) { $argon_threads = (int) $config->security['argon_threads']; }
        if (isset($encrypt_overide)) {
            $encryption_mode = sys_strtolower($encrypt_overide);
        }
        switch ($encryption_mode) {
            case "argon2id":
                $options = [
                    'memory_cost' => $argon_cost,
                    'time_cost' => $argon_iterations, 
                    'threads' => $argon_threads
                ];
                $hashed_info = password_hash($encoded_password, PASSWORD_ARGON2ID, $options);
                break;
            case "argon2i":
                $options = [
                    'memory_cost' => $argon_cost,
                    'time_cost' => $argon_iterations, 
                    'threads' => $argon_threads
                ];
                $hashed_info = password_hash($encoded_password, PASSWORD_ARGON2I, $options);
                break;
            case "bcrypt":
                $options = [
                    'cost' => $bcrypt_cost,
                ];
                $hashed_info = password_hash($encoded_password, PASSWORD_BCRYPT, $options);
                break;
            default:
                $hashed_info = password_hash($encoded_password, PASSWORD_DEFAULT);
                break;
        }
        $hash = $hashed_info;
    } else {
        $hash = password_hash($encoded_password, PASSWORD_DEFAULT);
    }
    if ($hash) {
        return $hash;
    } else {
        die ("Error in the password encryption method employed in the system. Check your configuration settings.");
    }
}

function generate_loginkey() {
	return random_str(50);
}

function update_loginkey($uid) {
	global $db;
	$loginkey = generate_loginkey();
	$sql_array = array(
		"login_key" => $loginkey
	);
	$db->update_query("users", $sql_array, "uid='{$uid}'");
	return $loginkey;
}
