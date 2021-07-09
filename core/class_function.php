<?php
/**
 * 
 * core/class_function.php
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

function get_execution_time() {
	static $time_start;
	$time = microtime(true);
	if (!$time_start) {
		$time_start = $time;
		return;
	} else {
		$total = $time-$time_start;
		if($total < 0) $total = 0;
		$time_start = 0;
		return $total;
	}
}

function format_time_duration($time) {
	global $lang;
	if (!is_numeric($time)) {
		return "narp.";
	}
	if (round(1000000 * $time, 2) < 1000) {
		$time = number_format(round(1000000 * $time, 2))." μs";
	} else if (round(1000000 * $time, 2) >= 1000 && round(1000000 * $time, 2) < 1000000) {
		$time = number_format(round((1000 * $time), 2))." ms";
	} else {
		$time = round($time, 3)." seconds";
	}
	return $time;
}

function dec_to_utf8($src) {
	$dest = '';
	if ($src < 0) {
		return false;
	} else if ($src <= 0x007f) {
		$dest .= chr($src);
	} else if($src <= 0x07ff) {
		$dest .= chr(0xc0 | ($src >> 6));
		$dest .= chr(0x80 | ($src & 0x003f));
	} else if ($src <= 0xffff) {
		$dest .= chr(0xe0 | ($src >> 12));
		$dest .= chr(0x80 | (($src >> 6) & 0x003f));
		$dest .= chr(0x80 | ($src & 0x003f));
	} else if ($src <= 0x10ffff) {
		$dest .= chr(0xf0 | ($src >> 18));
		$dest .= chr(0x80 | (($src >> 12) & 0x3f));
		$dest .= chr(0x80 | (($src >> 6) & 0x3f));
		$dest .= chr(0x80 | ($src & 0x3f));
	} else {
		return false;
	}
	return $dest;
}

function sys_strlen($string) {
	global $lang;
	$string = preg_replace("#&\#([0-9]+);#", "-", $string);
	if (strtolower($lang->settings['charset']) == "utf-8") {
		$string = str_replace(dec_to_utf8(8238), "", $string);
		$string = str_replace(dec_to_utf8(8237), "", $string);
		$string = str_replace(chr(0xCA), "", $string);
	}
	$string = trim($string);
	if (function_exists("mb_strlen")) {
		$string_length = mb_strlen($string);
	} else {
		$string_length = strlen($string);
	}
	return $string_length;
}

function sys_substr($string, $start, $length = null, $handle_entities = false) {
	if ($handle_entities) {
		$string = unhtmlentities($string);
	}
	if (function_exists("mb_substr")) {
		if ($length != null) {
			$cut_string = mb_substr($string, $start, $length);
		} else {
			$cut_string = mb_substr($string, $start);
		}
	} else {
		if ($length != null) {
			$cut_string = substr($string, $start, $length);
		} else {
			$cut_string = substr($string, $start);
		}
	}
	if ($handle_entities) {
		$cut_string = htmlspecialchars_uni($cut_string);
	}
	return $cut_string;
}

function sys_strtolower($string) {
	if (function_exists("mb_strtolower")) {
		$string = mb_strtolower($string);
	} else {
		$string = strtolower($string);
	}
	return $string;
}

function sys_stripos($haystack, $needle, $offset = 0) {
	if ($needle == '') {
		return false;
	}
	if (function_exists("mb_stripos")) {
		$position = mb_stripos($haystack, $needle, $offset);
	} else {
		$position = stripos($haystack, $needle, $offset);
	}
	return $position;
}

function sys_strpos($haystack, $needle, $offset = 0) {
	if ($needle == '') {
		return false;
	}
	if (function_exists("mb_strpos")) {
		$position = mb_strpos($haystack, $needle, $offset);
	} else {
		$position = strpos($haystack, $needle, $offset);
	}
	return $position;
}

function sys_strtoupper($string) {
	if (function_exists("mb_strtoupper")) {
		$string = mb_strtoupper($string);
	} else {
		$string = strtoupper($string);
	}
	return $string;
}

function unhtmlentities($string) {
	$string = preg_replace_callback('~&#x([0-9a-f]+);~i', 'unichr_callback1', $string);
	$string = preg_replace_callback('~&#([0-9]+);~', 'unichr_callback2', $string);
	$trans_tbl = get_html_translation_table(HTML_ENTITIES);
	$trans_tbl = array_flip($trans_tbl);
	return strtr($string, $trans_tbl);
}

function unichr($c) {
	if ($c <= 0x7F) {
		return chr($c);
	} else if ($c <= 0x7FF) {
		return chr(0xC0 | $c >> 6) . chr(0x80 | $c & 0x3F);
	} else if ($c <= 0xFFFF) {
		return chr(0xE0 | $c >> 12) . chr(0x80 | $c >> 6 & 0x3F) . chr(0x80 | $c & 0x3F);
	} else if ($c <= 0x10FFFF) {
		return chr(0xF0 | $c >> 18) . chr(0x80 | $c >> 12 & 0x3F) . chr(0x80 | $c >> 6 & 0x3F) . chr(0x80 | $c & 0x3F);
	} else {
		return false;
	}
}

function unichr_callback1($matches) {
	return unichr(hexdec($matches[1]));
}

function unichr_callback2($matches) {
	return unichr($matches[1]);
}

function get_ip() {
	$ip = strtolower($_SERVER['REMOTE_ADDR']);
	$addresses = array();
	if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$addresses = explode(',', strtolower($_SERVER['HTTP_X_FORWARDED_FOR']));
	} elseif(isset($_SERVER['HTTP_X_REAL_IP'])) {
		$addresses = explode(',', strtolower($_SERVER['HTTP_X_REAL_IP']));
	}
	if (is_array($addresses)) {
		foreach ($addresses as $val) {
			$val = trim($val);
			if (sys_inet_ntop(sys_inet_pton($val)) == $val && !preg_match("#^(10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.|192\.168\.|fe80:|fe[c-f][0-f]:|f[c-d][0-f]{2}:)#", $val)) {
				$ip = $val;
				break;
			}
		}
	}
	if (!$ip) {
		if (isset($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = strtolower($_SERVER['HTTP_CLIENT_IP']);
		}
	}
	return $ip;
}

function sys_inet_ntop($ip) {
	if (function_exists('inet_ntop')) {
		return @inet_ntop($ip);
	} else {
		switch (strlen($ip)) {
			case 4:
				list(,$r) = unpack('N', $ip);
				return long2ip($r);
			case 16:
				$r = substr(chunk_split(bin2hex($ip), 4, ':'), 0, -1);
				$r = preg_replace(
					array('/(?::?\b0+\b:?){2,}/', '/\b0+([^0])/e'),
					array('::', '(int)"$1"?"$1":"0$1"'),
					$r);
				return $r;
		}
		return false;
	}
}

function sys_inet_pton($ip) {
	if (function_exists('inet_pton')) {
		return @inet_pton($ip);
	} else {
		$r = ip2long($ip);
		if ($r !== false && $r != -1) {
			return pack('N', $r);
		}
		$delim_count = substr_count($ip, ':');
		if ($delim_count < 1 || $delim_count > 7) {
			return false;
		}
		$r = explode(':', $ip);
		$rcount = count($r);
		if (($doub = array_search('', $r, 1)) !== false) {
			$length = (!$doub || $doub == $rcount - 1 ? 2 : 1);
			array_splice($r, $doub, $length, array_fill(0, 8 + $length - $rcount, 0));
		}
		$r = array_map('hexdec', $r);
		array_unshift($r, 'n*');
		$r = call_user_func_array('pack', $r);
		return $r;
	}
}

function get_ip_by_hostname($hostname) {
	$addresses = @gethostbynamel($hostname);
	if (!$addresses) {
		$result_set = @dns_get_record($hostname, DNS_A | DNS_AAAA);
		if ($result_set) {
			$addresses = array_column($result_set, 'ip');
		} else {
			return false;
		}
	}
	return $addresses;
}

function fetch_ip_range($ipaddress) {
	if (strpos($ipaddress, '*') !== false) {
		if (strpos($ipaddress, ':') !== false) {
			$upper = str_replace('*', 'ffff', $ipaddress);
			$lower = str_replace('*', '0', $ipaddress);
		} else {
			$ip_bits = count(explode('.', $ipaddress));
			if ($ip_bits < 4) {
				$replacement = str_repeat('.*', 4-$ip_bits);
				$ipaddress = substr_replace($ipaddress, $replacement, strrpos($ipaddress, '*') + 1, 0);
			}
			$upper = str_replace('*', '255', $ipaddress);
			$lower = str_replace('*', '0', $ipaddress);
		}
		$upper = sys_inet_pton($upper);
		$lower = sys_inet_pton($lower);
		if ($upper === false || $lower === false) {
			return false;
		}
		return array($lower, $upper);
	} else if (strpos($ipaddress, '/') !== false) {
		$ipaddress = explode('/', $ipaddress);
		$ip_address = $ipaddress[0];
		$ip_range = (int) $ipaddress[1];
		if (empty($ip_address) || empty($ip_range)) {
			return false;
		} else {
			$ip_address = sys_inet_pton($ip_address);
			if (!$ip_address) {
				return false;
			}
		}
		$ip_pack = $ip_address;
		$ip_pack_size = strlen($ip_pack);
		$ip_bits_size = $ip_pack_size*8;
		$ip_bits = '';
		for($i = 0; $i < $ip_pack_size; $i = $i + 1) {
			$bit = decbin(ord($ip_pack[$i]));
			$bit = str_pad($bit, 8, '0', STR_PAD_LEFT);
			$ip_bits .= $bit;
		}
		$ip_bits = substr($ip_bits, 0, $ip_range);
		$ip_lower_bits = str_pad($ip_bits, $ip_bits_size, '0', STR_PAD_RIGHT);
		$ip_higher_bits = str_pad($ip_bits, $ip_bits_size, '1', STR_PAD_RIGHT);
		$ip_lower_pack = '';
		for ($i = 0; $i < $ip_bits_size; $i = $i + 8) {
			$chr = substr($ip_lower_bits, $i, 8);
			$chr = chr(bindec($chr));
			$ip_lower_pack .= $chr;
		}
		$ip_higher_pack = '';
		for ($i = 0; $i < $ip_bits_size; $i = $i + 8) {
			$chr = substr($ip_higher_bits, $i, 8);
			$chr = chr( bindec($chr) );
			$ip_higher_pack .= $chr;
		}
		return array($ip_lower_pack, $ip_higher_pack);
	} 	else {
		return sys_inet_pton($ipaddress);
	}
}

function random_str($length = 8, $complex = false) {
	$set = array_merge(range(0, 9), range('A', 'Z'), range('a', 'z'));
	$str = array();
	if ($complex == true) {
		$str[] = $set[sys_rand(0, 9)];
		$str[] = $set[sys_rand(10, 35)];
		$str[] = $set[sys_rand(36, 61)];
		$length -= 3;
	}
	for ($i = 0; $i < $length; ++$i) {
		$str[] = $set[sys_rand(0, 61)];
	}
	shuffle($str);
	return implode($str);
}

function sys_rand($min = 0, $max = PHP_INT_MAX) {
	if ($min === null || $max === null || $max < $min) {
		$min = 0;
		$max = PHP_INT_MAX;
	}
	if (version_compare(PHP_VERSION, '7.0', '>=')) {
		try {
			$result = random_int($min, $max);
		} catch (Exception $e) {
		}
		if (isset($result)) {
			return $result;
		}
	}
	$seed = secure_seed_rng();
	$distance = $max - $min;
	return $min + floor($distance * ($seed / PHP_INT_MAX) );
}

function secure_seed_rng() {
	$bytes = PHP_INT_SIZE;
	do {
		$output = secure_binary_seed_rng($bytes);
		if ($bytes == 4) {
			$elements = unpack('i', $output);
			$output = abs($elements[1]);
		} else {
			$elements = unpack('N2', $output);
			$output = abs($elements[1] << 32 | $elements[2]);
		}
	} while ($output > PHP_INT_MAX);
	return $output;
}

function secure_binary_seed_rng($bytes) {
	$output = null;
	if (version_compare(PHP_VERSION, '7.0', '>=')) {
		try {
			$output = random_bytes($bytes);
		} catch (Exception $e) {
		}
	}
	if (strlen($output) < $bytes) {
		if (@is_readable('/dev/urandom') && ($handle = @fopen('/dev/urandom', 'rb'))) {
			$output = @fread($handle, $bytes);
			@fclose($handle);
		}
	} else {
		return $output;
	}
	if (strlen($output) < $bytes) {
		if (function_exists('mcrypt_create_iv')) {
			if (DIRECTORY_SEPARATOR == '/') {
				$source = MCRYPT_DEV_URANDOM;
			} else {
				$source = MCRYPT_RAND;
			}
			$output = @mcrypt_create_iv($bytes, $source);
		}
	} else {
		return $output;
	}
	if (strlen($output) < $bytes) {
		if (function_exists('openssl_random_pseudo_bytes')) {
			if ((DIRECTORY_SEPARATOR == '/') || version_compare(PHP_VERSION, '5.3.4', '>=')) {
				$output = openssl_random_pseudo_bytes($bytes, $crypto_strong);
				if ($crypto_strong == false) {
					$output = null;
				}
			}
		}
	} else {
		return $output;
	}
	if (strlen($output) < $bytes) {
		if (class_exists('COM')) {
			try {
				$CAPI_Util = new COM('CAPICOM.Utilities.1');
				if (is_callable(array($CAPI_Util, 'GetRandom'))) {
					$output = $CAPI_Util->GetRandom($bytes, 0);
				}
			} catch (Exception $e) {
			}
		}
	} else {
		return $output;
	}
	if (strlen($output) < $bytes) {
		$unique_state = microtime().@getmypid();
		$rounds = ceil($bytes / 16);
		for ($i = 0; $i < $rounds; $i++) {
			$unique_state = md5(microtime().$unique_state);
			$output .= md5($unique_state);
		}
		$output = substr($output, 0, ($bytes * 2));
		$output = pack('H*', $output);
		return $output;
	} else {
		return $output;
	}
}

function sys_setcookie($name, $value = "", $expires = "", $httponly = false, $samesite = "") {
	global $config, $wms;
	if (!$config->cookies['path']) {
		$config->cookies['path'] = "/";
	}
	if ($expires == -1) {
		$expires = 0;
	} else if ($expires == "" || $expires == null) {
		$expires = CURRENT_TIME + $config->session['timeout']; // Make the cookie expire in a years time by default
	} 	else {
		$expires = CURRENT_TIME + (int) $expires;
	}
	$config->cookies['path'] = str_replace(array("\n","\r"), "", $config->cookies['path']);
	$config->cookies['domain'] = str_replace(array("\n","\r"), "", $config->cookies['domain']);
	$config->cookies['prefix'] = str_replace(array("\n","\r", " "), "", $config->cookies['prefix']);
	$cookie = "Set-Cookie: {$config->cookies['prefix']}{$name}=" . urlencode($value);
	if ($expires > 0) {
		$cookie .= "; expires=".@gmdate('D, d-M-Y H:i:s \\G\\M\\T', $expires);
	}
	if (!empty($config->cookies['path'])) {
		$cookie .= "; path={$config->cookies['path']}";
	}
	if (!empty($config->cookies['domain'])) {
		$cookie .= "; domain={$config->cookies['domain']}";
	}
	if ($httponly == true) {
		$cookie .= "; HttpOnly";
	}
	if ($samesite != "" && $config->cookies['samesite']) {
		$samesite = strtolower($samesite);
		if ($samesite == "lax" || $samesite == "strict") {
			$cookie .= "; SameSite=".$samesite;
		}
	}
	if ($config->cookies['secure']) {
		$cookie .= "; Secure";
	}
	$wms->cookies[$name] = $value;
	header($cookie, false);
}

function sys_unsetcookie($name) {
	global $wms;
	$expires = -3600;
	sys_setcookie($name, "", $expires);
	unset($wms->cookies[$name]);
}

function sys_get_array_cookie($name, $id) {
	global $wms, $config;
	if (!isset($wms->cookies[$config->session['name']][$name])) {
		return false;
	}
	$cookie = sys_unserialize($wms->cookies[$config->session['name']][$name]);
	if (is_array($cookie) && isset($cookie[$id])) {
		return $cookie[$id];
	} else {
		return 0;
	}
}

function sys_set_array_cookie($name, $id, $value, $expires = "") {
	global $wms;

	if (isset($wms->cookies[$config->session['name']][$name])) {
		$newcookie = sys_unserialize($wms->cookies[$config->session['name']][$name]);
	} else {
		$newcookie = array();
	}
	$newcookie[$id] = $value;
	$newcookie = sys_serialize($newcookie);
	sys_setcookie($config->session['name'] . "[$name]", addslashes($newcookie), $expires);
	$wms->cookies[$config->session['name']][$name] = $newcookie;
}

define('MAX_SERIALIZED_INPUT_LENGTH', 10240);
define('MAX_SERIALIZED_ARRAY_LENGTH', 256);
define('MAX_SERIALIZED_ARRAY_DEPTH', 5);

function _safe_unserialize($str) {
	if (strlen($str) > MAX_SERIALIZED_INPUT_LENGTH) {
		return false;
	}
	if (empty($str) || !is_string($str)) {
		return false;
	}
	$stack = $list = $expected = array();
	$state = 0;
	while ($state != 1) {
		$type = isset($str[0]) ? $str[0] : '';
		if ($type == '}') {
			$str = substr($str, 1);
		} else if ($type == 'N' && $str[1] == ';') {
			$value = null;
			$str = substr($str, 2);
		} else if ($type == 'b' && preg_match('/^b:([01]);/', $str, $matches)) {
			$value = $matches[1] == '1' ? true : false;
			$str = substr($str, 4);
		} else if ($type == 'i' && preg_match('/^i:(-?[0-9]+);(.*)/s', $str, $matches)) {
			$value = (int)$matches[1];
			$str = $matches[2];
		} else if ($type == 'd' && preg_match('/^d:(-?[0-9]+\.?[0-9]*(E[+-][0-9]+)?);(.*)/s', $str, $matches)) {
			$value = (float)$matches[1];
			$str = $matches[3];
		} else if($type == 's' && preg_match('/^s:([0-9]+):"(.*)/s', $str, $matches) && substr($matches[2], (int)$matches[1], 2) == '";') {
			$value = substr($matches[2], 0, (int)$matches[1]);
			$str = substr($matches[2], (int)$matches[1] + 2);
		} else if ($type == 'a' && preg_match('/^a:([0-9]+):{(.*)/s', $str, $matches) && $matches[1] < MAX_SERIALIZED_ARRAY_LENGTH) {
			$expectedLength = (int)$matches[1];
			$str = $matches[2];
		} else {
			return false;
		}
		switch($state) {
			case 3:
				if ($type == 'a') {
					if (count($stack) >= MAX_SERIALIZED_ARRAY_DEPTH) {
						return false;
					}
					$stack[] = &$list;
					$list[$key] = array();
					$list = &$list[$key];
					$expected[] = $expectedLength;
					$state = 2;
					break;
				}
				if ($type != '}') {
					$list[$key] = $value;
					$state = 2;
					break;
				}
				return false;
			case 2:
				if ($type == '}') {
					if (count($list) < end($expected)) {
						return false;
					}
					unset($list);
					$list = &$stack[count($stack)-1];
					array_pop($stack);
					array_pop($expected);
					if (count($expected) == 0) {
						$state = 1;
					}
					break;
				}
				if ($type == 'i' || $type == 's') {
					if (count($list) >= MAX_SERIALIZED_ARRAY_LENGTH) {
						return false;
					}
					if (count($list) >= end($expected)) {
						return false;
					}
					$key = $value;
					$state = 3;
					break;
				}
				return false;
			case 0:
				if ($type == 'a') {
					if (count($stack) >= MAX_SERIALIZED_ARRAY_DEPTH) {
						return false;
					}
					$data = array();
					$list = &$data;
					$expected[] = $expectedLength;
					$state = 2;
					break;
				}
				if ($type != '}') {
					$data = $value;
					$state = 1;
					break;
				}
				return false;
		}
	}
	if (!empty($str)) {
		return false;
	}
	return $data;
}

function htmlspecialchars_uni($message) {
	$message = preg_replace("#&(?!\#[0-9]+;)#si", "&amp;", $message);
	$message = str_replace("<", "&lt;", $message);
	$message = str_replace(">", "&gt;", $message);
	$message = str_replace("\"", "&quot;", $message);
	return $message;
}

function sys_unserialize($str) {
	if (function_exists('mb_internal_encoding') && (((int)ini_get('mbstring.func_overload')) & 2)) {
		$mbIntEnc = mb_internal_encoding();
		mb_internal_encoding('ASCII');
	}
	$out = _safe_unserialize($str);
	if (isset($mbIntEnc)) {
		mb_internal_encoding($mbIntEnc);
	}
	return $out;
}

function _safe_serialize($value) {
    if (is_null($value)) {
		return 'N;';
	}
	if (is_bool($value)) {
		return 'b:' . (int) $value . ';';
	}
	if (is_int($value)) {
		return 'i:' . $value . ';';
	}
	if (is_float($value)) {
		return 'd:' . str_replace(',', '.', $value) . ';';
	}
	if (is_string($value)) {
		return 's:' . strlen($value) . ':"' . $value . '";';
	}
	if (is_array($value)) {
		$out = '';
		foreach ($value as $k => $v) {
			$out .= _safe_serialize($k) . _safe_serialize($v);
		}
		return 'a:' . count($value) . ':{' . $out . '}';
	}
	return false;
}

function sys_serialize($value) {
	if (function_exists('mb_internal_encoding') && (((int)ini_get('mbstring.func_overload')) & 2)) {
		$mbIntEnc = mb_internal_encoding();
		mb_internal_encoding('ASCII');
	}
	$out = _safe_serialize($value);
	if (isset($mbIntEnc)) {
		mb_internal_encoding($mbIntEnc);
	}
	return $out;
}

function validate_utf8_string($input, $allow_mb4 = true, $return = true) {
	if (!preg_match('##u', $input)) {
		$string = '';
		$len = strlen($input);
		for ($i = 0; $i < $len; $i++) {
			$c = ord($input[$i]);
			if ($c > 128) {
				if ($c > 247 || $c <= 191) {
					if ($return) {
						$string .= '?';
						continue;
					} else {
						return false;
					}
				} else if ($c > 239) {
					$bytes = 4;
				} else if ($c > 223) {
					$bytes = 3;
				} else if ($c > 191) {
					$bytes = 2;
				}
				if (($i + $bytes) > $len) {
					if ($return) {
						$string .= '?';
						break;
					} else {
						return false;
					}
				}
				$valid = true;
				$multibytes = $input[$i];
				while ($bytes > 1) {
					$i++;
					$b = ord($input[$i]);
					if ($b < 128 || $b > 191) {
						if ($return) {
							$valid = false;
							$string .= '?';
							break;
						} else {
							return false;
						}
					} else {
						$multibytes .= $input[$i];
					}
					$bytes--;
				}
				if ($valid) {
					$string .= $multibytes;
				}
			} else {
				$string .= $input[$i];
			}
		}
		$input = $string;
	}
	if ($return) {
		if ($allow_mb4) {
			return $input;
		} else {
			return preg_replace("#[^\\x00-\\x7F][\\x80-\\xBF]{3,}#", '?', $input);
		}
	} else {
		if ($allow_mb4) {
			return true;
		} else {
			return !preg_match("#[^\\x00-\\x7F][\\x80-\\xBF]{3,}#", $input);
		}
	}
}

function get_current_location($fields = false, $ignore = array(), $quick = false) {
	global $wms;
	if (defined("CURRENT_LOCATION")) {
		return CURRENT_LOCATION;
	}
	if (!empty($_SERVER['SCRIPT_NAME'])) {
		$location = htmlspecialchars_uni($_SERVER['SCRIPT_NAME']);
	} else if (!empty($_SERVER['PHP_SELF'])) {
		$location = htmlspecialchars_uni($_SERVER['PHP_SELF']);
	} else if (!empty($_ENV['PHP_SELF'])) {
		$location = htmlspecialchars_uni($_ENV['PHP_SELF']);
	} else if (!empty($_SERVER['PATH_INFO'])) {
		$location = htmlspecialchars_uni($_SERVER['PATH_INFO']);
	} else {
		$location = htmlspecialchars_uni($_ENV['PATH_INFO']);
	}
	if ($quick) {
		return $location;
	}
	if (!is_array($ignore)) {
		$ignore = array($ignore);
	}
	if ($fields == true) {
		$form_html = '';
		if (!empty($wms->input)) {
			foreach ($wms->input as $name => $value) {
				if (in_array($name, $ignore) || is_array($name) || is_array($value)) {
					continue;
				}
				$form_html .= "<input type=\"hidden\" name=\"".htmlspecialchars_uni($name)."\" value=\"".htmlspecialchars_uni($value)."\" />\n";
			}
		}
		return array('location' => $location, 'form_html' => $form_html, 'form_method' => $wms->request_method);
	} else {
		$parameters = array();
		if (isset($_SERVER['QUERY_STRING'])) {
			$current_query_string = $_SERVER['QUERY_STRING'];
		} else if (isset($_ENV['QUERY_STRING'])) {
			$current_query_string = $_ENV['QUERY_STRING'];
		} else {
			$current_query_string = '';
		}
		parse_str($current_query_string, $current_parameters);
		foreach ($current_parameters as $name => $value) {
			if (!in_array($name, $ignore)) {
				$parameters[$name] = $value;
			}
		}
		if ($wms->request_method === 'post') {
		    //@Variable input for current post action to track users flow
			$post_array = array('action', 'fid', 'pid', 'tid', 'uid', 'eid');
			foreach ($post_array as $var) {
				if (isset($_POST[$var]) && !in_array($var, $ignore)) {
					$parameters[$var] = $_POST[$var];
				}
			}
		}
		if (!empty($parameters)) {
			$location .= '?' . http_build_query($parameters, '', '&amp;');
		}
		return $location;
	}
}

function get_server_load() {
	global $lang;
	$serverload = array();
	if(DIRECTORY_SEPARATOR != '\\') {
		if (function_exists("sys_getloadavg")) {
			$serverload = sys_getloadavg();
			$serverload[0] = round($serverload[0], 4);
		}
		else if (@file_exists("/proc/loadavg") && $load = @file_get_contents("/proc/loadavg")) {
			$serverload = explode(" ", $load);
			$serverload[0] = round($serverload[0], 4);
		}
		if (!is_numeric($serverload[0])) {
			if ($func_blacklist = @ini_get('suhosin.executor.func.blacklist')) {
				if (strpos(",".$func_blacklist.",", 'exec') !== false) {
					return $lang->unknown;
				}
			}
			if ($func_blacklist = @ini_get('disable_functions')) {
				if (strpos(",".$func_blacklist.",", 'exec') !== false) {
					return $lang->unknown;
				}
			}
			$load = @exec("uptime");
			$load = explode("load average: ", $load);
			$serverload = explode(",", $load[1]);
			if (!is_array($serverload)) {
				return $lang->unknown;
			}
		}
	} else {
		return $lang->unknown;
	}
	$returnload = trim($serverload[0]);
	return $returnload;
}

function get_memory_usage() {
	if (function_exists('memory_get_peak_usage')) {
		return memory_get_peak_usage(true);
	} else if (function_exists('memory_get_usage')) {
		return memory_get_usage(true);
	}
	return false;
}

// NOT USED ANYMORE
function output_page($contents) {
    global $primary_timer, $db, $config;
    $contents = parse_page($contents);
	$totaltime = format_time_duration($primary_timer->stop());
	$phptime = $primary_timer->totaltime - $db->query_time;
	$query_time = $db->query_time;
	if ($primary_timer->totaltime > 0) {
		$percentphp = number_format((($phptime / $primary_timer->totaltime) * 100), 2);
		$percentsql = number_format((($query_time / $primary_timer->totaltime) * 100), 2);
	} else {
		$percentphp = 0;
		$percentsql = 0;
	}
	$serverload = get_server_load();
	$memory_usage = get_memory_usage();
	if ($memory_usage) {
		$memory_usage = $lang->sprintf($lang->debug_memory_usage, get_friendly_size($memory_usage));
	} else {
		$memory_usage = '';
	}
	$database_server = $db->short_title;
	if ($database_server == 'MySQLi') {
		$database_server = 'MySQL';
	}
	$generated_in = $lang->sprintf($lang->debug_generated_in, $totaltime);
	$debug_weight = $lang->sprintf($lang->debug_weight, $percentphp, $percentsql, $database_server);
	$sql_queries = $lang->sprintf($lang->debug_sql_queries, $db->query_count);
	$server_load = $lang->sprintf($lang->debug_server_load, $serverload);
	if ($config->gzip['enabled']) {
	    $contents = gzip_encode($contents, $config->gzip['level']);
    }
    @header("Content-type: text/html; charset={$lang->settings['charset']}");
	echo $contents;
}

function add_shutdown($name, $arguments = array()) {
	global $shutdown_functions;
	if (!is_array($shutdown_functions))	{
		$shutdown_functions = array();
	}
	if (!is_array($arguments)) {
		$arguments = array($arguments);
	}
	if (is_array($name) && method_exists($name[0], $name[1])) {
		$shutdown_functions[] = array('function' => $name, 'arguments' => $arguments);
		return true;
	} else if (!is_array($name) && function_exists($name)) {
		$shutdown_functions[] = array('function' => $name, 'arguments' => $arguments);
		return true;
	}
	return false;
}

// NOT USED ANYMORE
function parse_page($contents) {
	global $lang, $wms, $htmldoctype, $error_handler;
	//$contents = str_replace('<navigation>', build_breadcrumb(), $contents);
	//$contents = str_replace('<archive_url>', $archive_url, $contents);
	if ($htmldoctype) {
		$contents = $htmldoctype . $contents;
	} else {
		$contents = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n" . $contents;
	}
	$contents = str_replace("<html", "<html xmlns=\"http://www.w3.org/1999/xhtml\"", $contents);
	if ($error_handler->warnings) {
		$contents = str_replace("<body>", "<body>\n" . $error_handler->show_warnings(), $contents);
	}
	return $contents;
}
