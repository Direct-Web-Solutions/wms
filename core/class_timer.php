<?php
/**
 * 
 * core/class_timer.php
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

class timer {

	public $name;
	public $start;
	public $end;
	public $totaltime;
	public $formatted;

	function __construct() {
		$this->add();
	}

	function add() {
		if (!$this->start) {
			$this->start = microtime(true);
		}
	}

	function getTime() {
		if ($this->end) {
			return $this->totaltime;
		} else if ($this->start && !$this->end) {
			$currenttime = microtime(true);
			$totaltime = $currenttime - $this->start;
			return $this->format($totaltime);
		} else {
			return false;
		}
	}

	function stop()	{
		if ($this->start) {
			$this->end = microtime(true);
			$totaltime = $this->end - $this->start;
			$this->totaltime = $totaltime;
			$this->formatted = $this->format($totaltime);
			return $this->formatted;
		}
		return '';
	}

	function remove() {
		$this->name = "";
		$this->start = "";
		$this->end = "";
		$this->totaltime = "";
		$this->formatted = "";
	}

	function format($string) {
		return number_format($string, 7);
	}

}
