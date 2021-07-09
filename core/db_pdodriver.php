<?php
/**
 * 
 * core/db_pdodriver.php
 * WMS (Website Management System)
 *
 * @category    database
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

abstract class AbstractPdoDbDriver implements DB_Base {

	public $error_reporting = 1;
	public $read_link = null;
	public $write_link = null;
	public $current_link = null;
	public $database;
	public $db_encoding = "utf8";
	public $query_time = 0;
	public $query_count = 0;
	public $connections = array();
	private $lastPdoException;
	protected $last_query_type = 0;
	private $resultSeekPositions = array();
	private $lastResult = null;
	public $table_prefix;
	public $version;
	public $querylist = array();
	public $engine = "pdo";
	public $can_search = true;
	protected abstract function getDsn($hostname, $db, $port, $encoding);
	
	public function connect($config) {
		$connections = array(
			'read' => array(),
			'write' => array(),
		);
		if (isset($config['hostname'])) {
			$connections['read'][] = $config;
		} else {
			if (!isset($config['read'])) {
				foreach ($config as $key => $settings) {
					if (is_int($key)) {
						$connections['read'][] = $settings;
					}
				}
			} else {
				$connections = $config;
			}
		}
		$this->db_encoding = $config['encoding'];
		foreach (array('read', 'write') as $type) {
			if (!isset($connections[$type]) || !is_array($connections[$type])){
				break;
			}
			if (isset($connections[$type]['hostname'])) {
				$details = $connections[$type];
				unset($connections[$type]);
				$connections[$type][] = $details;
			}
			shuffle($connections[$type]);
			foreach ($connections[$type] as $singleConnection) {
				$flags = array(
					PDO::ATTR_PERSISTENT => false,
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_EMULATE_PREPARES => false,
				);
				if (!empty($singleConnection['pconnect'])) {
					$flags[PDO::ATTR_PERSISTENT] = true;
				}
				$link = "{$type}_link";
				get_execution_time();
				list($hostname, $port) = self::parseHostname($singleConnection['hostname']);
				$dsn = $this->getDsn(
					$hostname,
					$config['database'],
					$port,
					$this->db_encoding
				);
				try {
					$this->$link = new PDO(
						$dsn,
						$singleConnection['username'],
						$singleConnection['password'],
						$flags
					);
					$this->lastPdoException = null;
				} catch (PDOException $e) {
					$this->$link = null;
					$this->lastPdoException = $e;
				}
				$time_spent = get_execution_time();
				$this->query_time += $time_spent;
				if ($this->$link !== null) {
					$this->connections[] = "[".strtoupper($type)."] {$singleConnection['username']}@{$singleConnection['hostname']} (Connected in ".format_time_duration($time_spent).")";
					break;
				} else {
					$this->connections[] = "<span style=\"color: red\">[FAILED] [".strtoupper($type)."] {$singleConnection['username']}@{$singleConnection['hostname']}</span>";
				}
			}
		}
		if (empty($connections['write'])) {
			$this->write_link = $this->read_link;
		}
		if ($this->read_link === null) {
			$this->error("[READ] Unable to connect to database server");
			return false;
		} else if($this->write_link === null) {
			$this->error("[WRITE] Unable to connect to database server");
			return false;
		}
		$this->database = $config['database'];
		if (version_compare('PHP_VERSION', '5.3.6', '<') === true) {
			$this->setCharacterSet($this->db_encoding);
		}
		$this->current_link = $this->read_link;
		return true;
	}

	private static function parseHostname($hostname) {
		$openingSquareBracket = strpos($hostname, '[');
		if ($openingSquareBracket === 0) {
			$closingSquareBracket = strpos($hostname, ']', $openingSquareBracket);
			if ($closingSquareBracket !== false) {
				$portSeparator = strpos($hostname, ':', $closingSquareBracket);
				if ($portSeparator === false) {
					return array($hostname, null);
				} else {
					$host = substr($hostname, $openingSquareBracket, $closingSquareBracket + 1);
					$port = (int) substr($hostname, $portSeparator + 1);
					return array($host, $port);
				}
			} else {
				throw new InvalidArgumentException("Hostname is missing a closing square bracket for IPv6 address: {$hostname}");
			}
		}
		$portSeparator = strpos($hostname, ':', 0);
		if ($portSeparator === false) {
			return array($hostname, null);
		} else {
			$host = substr($hostname, 0, $portSeparator);
			$port = (int) substr($hostname, $portSeparator + 1);
			return array($host, $port);
		}
	}

	public function setCharacterSet($characterSet) {
		$query = "SET NAMES {$characterSet}";
		self::execIgnoreError($this->read_link, $query);
		if ($this->write_link !== $this->read_link) {
			self::execIgnoreError($this->write_link, $query);
		}
	}

	private static function execIgnoreError($connection, $query) {
		try {
			$connection->exec($query);
		} catch (PDOException $e) {
		}
	}

	public function error($string = '') {
		if ($this->error_reporting) {
			if (class_exists("errorHandler")) {
				global $error_handler;
				if (!is_object($error_handler)) {
					require_once ROOT_DIR . "core/class_errorhandler.php";
					$error_handler = new errorHandler();
				}
				$error = array(
					"error_no" => $this->error_number(),
					"error" => $this->error_string(),
					"query" => $string
				);
				$error_handler->error(WMS_SQL, $error);
			} else {
				trigger_error("<strong>[SQL] [". $this->error_number() ."]" . $this->error_string() . " </strong><br />{$string}", E_USER_ERROR);
			}
			return true;
		} else {
			return false;
		}
	}

	public function error_number() {
		if ($this->lastPdoException !== null) {
			return $this->lastPdoException->getCode();
		}
		return null;
	}

	public function error_string() {
		if ($this->lastPdoException !== null && isset($this->lastPdoException->errorInfo[2])) {
			return $this->lastPdoException->errorInfo[2];
		}
		return null;
	}

	public function query($string, $hideErrors = false, $writeQuery = false) {
		global $wms;
		get_execution_time();
		if (($writeQuery || $this->last_query_type) && $this->write_link) {
			$this->current_link = &$this->write_link;
		} else {
			$this->current_link = &$this->read_link;
		}
		$query = null;
		try {
			if (preg_match('/^\\s*SELECT\\b/i', $string) === 1) {
				$query = $this->current_link->prepare($string, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
				$query->execute();
				$this->lastPdoException = null;
			} else {
				$query = $this->current_link->query($string);
				$this->lastPdoException = null;
			}
		} catch (PDOException $e) {
			$this->lastPdoException = $e;
			$query = null;
			if (!$hideErrors) {
				$this->error($string);
				exit;
			}
		}
		if ($writeQuery) {
			$this->last_query_type = 1;
		} else {
			$this->last_query_type = 0;
		}
		$query_time = get_execution_time();
		$this->query_time += $query_time;
		$this->query_count++;
		$this->lastResult = $query;
		return $query;
	}

	public function write_query($query, $hideErrors = false) {
		return $this->query($query, $hideErrors, true);
	}

	public function fetch_array($query, $resultType = PDO::FETCH_ASSOC) {
		if (is_null($query) || !($query instanceof PDOStatement)) {
			return false;
		}
		switch ($resultType)	{
			case PDO::FETCH_NUM:
			case PDO::FETCH_BOTH:
				break;
			default:
				$resultType = PDO::FETCH_ASSOC;
				break;
		}
		$hash = spl_object_hash($query);
		if (isset($this->resultSeekPositions[$hash])) {
			return $query->fetch($resultType, PDO::FETCH_ORI_ABS, $this->resultSeekPositions[$hash]);
		}
		return $query->fetch($resultType);
	}

	public function fetch_field($query, $field, $row = false) {
		if (is_null($query) || !($query instanceof PDOStatement)) {
			return false;
		}
		if ($row !== false) {
			$this->data_seek($query, (int) $row);
		}
		$array = $this->fetch_array($query, PDO::FETCH_ASSOC);
		if ($array === false) {
			return false;
		}
		return $array[$field];
	}

	public function data_seek($query, $row) {
		if (is_null($query) || !($query instanceof PDOStatement)) {
			return false;
		}
		$hash = spl_object_hash($query);
		$this->resultSeekPositions[$hash] = ((int) $row) + 1;
		return true;
	}

	public function num_rows($query) {
		if (is_null($query) || !($query instanceof PDOStatement)) {
			return false;
		}
		if (preg_match('/^\\s*SELECT\\b/i', $query->queryString) === 1) {
			$countQuery = $this->read_link->query($query->queryString);
			$result = $countQuery->fetchAll(PDO::FETCH_COLUMN, 0);
			return count($result);
		} else {
			return $query->rowCount();
		}
	}

	public function insert_id()	{
		return $this->current_link->lastInsertId();
	}

	public function close()	{
		$this->read_link = $this->write_link = $this->current_link = null;
	}

	public function affected_rows()	{
		if ($this->lastResult === null) {
			return 0;
		}
		return $this->lastResult->rowCount();
	}

	public function num_fields($query) {
		if (is_null($query) || !($query instanceof PDOStatement)) {
			return false;
		}
		return $query->columnCount();
	}

	 public function shutdown_query($query, $name = '') {
		 global $shutdown_queries;
		 if ($name) {
			 $shutdown_queries[$name] = $query;
		 } else {
			 $shutdown_queries[] = $query;
		 }
	 }

	 public function escape_string($string) {
		 $string = $this->read_link->quote($string);
		 $string = substr($string, 1);
		 $string = substr($string, 0, -1);
		 return $string;
	 }

	 public function free_result($query) {
	 	 if (is_object($query) && $query instanceof PDOStatement) {
		     return $query->closeCursor();
	     }
	 	 return false;
	 }

	 public function escape_string_like($string) {
		 return $this->escape_string(str_replace(array('\\', '%', '_') , array('\\\\', '\\%' , '\\_') , $string));
	 }

	 public function get_version() {
		 if ($this->version) {
			 return $this->version;
		 }
		 $this->version = $this->read_link->getAttribute(PDO::ATTR_SERVER_VERSION);
		 return $this->version;
	 }

	 public function set_table_prefix($prefix) {
		 $this->table_prefix = $prefix;
	 }

	 public function get_execution_time() {
		 return get_execution_time();
	 }

 }
