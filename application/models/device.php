<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Device extends CI_Model {
	
	private $eventAr;
	private $requestPayload;
	private $responsePayload; //controller에 있는 responsePayload를 참조하는 변수
	
	function __construct() {
  		parent::__construct();
		$this->load->database();
	}
	
	public function act($handler,$eventAr,$requestPayload) {
		$this->eventAr = $eventAr;
		$this->requestPayload = $requestPayload;
		$this->responsePayload = &Handler::$responsePayload;	

		//sub event별 분류
		switch( $eventAr[1] ) {
			case "IS_REGISTERED":
				$this->is_registered();
				break;
			case "REGISTER":
				$this->register();
				break;
			case "UNREGISTER":
				$this->unregister();
				break;
		}
	}
	
	private function is_registered() {
		$data = $this->requestPayload['data'];
				
		if( !isset($data[0][KEY_USER::IDX]) || !isset($data[0][KEY_DEVICE::UUID]) || !isset($data[0][KEY_DEVICE::GCM_REGISTRATION_ID]) || 
				!$data[0][KEY_USER::IDX] || !$data[0][KEY_DEVICE::UUID] || !$data[0][KEY_DEVICE::GCM_REGISTRATION_ID] ) {
			
 			$this->responsePayload['status'] = STATUS::INSUFFICIENT_ARGUMENTS;
 			$this->responsePayload['data'][] = array(KEY::RESPONSE_TEXT=>'Insufficient Informations');
			return;
		}
		
		//TODO: user_hash, uuid, regid가 유효한 값인지 검사
		
		$userSeq = $this->db->query("select seq from js_user where user_hash = ?",$data[0][KEY_USER::IDX])->row('seq');
		
		//플래그
		$deviceRegistered = false;
		$deviceEnabled = false;
		
		//등록되어 있는지 쿼리 보내 검사
		$query = "select is_enabled from js_device where user_seq = ? and regid = ? and uuid = ?";
		$result = $this->db->query($query,array($userSeq,$data[0][KEY_DEVICE::GCM_REGISTRATION_ID],$data[0][KEY_DEVICE::UUID]))->row_array();
		
		if( $result ) {
			$deviceRegistered = true;
			
			$deviceEnabled = $result['is_enabled']==1?true:false;
		}
		
		$this->responsePayload['status'] = STATUS::SUCCESS;
		$this->responsePayload['data'][] = array(KEY_DEVICE::IS_REGISTERED=>$deviceRegistered,KEY_DEVICE::IS_ENABLED=>$deviceEnabled);
	}
	
	private function register() {
		
		$data = $this->requestPayload['data'];
		
		if( !isset($data[0][KEY_USER::IDX]) || !isset($data[0][KEY_DEVICE::UUID]) || !isset($data[0][KEY_DEVICE::GCM_REGISTRATION_ID]) ||!isset($data[0][KEY_DEVICE::TYPE]) ||
				!$data[0][KEY_USER::IDX] || !$data[0][KEY_DEVICE::UUID] || !$data[0][KEY_DEVICE::GCM_REGISTRATION_ID] || !$data[0][KEY_DEVICE::TYPE] ) {
			
 			$this->responsePayload['status'] = STATUS::INSUFFICIENT_ARGUMENTS;
 			$this->responsePayload['data'][] = array(KEY::RESPONSE_TEXT=>'Insufficient Informations');
			return;
		}
		
		$userSeq = $this->db->query("select seq from js_user where user_hash = ?",$data[0][KEY_USER::IDX])->row('seq');
		
		$query = 
		"INSERT INTO `daondb`.`js_device`
		(`user_seq`,
		`device_type`,
		`uuid`,
		`regid`,
		
		`is_enabled`,
		`created_ts`) 
		VALUES (?, ?, ?, ?,  0, now())
		ON DUPLICATE KEY UPDATE
		`user_seq` = values(user_seq),
		`device_type` = values(device_type),
		`uuid` = values(uuid),
		`regid` = values(regid)";
		$this->db->query($query,array($userSeq,$data[0][KEY_DEVICE::TYPE],$data[0][KEY_DEVICE::UUID],$data[0][KEY_DEVICE::GCM_REGISTRATION_ID]));		

		//dept hash update
		$devSeq = $this->db->query('select last_insert_id() a')->row('a');
		$devHash = md5("js_device".$devSeq);
		
		$query = "UPDATE js_device SET device_hash = ? where seq = ?";
		$this->db->query($query,array($devHash,$devSeq));
		
		$this->responsePayload['status'] = STATUS::SUCCESS;
		$this->responsePayload['data'][] = array(
										KEY_DEVICE::IDX=>$devHash,
										KEY_DEVICE::GCM_REGISTRATION_ID=>$data[0][KEY_DEVICE::GCM_REGISTRATION_ID],
										KEY_DEVICE::UUID=>$data[0][KEY_DEVICE::UUID]);
	}
	
	private function unregister() {
	
		$data = $this->requestPayload['data'];
		
		if( !isset($data[0][KEY_DEVICE::UUID]) || !isset($data[0][KEY_DEVICE::GCM_REGISTRATION_ID]) ||
				!$data[0][KEY_DEVICE::UUID] || !$data[0][KEY_DEVICE::GCM_REGISTRATION_ID] ) {
				
			$this->responsePayload['status'] = STATUS::INSUFFICIENT_ARGUMENTS;
			$this->responsePayload['data'][] = array(KEY::RESPONSE_TEXT=>'Insufficient Informations');
			return;
		}
		
		$query = "select seq from js_device where uuid = ? and regid = ?";
		if ( !$this->db->query($query,array($data[0][KEY_DEVICE::UUID],$data[0][KEY_DEVICE::GCM_REGISTRATION_ID]))->row_array() ) {
			$this->responsePayload['status'] = STATUS::NO_DATA;
			$this->responsePayload['data'][] = array(KEY::RESPONSE_TEXT=>'no data in table');
		} else {
			$query = "delete from js_device where uuid = ? and regid = ?";
			$this->db->query($query,array($data[0][KEY_DEVICE::UUID],$data[0][KEY_DEVICE::GCM_REGISTRATION_ID]));
			$this->responsePayload['status'] = STATUS::SUCCESS;
			$this->responsePayload['data'][] = array(KEY::RESPONSE_TEXT=>'success');
		}
	}
	
}


/* End of file device.php */