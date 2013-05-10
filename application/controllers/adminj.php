<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Adminj extends DAON_Controller {
	
	function __construct() {
		parent::__construct();
	}
	
	public function index() {
		$this->dashboard();
	}
	
	public function dashboard() {
		$this->load->view('adminj/dashboard.php',get_defined_vars());
	}

}