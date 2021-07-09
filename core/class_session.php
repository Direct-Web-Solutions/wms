<?php
/**
 * 
 * core/class_session.php
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

class session {

	public $sid = 0;
	public $uid = 0;
	public $is_admin = false;
	public $ipaddress = '';
	public $packedip = '';
	public $useragent = '';
	public $is_spider = false;
	public $ignore_parameters = array(
		'post_key',
		'logoutkey'
	);

	function init()	{
		global $db, $wms, $spiders, $config;
		$this->ipaddress = get_ip();
		$this->packedip = sys_inet_pton($this->ipaddress);
		$this->useragent = $_SERVER['HTTP_USER_AGENT'];
		$spiders = $spiders->read("spiders");
        if (is_array($spiders))	{
            foreach ($spiders as $spider) {
				if (sys_strpos(sys_strtolower($this->useragent), sys_strtolower($spider['useragent'])) !== false) {
					$this->load_spider($spider['sid']);
				}
			}
        }
		if (isset($wms->cookies[$config->session['name']])) {
		    $session_id = $wms->cookies[$config->session['name']];
		    if (substr($session_id, 3, 1) !== '=') {
    		    if (isset($wms->cookies['userinfo'])) {
    		        if (isset($wms->cookies['loginattempts'])) {
    		            sys_unsetcookie('loginattempts');
    		        }
    		        $session_data = $db->prepared_select("sessions", "WHERE sid = ? AND destroy_me = ?", array($session_id, 0));
    		        $account_details = explode("_", $wms->cookies['userinfo'], 2);
    		        if ($session_data && $session_data['uid'] == $account_details[0]) {
    		            $this->sid = $session_id;
    		            $this->load_user($account_details[0], $account_details[1]);
    		        } else {
    		            if (isset($wms->cookies['userinfo'])) {
        		            sys_unsetcookie('userinfo');
        		        }
        		        $this->sid = $session_id;
    		            $this->load_guest();
    		        }
    		    } else {
    		        if (!$this->is_spider) {
    		            $this->sid = $session_id;
            			$this->load_guest();
            		}
    		    }
		    } else {
		        $this->load_guest();
		    }
		} else {
		    if (isset($wms->cookies['userinfo'])) {
		        sys_unsetcookie('userinfo');
		    }
		    $this->load_guest();
		}
	}

	function load_guest() {
		global $wms, $db;
		$time = CURRENT_TIME;
		$wms->user['usergroup'] = 0;
		$wms->user['username'] = 'Guest';
		$wms->user['uid'] = 0;
		sys_setcookie("lastvisit", $time);
		$wms->user['lastvisit'] = $time;
		if (!empty($this->sid)) {
		    $destroy = false;
		    $check_session = $db->prepared_select("sessions", "WHERE sid = ? AND uid = ? LIMIT 1", array($this->sid, $this->uid));
		    if ($check_session) {
			    if ($check_session['destroy_me'] > 0) {
			        $destroy = true;
			    }
		    }
		    $this->update_session($this->sid, $this->uid, $destroy);
		} else {
			$this->create_session();
		}
		return true;
	}

	function load_spider($spider_id) {
		global $wms, $db;
		$spider = $db->prepared_select("spiders", "WHERE sid = ?", array($spider_id));
        if ($spider) {
            $this->is_spider = true;
    		$time = CURRENT_TIME;
    		$wms->user['usergroup'] = $spider['usergroup'];
    		$wms->user['username'] = 'Crawler';
    		$wms->user['uid'] = 0;
    		if ($spider['lastvisit'] < CURRENT_TIME - 120) {
    			$updated_spider = array(
    				"lastvisit" => CURRENT_TIME
    			);
    			$db->update_query("spiders", $updated_spider, "sid='{$spider_id}'");
    		}
    		$this->sid = "bot=" . $spider_id;
    		$this->create_session();
        } else {
            $this->load_guest();
        }
	}
	
	function load_user($user_id, $loginkey = "") {
	    global $wms, $db;
	    $wms->user = $db->prepared_select("users", "WHERE uid = ? LIMIT 1", array($user_id));
	    if ($wms->user['usergroup'] >= 3) {
	        $this->is_admin = true;
	    } else {
	        $this->is_admin = false;
	    }
        if (empty($loginkey) || $loginkey !== $wms->user['login_key'] || !$wms->user['uid']) {
			unset($wms->user);
			$this->uid = 0;
			$this->load_guest();
			return false;
		}
		$check_session = $db->prepared_select("sessions", "WHERE sid = ? AND uid = ? LIMIT 1", array($this->sid, $user_id));
		if ($check_session) {
    		$this->uid = $wms->user['uid'];
    		$wms->user['logoutkey'] = md5($wms->user['login_key']);
    		if ($wms->user['lastip'] != $this->packedip && array_key_exists('lastip', $wms->user)) {
    			$lastip_add = ", lastip=" . $db->escape_binary($this->packedip);
    		} else {
    			$lastip_add = "";
    		}
    		if (!empty($this->sid)) {
        		$destroy = false;
    		    if ($check_session['destroy_me'] > 0) {
    		        $destroy = true;
    		    }
    		    $this->update_session($this->sid, $user_id, $destroy);
    		} else {
    			$this->create_session($wms->user['uid']);
    		}
		} else {
		    $this->update_session($this->sid, $user_id, true);
		}
		return true;
	}

	function update_session($sid, $uid = 0, $destroy = false) {
		global $db, $config;
		if (!$this->is_spider && sys_substr($sid, 0, 3) == "bot") {
		    sys_unsetcookie($config->session['name']);
		    return NULL;
		}
		$onlinedata['uid'] = $uid;
		$onlinedata['time'] = CURRENT_TIME;
		$onlinedata['location'] = $db->escape_string(sys_substr(get_current_location(false, $this->ignore_parameters), 0, 150));
		$sid = $db->escape_string($sid);
		if ($destroy) {
		    if (isset($wms->cookies['userinfo'])) {
                sys_unsetcookie('userinfo');
            }
            if (isset($wms->cookies[$config->session['name']])) {
                sys_unsetcookie($config->session['name']);
            }
			$db->delete_query("sessions", "sid='{$sid}'");
			unset($wms->user);
			$this->uid = 0;
			$this->sid = "";
			$this->load_guest();
			return false;
		}
		$db->update_query("sessions", $onlinedata, "sid='{$sid}'");
		if (0 === $db->rows_affected_on_last_query && 32 === sys_strlen($sid)) {
		    $onlinedata['sid'] = $sid;
		    $onlinedata['start_time'] = CURRENT_TIME;
    		$onlinedata['ip'] = $db->escape_binary($this->packedip);
    		$onlinedata['useragent'] = $db->escape_string(sys_substr($this->useragent, 0, 200));
		    $db->replace_query("sessions", $onlinedata, "sid", false);
		    $this->sid = $onlinedata['sid'];
		    $this->uid = $onlinedata['uid'];
		}
		$db->rows_affected_on_last_query = 0;
		return true;
	}

	function create_session($user_id = 0) {
		global $db, $config;
		if ($user_id > 0) {
			$db->delete_query("sessions", "uid='{$user_id}'");
			$onlinedata['uid'] = $user_id;
		} else if ($this->is_spider) {
			$db->delete_query("sessions", "sid='{$this->sid}'");
			$onlinedata['sid'] = $this->sid;
		} else {
			$onlinedata['uid'] = 0;
			$onlinedata['sid'] = md5(random_str(50));
		}
		$onlinedata['start_time'] = CURRENT_TIME;
		$onlinedata['time'] = CURRENT_TIME;
		$onlinedata['ip'] = $db->escape_binary($this->packedip);
		$onlinedata['location'] = $db->escape_string(substr(get_current_location(false, $this->ignore_parameters), 0, 150));
		$onlinedata['useragent'] = $db->escape_string(sys_substr($this->useragent, 0, 200));
		$db->replace_query("sessions", $onlinedata, "sid", false);
		$this->sid = $onlinedata['sid'];
		$this->uid = $onlinedata['uid'];
		sys_setcookie($config->session['name'], $this->sid, -1, true);
	}

}
