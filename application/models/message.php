<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
// Common Constants
define('NOT_SPECIFIED', -777);
define('MESSAGE_TYPE_DIVIDER', 100);
define('GCM_API_KEY',"AIzaSyD4LzxiJHYlNFBFcNQalmdDW3FDirnr4N4");

// Message Type Constants
define('MESSAGE_TYPE_CHAT', 0);
define('MESSAGE_TYPE_DOCUMENT', 1);
define('MESSAGE_TYPE_SURVEY', 2);


define('MESSAGE_KEY_CHAT', "CHAT");
define('MESSAGE_KEY_MEETING', "MEETING");
define('MESSAGE_KEY_COMMAND', "COMMAND");
define('MESSAGE_KEY_DOCUMENT', "DOCUMENT");
define('MESSAGE_KEY_SURVEY', "SURVEY");

// Message Sub Type Constants
define('TYPE_MEETING', 0);
define('TYPE_COMMAND', 1);

// Message Sub Type Constants
define('TYPE_RECEIVED', 0);
define('TYPE_DEPARTED', 1);
define('TYPE_FAVORITE', 2);

class Message extends CI_Model {
	
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
			case "SEND":
				$this->send();
				break;
			case "SET_CHECKED":
				$this->setMessageChecked();
				break;
			case "GET_UNCHECKERS":
				$this->getUncheckers();
				break;
			
			case "SURVEY":
				
				switch( $eventAr[2] ) {
					case "GET_RESULT":
						$this->surveyGetResult();
						break;
					case "ANSWER_SURVEY":
						$this->answerSurvey();
						break;
					default:
						die();
				}
				
				break;
		}
	}
	
	private function send() {
		/*
			-data:
				-sender_hash
				-receiver_hash
				-room_hash
				-message:
					-type
					-title
					-content
					-appendix
		 */
		
		$data = $this->requestPayload['data'];
		
		if( !isset($data['sender_hash']) || !isset($data['receiver_hash']) || !isset($data['room_hash']) || 
				!isset($data['message']) ||
				!$data['sender_hash'] || !$data['receiver_hash'] || !$data['message'] ) {
		
			$this->responsePayload['status'] = INSUFFICIENT_ARGUMENTS;
			$this->responsePayload['data'] = 'Insufficient Informations';
			return;
		}
		
		$msg = $data['message'];
		if ( !isset($msg['type']) || !isset($msg['title']) || !isset($msg['content']) ||!isset($msg['appendix']) || 
				!$msg['content'] ) {
			
			$this->responsePayload['status'] = INSUFFICIENT_ARGUMENTS;
			$this->responsePayload['data'] = 'Insufficient Informations';
			return;	
		}

		//get sender seq
		$senderSeq = $this->db->query('select seq from js_user where user_hash = ?',$data['sender_hash'])->row('seq');
		
		if ( !$senderSeq ) {
			
			$this->responsePayload['status'] = NO_DATA;
			$this->responsePayload['data'] = 'INVALID SENDER HASH';
			return;
		}
		
		/**
		 * DB 로깅 시작
		 */
		
		//메세지 타입
		$mType = floor($msg['type'] / MESSAGE_TYPE_DIVIDER);
		$mSubType = floor($msg['type'] % MESSAGE_TYPE_DIVIDER);
		
		//메세지 타입 별로 각자 다른 테이블에 로깅
		switch($mType) {
			case MESSAGE_TYPE_CHAT :
				$query = 'INSERT INTO js_chat (content, appendix, created_ts, created_by) VALUES ( ? , ? , now(), ?)';
				$this->db->query($query,array($msg['content'],$msg['appendix'],$senderSeq));
				$chat_seq = $this->db->query('select last_insert_id() a')->row('a');
				$hash = md5('js_chat'.$chat_seq);
				$this->db->query('update js_chat set chat_hash = ? where seq = ?',array($hash,$chat_seq));
				$queryForHistory = "INSERT INTO js_chat_history (sender_seq, receiver_seq, chat_seq, is_checked, created_ts) VALUES ";
				break;
			case MESSAGE_TYPE_DOCUMENT :
				$query = 'INSERT INTO js_document (title, content, appendix, created_ts, created_by) 
						VALUES ( ?, ? , ? , now(), ?)';
				$this->db->query($query,array($msg['title'], $msg['content'], $msg['appendix'], $senderSeq));
				$document_seq = $this->db->query('select last_insert_id() a')->row('a');
				$hash = md5('js_document'.$document_seq);
				$this->db->query('update js_chat set document_hash = ? where seq = ?',array($hash,$document_seq));
				$queryForHistory = "INSERT INTO js_document_history (sender_seq, receiver_seq, document_seq, is_checked, created_ts) VALUES ";
				break;
			case MESSAGE_TYPE_SURVEY :
				$query = 'INSERT INTO js_survey (title, content, appendix, created_ts, created_by)
						VALUES ( ?, ? , ? , now(), ?)';
				$this->db->query($query,array($msg['title'], $msg['content'], $msg['appendix'], $senderSeq));
				$survey_seq = $this->db->query('select last_insert_id() a')->row('a');
				$hash = md5('js_survey'.$survey_seq);
				$this->db->query('update js_survey set survey_hash = ? where seq = ?',array($hash,$survey_seq));
				$queryForHistory = "INSERT INTO js_survey_history (sender_seq, receiver_seq, survey_seq, is_checked, is_answered, created_ts) VALUES ";
				break;
		}
		
		$seq = $this->db->query('select last_insert_id() a')->row('a');
		
		$binds = array();
		$receiverSeqArray = array();
		if ( is_array($data['receiver_hash']) ) {
			foreach ( $data['receiver_hash'] as $receiver ) {
				$receiverSeq = $this->db->query('select seq from js_user where user_hash = ?',$receiver)->row('seq');
				$receiverSeqArray[] = $receiverSeq;
				if ( !$receiverSeq ) {
					$this->responsePayload['status'] = NO_DATA;
					$this->responsePayload['data'] = 'INVALID RECEIVER HASH';
					return;
				}
				
				switch($mType) {
					case MESSAGE_TYPE_CHAT :
						$queryForHistory.=
							"(?, ?, ?, 0, now()),";
						$binds = array_merge($binds,array($senderSeq,$receiverSeq,$seq));
						break;
					case MESSAGE_TYPE_DOCUMENT :
						$queryForHistory.=
							"(?, ?, ?, 0, now()),";
						$binds = array_merge($binds,array($senderSeq,$receiverSeq,$seq));
						break;
					case MESSAGE_TYPE_SURVEY :
						$queryForHistory.=
							"(?, ?, ?, 0, 0, now()),";
						$binds = array_merge($binds,array($senderSeq,$receiverSeq,$seq));
						break;
				}
			}
		} else {
			
			$receiverSeq = $this->db->query('select seq from js_user where user_hash = ?',$data['receiver_hash'])->row('seq');
			$receiverSeqArray[] = $receiverSeq;
			
			if ( !$receiverSeq ) {
				$this->responsePayload['status'] = NO_DATA;
				$this->responsePayload['data'] = 'INVALID RECEIVER HASH';
				return;
			}
			
			switch($mType) {
				case MESSAGE_TYPE_CHAT :
					$queryForHistory.=
					"(?, ?, ?, 0, now()),";
					$binds = array_merge($binds,array($senderSeq,$receiverSeq,$seq));
					break;
				case MESSAGE_TYPE_DOCUMENT :
					$queryForHistory.=
					"(?, ?, ?, 0, now()),";
					$binds = array_merge($binds,array($senderSeq,$receiverSeq,$seq));
					break;
				case MESSAGE_TYPE_SURVEY :
					$queryForHistory.=
					"(?, ?, ?, 0, 0, now()),";
					$binds = array_merge($binds,array($senderSeq,$receiverSeq,$seq));
					break;
			}
			
		}
		$queryForHistory = substr_replace($queryForHistory,'',-1,1);

		$this->db->query($queryForHistory,$binds);
		

		/**
		 * DB 로깅 끝
		 */
		
		
		/**
		 * GCM에 보내기
		 */
		$regIdArray = array();
		
		foreach ( $receiverSeqArray as $r ) {
			$regIdArray[] = $this->db->query('select regid from js_device where user_seq = ? and device_type in ("a","i") ',$r)->row('regid');
		}
		
		$deviceStatus = -1;//아직 아무것도 구현 안함
		if((($mType) == MESSAGE_TYPE_CHAT) && (($mSubType) == TYPE_COMMAND) ) {
			$packet = array('departingTS'=>time(), 'deviceStatus'=>$deviceStatus, 'payload'=>$this->requestPayload);
			foreach ( $regIdArray as $key=>$regid ) {
				$sendResult = $this->_gcmMessage(GCM_API_KEY, array($regid), $packet);
			}
		
		} else {
		
			// TODO : 전송을 위한 Packet Class 생성
			$packet = array('departingTS'=>time(), 'deviceStatus'=>$deviceStatus, 'payload'=> $this->requestPayload);
		
				
			//-----------------------
			//  메시지 전송
			//-----------------------
			//GCM 서비스를 위한 세팅; Google Api Key for Server Apps
			
			// 메시지를 보낸다.
			$sendResult = $this->_gcmMessage(GCM_API_KEY, $regIdArray, $packet);
		
		}
		
		
		$this->responsePayload['status'] = SUCCESS;
		$this->responsePayload['data'] = array("msg" => $sendResult, "receiverIds"=>$this->requestPayload['data']['receiver_hash'], "regids" => $regIdArray, 'status' => '1', "msg_hash" => $hash);
	}
	
	private function _gcmMessage ($auth, $regids, $packet) {
	
		// 메시지 데이터
	
		$data = array(	'registration_ids' => $regids,	'data' => $packet	);
	
		// 메시지 헤더
		$headers = array(
				"Content-Type:application/json",
				"Authorization:key=".$auth
		);
			
	
		// cURL 을 이용한 GCM 서버로의 메시지 전달. (전송 명령)
		
			$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://android.googleapis.com/gcm/send");	//
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);							//
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);						//
		curl_setopt($ch, CURLOPT_POST, 1);											//
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));	//
		$result = curl_exec($ch);
		curl_close($ch);
		
		/*$jsonData = json_encode($data);
		$json = str_replace('"', "\\\"", $jsonData);
	
		$curl = 'curl';
		$curl .= ' -k';
		$curl .= ' -X POST';
		$curl .= ' -H "Authorization:key=AIzaSyD4LzxiJHYlNFBFcNQalmdDW3FDirnr4N4"';
		$curl .= ' -H "Content-Type: application/json"';
		$curl .= ' https://android.googleapis.com/gcm/send';
		$curl .= ' -d "'.$json.'"';
		//echo json_encode($data);
		//echo exec($curl ,null,$result = array());
		exec($curl, $result);
	*/
		return $result;
	}
	
	private function getUncheckers() {
		$data = $this->requestPayload['data'];
		
		if( !isset($data['type']) || !isset($data['msg_hash']) ||
				!$data['msg_hash'] ) {
		
			$this->responsePayload['status'] = INSUFFICIENT_ARGUMENTS;
			$this->responsePayload['data'] = 'Insufficient Informations';
			return;
		}
		
		$messageType = floor($data['type']% MESSAGE_TYPE_DIVIDER);
		
		$tableName;
		$sql = "";
		switch($messageType) {
			case MESSAGE_TYPE_CHAT:
				$tableName = 'js_chat_history';
				$sql = 'SELECT receiver_seq, user_hash FROM ' . $tableName . ' a left join 
						js_user u on receiver_seq = u.seq 
						left join js_chat c
							on c.seq = a.chat_seq
						WHERE chat_hash ="'.$data['msg_hash'].'" AND is_checked=0';
				break;
	
			case MESSAGE_TYPE_DOCUMENT:
				$tableName = 'js_document_history';
				$sql = 'SELECT receiver_seq, user_hash FROM ' . $tableName . ' a left join
						js_user u on receiver_seq = u.seq 
						left join js_document d on d.seq = a.document_seq 
						WHERE document_hash= "'.$data['msg_hash'].'" AND is_checked=0';
				break;
					
			case MESSAGE_TYPE_SURVEY:
				$tableName = 'js_survey_history';
				$sql = 'SELECT receiver_seq, user_hash FROM ' . $tableName . '  a left join 
						js_user u on receiver_seq = u.seq 
						left join js_survey s on s.seq = a.survey_seq 
						WHERE survey_hash = "'.$data['msg_hash'].'" AND is_checked=0';
		}
	
		$res = $this->db->query($sql)->result_array();
	
		$uncheckers = array();
		foreach ( $res as $row ) {
			$uncheckers[] = $row['user_hash'];	
		}
	
		$this->responsePayload['status'] = SUCCESS;
		$this->responsePayload['data'] = $uncheckers;
	}
	
	private function setMessageChecked() {
		$data = $this->requestPayload['data'];
		
		if( !isset($data['type']) || !isset($data['msg_hash']) || !isset($data['user_hash']) ||
				 !$data['msg_hash'] || !$data['user_hash'] ) {
		
			$this->responsePayload['status'] = INSUFFICIENT_ARGUMENTS;
			$this->responsePayload['data'] = 'Insufficient Informations';
			return;
		}
		
		$messageType = floor($data['type'] / MESSAGE_TYPE_DIVIDER);
		$userSeq = $this->db->query('select seq from js_user where user_hash = ?',$data['user_hash'])->row('seq');

		if ( !$userSeq ) {
			$this->responsePayload['status'] = NO_DATA;
			$this->responsePayload['data'] = 'NO USER';			
			return;
		}
		
		$tableName;
		$sql = "";
		switch($messageType) {
			case MESSAGE_TYPE_CHAT:
				$q = 'select seq from js_chat where chat_hash = ?';
				$msgSeq = $this->db->query($q,$data['msg_hash'])->row('seq');
				$tableName = 'js_chat_history';				
				$sql = 'UPDATE `' . $tableName . '` SET is_checked= 1  , checked_ts = now() WHERE chat_seq = '.$msgSeq.' AND receiver_seq='. $userSeq ;
				break;
	
			case MESSAGE_TYPE_DOCUMENT:
				$q = 'select seq from js_document where document_hash = ?';
				$msgSeq = $this->db->query($q,$data['msg_hash'])->row('seq');
				$tableName = 'js_document_history';
				$sql = 'UPDATE `' . $tableName . '` SET is_checked= 1 , checked_ts = now() WHERE document_seq='.$msgSeq.' AND receiver_seq='. $userSeq;				
				break;
					
			case MESSAGE_TYPE_SURVEY:
				$q = 'select seq from js_survey where survey_hash = ?';
				$msgSeq = $this->db->query($q,$data['msg_hash'])->row('seq');
				$tableName = 'js_survey_history';
				$sql = 'UPDATE `' . $tableName . '` SET is_checked= 1 , checked_ts = now() WHERE survey_seq='.$msgSeq.' AND receiver_seq='. $userSeq;
		}
	
		$query = $this->db->query($sql);
		
		$this->responsePayload['status'] = SUCCESS;
		$this->responsePayload['data'] = "success";
	}
	
	private function surveyGetResult(){
		$data = $this->requestPayload['data'];
	
		if( !isset($data['survey_hash']) || !$data['survey_hash'] ) {
	
			$this->responsePayload['status'] = INSUFFICIENT_ARGUMENTS;
			$this->responsePayload['data'] = 'Insufficient Informations';
			return;
		}
	
		$seq = $this->db->query('select seq from js_survey where survey_hash = ?',$data['survey_hash'])->row('seq');
	
		$sql = 'SELECT receiver_seq FROM `js_survey_history` WHERE survey_seq='.$seq;
		$query = $this->db->query($sql);
		$nReceivers = $query->num_rows();
	
		$sql = 'SELECT answersheet FROM `js_survey_history` WHERE survey_seq='.$seq.' AND answered=1';
		$query = $this->db->query($sql);
		$_ass = $query->result_array();
		$nResponders = count($_ass);
	
		$result = array();
	
		for($ri = 0; $ri<$nResponders; $ri++) {
			$_as = $_ass[$ri];
			$as = json_decode($_as['answersheet'], true);
	
			for($qi=0; $qi<count($as); $qi++) {
	
				if(!array_key_exists($qi,$result)) {
					$result[$qi]=array();
				}
				$a = $as[$qi];
				$m = $a['isMultiple'];
				$os = $a['options'];
	
				if($m) {
					for($oi = 0; $oi < count($os); $oi++) {
						if(!array_key_exists($os[$oi],$result[$qi])) {
							$result[$qi][ $os[$oi] ] = 1;
						} else {
							$result[$qi][ $os[$oi] ] += 1;
						}
	
					}
				} else {
					if(!array_key_exists($os,$result[$qi])) {
						$result[$qi][$a['options']] = 1;
					} else {
						$result[$qi][$a['options']] += 1;
					}
	
				}	//$m END
	
			}	// for nQuestions END
	
		}	// for nResponders END
	
		$this->responsePayload['status'] = SUCCESS;
		$this->responsePayload['data'] = array('result'=> $result, 'nReceivers'=>$nReceivers, 'nResponders'=>$nResponders);
	}
	
	private function answerSurvey () {
		$data = $this->requestPayload['data'];
	
		if( !isset($data['user_hash']) || !isset($data['survey_hash']) || !isset($data['answersheet']) ||
				!$data['user_hash'] || !$data['survey_hash'] || !$data['answersheet'] ) {
	
			$this->responsePayload['status'] = INSUFFICIENT_ARGUMENTS;
			$this->responsePayload['data'] = 'Insufficient Informations';
			return;
		}
		$userSeq = $this->db->query('select seq from js_user where user_hash = ?',$data['user_hash'])->row('seq');
		$surveySeq = $this->db->query('select seq from js_survey where survey_hash = ?',$data['survey_hash'])->row('seq');
		$answersheet = $data['answersheet'];
			
		$sql = 'UPDATE `js_survey_history` SET answersheet="'.$answersheet.'" , answered_ts = now() WHERE survey_seq ='.$surveySeq.' AND receiver_seq ='.$userSeq;
		$this->db->query($sql);
	
		$this->responsePayload['status'] = SUCCESS;
		$this->responsePayload['data'] = "success";
	}
}

/* End of message.php */