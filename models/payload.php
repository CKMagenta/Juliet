<?php
class Payload extends CI_Model {

	public $event;				// Event
	public $sender;			// User
	public $receivers;		// User[]
	public $room;
	public $messsage;		//Message

	function __construct() {
  	parent::__construct();
  }
  
  
}
?>