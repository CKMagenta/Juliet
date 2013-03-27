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
		$this->responsePayload =& $handler::$responsePayload;		
				
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
		
		/**
			○ request
			-event : DEVICE:IS_REGISTERED
			-data :
				-user_hash :	유저해시
				-uuid :			기기식별용 uuid
				-regid :		gcm regid
		 */
		
		$data = $this->requestPayload['data'];
				
		if( !isset($data['user_hash']) || !isset($data['uuid']) || !isset($data['regid']) || 
				!$data['user_hash'] || !$data['uuid'] || !$data['regid'] ) {
			
 			$this->responsePayload['status'] = INSUFFICIENT_ARGUMENTS;
 			$this->responsePayload['data'] = 'Insufficient Informations';
			return;
		}
		
		//TODO: user_hash, uuid, regid가 유효한 값인지 검사
		
		$userSeq = $this->db->query("select user_seq from js_user where user_hash = ?",$data['user_hash'])->row('user_seq');
		
		//플래그
		$deviceRegistered = 0;
		$deviceEnabled = 0;
		
		//등록되어 있는지 쿼리 보내 검사
		$query = "select is_enabled from js_device where user_seq = ? and regid = ? and uuid = ?";
		$result = $this->db->query($query,array($userSeq,$data['regid'],$data['uuid']))->row_array();
		
		if( $result ) {
			$deviceRegistered = 1;
			
			$deviceEnabled = $result['is_enabled'];
		}
		
		$this->responsePayload['status'] = SUCCESS;
		$this->responsePayload['data'] = array("isRegistered"=>$deviceRegistered,"isEnabled"=>$deviceEnabled);
	}
	
	private function register() {
		
		/**
			○ request
			-event : DEVICE:REGISTER
			-data :
				-user_hash :	유저코드(기존의 idx)
				-uuid :			기기식별용 uuid
				-regid : 		gcm regid
				-dev_type:		device type
		 */
		
		$data = $this->requestPayload['data'];
		
		if( !isset($data['user_hash']) || !isset($data['uuid']) || !isset($data['regid']) ||!isset($data['dev_type']) ||
				!$data['user_hash'] || !$data['uuid'] || !$data['regid'] || !$data['dev_type'] ) {
			
 			$this->responsePayload['status'] = INSUFFICIENT_ARGUMENTS;
 			$this->responsePayload['data'] = 'Insufficient Informations';
			return;
		}
		
		$userSeq = $this->db->query("select user_seq from js_user where user_hash = ?",$data['user_hash'])->row('user_seq');
		
		$query = 
		"INSERT INTO `daondb`.`js_device`
		(`user_seq`,
		`device_type`,
		`uuid`,
		`regid`,
		`registered_ts`,
		`is_enabled`,
		`created_ts`,
		`created_by`) 
		VALUES (?, ?, ?, ?, now(), 0, now(), ?)
		ON DUPLICATE KEY UPDATE
		`user_seq` = values(user_seq),
		`device_type` = values(device_type),
		`uuid` = values(uuid),
		`regid` = values(regid),
		`registered_ts` = now(),
		`is_enabled` = 0,
		`modified_ts` = now(),
		`modified_by` = values(created_by)";
		$this->db->query($query,array($userSeq,$data['dev_type'],$data['uuid'],$data['regid'],$_SERVER['REMOTE_ADDR']));		

		//dept hash update
		$devSeq = $this->db->query('select last_insert_id() a')->row('a');
		$devHash = md5("js_device".$devSeq);
		
		$query = "UPDATE js_device SET device_hash = ? where seq = ?";
		$this->db->query($query,array($devHash,$devSeq));
		
		$this->responsePayload['status'] = SUCCESS;
		$this->responsePayload['data'] = array("dev_hash"=>$devHash,"regid"=>$data['regid'],"uuid"=>$data['uuid']);
	}
	
	private function unregister() {
	
		$data = $this->requestPayload['data'];
		
		if( !isset($data['uuid']) || !isset($data['regid']) ||
				!$data['uuid'] || !$data['regid'] ) {
				
			$this->responsePayload['status'] = INSUFFICIENT_ARGUMENTS;
			$this->responsePayload['data'] = 'Insufficient Informations';
			return;
		}
		
		$query = "select seq from js_device where uuid = ? and regid = ?";
		if ( !$this->db->query($query,array($data['uuid'],$data['regid']))->row_array() ) {
			$this->responsePayload['status'] = NO_DATA;
			$this->responsePayload['data'] = "no data in table";
		} else {
			$query = "delete from js_device where uuid = ? and regid = ?";
			$this->db->query($query,array($data['uuid'],$data['regid']));
			$this->responsePayload['status'] = SUCCESS;
			$this->responsePayload['data'] = "success";
		}
	}
	
}


/* End of file device.php */