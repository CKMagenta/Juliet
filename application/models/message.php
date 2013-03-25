<?php
class Message extends CI_Model {
	
  public $type;
  public $title;
  public $content;
  public $appendex;		// Appendix


	function __construct() {
  	parent::__construct();
  }
  
  
}
?>