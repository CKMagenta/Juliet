<?php
class Timestamp extends CI_Model {

	public $timestamp;
	public $UTC;

	function __construct($timestamp) {
  	parent::__construct();
    $_time = time();
    $this->$timestamp = ($timestamp)? $timestamp : $_time;
    $this->$UTC = gmdate("M d Y H:i:s", $_time);
  }
  
  
}
?>