<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class DAON_Controller extends CI_Controller {
	
	const USER_STATUS_NOT_ENABLED = 0;
	const USER_STATUS_ENABLED = 1;
	const USER_STATUS_BLOCKED = 2;
	
	protected $_open_controllers = array('auth');
	
	public function __construct() {
		
		parent::__construct();
		$this->load->library('session');
		$this->_check_auth();
	}
	
	private function _check_auth() {
		return;
		if ( ! $this->session->userdata('logged_in') ) {
			
			if ( ! in_array($this->uri->segment(1), $this->_open_controllers) ) {
				
				$this->session->set_userdata('next', $this->uri->uri_string);
				redirect('auth/login');
				
			}
			
		} else {
			
			switch ( $this->session->userdata('user_status') ) {
				
			case USER_STATUS_ENABLED:
				return;
				
			case USER_STATUS_NOT_ENABLED:
				show_error('승인 대기 중',401,'아직 승인되지 않은 계정입니다. 관리자에게 문의해주세요.');
				
			case USER_STATUS_BLOCKED:
				show_error('접근 차단 상태',403,'로그인 시도 횟수 초과 등의 이유로 접근이 차단된 상태입니다. 관리자에게 문의해주세요.');
				
			default:
				$this->session->set_userdata('next', $this->uri->uri_string);
				redirect('auth/login');
				
			}
			
		}
		
	}
}