<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Auth extends DAON_Controller {
	
	function __construct() {
		parent::__construct();
	}

	public function login() {
		$attempts = $this->session->userdata('login_attempts');
		$attempts = $attempts == false ? 0 : $attempts;
		
		$this->load->view('auth/login.php',get_defined_vars());
	}

	public function login_submit() {
		
	}
}