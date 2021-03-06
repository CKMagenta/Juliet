<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
// Common Constants
define('NOT_SPECIFIED', -777);
define('MESSAGE_TYPE_DIVIDER', 100);
define('GCM_API_KEY',"AIzaSyD4LzxiJHYlNFBFcNQalmdDW3FDirnr4N4");

// Message Type Constants
define('MESSAGE_TYPE_CHAT', 0);
define('MESSAGE_TYPE_DOCUMENT', 1);
define('MESSAGE_TYPE_SURVEY', 2);

// Message Sub Type Constants
define('TYPE_MEETING', 0);
define('TYPE_COMMAND', 1);

// Message Sub Type Constants
define('TYPE_RECEIVED', 0);
define('TYPE_DEPARTED', 1);
define('TYPE_FAVORITE', 2);

class Message extends CI_Model {
	
	protected static $eventAr;
	protected $requestPayload;
	protected static $responsePayload; //controller에 있는 responsePayload를 참조하는 변수

	function __construct() {
  		parent::__construct();
		$this->load->database();
	}
	
	public function act($handler,$eventAr,$requestPayload) {
		$this->eventAr = $eventAr;
		
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

				$model = new SurveyModel();
				switch( $eventAr[2] ) {
					case "GET_RESULT":
						$model->getSurveyResult();
						break;
					case "GET_CONTENT":
						$model->getSurveyContent();
						break;
					case "ANSWER_SURVEY":
						$model->answerSurvey();
						break;
					default:
						die();
				}
				
				break;
			case "CHAT":
				$model = new ChatModel();
				switch( $eventAr[2] ) {
					case "CREATE_ROOM":
						$model->createRoom();
						break;
					case "PULL_LAST_READ_TS":
						$model->pullLastReadTS();
						break;
					case "NOTIFY_LAST_READ_TS":
						$model->updateLastReadTS();
						break;
					case "INVITE":
						$model->inviteUser();
						break;
					case "LEAVE_ROOM":
						$model->leaveRoom();
						break;
					default:
						die();
				}
				break;
		}
	}
	
	private function send() {
		$data = Handler::$requestPayload['data'];
		if( !isset($data[0][KEY::_MESSAGE]) || !$data[0][KEY::_MESSAGE] ) {
		
			Handler::$responsePayload['status'] = STATUS::INSUFFICIENT_ARGUMENTS;
			Handler::$responsePayload['data'][] = array(KEY::RESPONSE_TEXT=>'Insufficient Informations msg not set');
			return;
		}
		
		$msg = $data[0][KEY::_MESSAGE];
		if (!isset($msg[KEY_MESSAGE::SENDER_IDX]) || !isset($msg[KEY_MESSAGE::RECEIVERS_IDX]) ||
				 !isset($msg[KEY_MESSAGE::TYPE]) || !isset($msg[KEY_MESSAGE::TITLE]) || !isset($msg[KEY_MESSAGE::CONTENT])  ) {
			
			Handler::$responsePayload['status'] = STATUS::INSUFFICIENT_ARGUMENTS;
			Handler::$responsePayload['data'][] = array(KEY::RESPONSE_TEXT=>"Insufficient Informations");
			return;	
		}

		//get sender seq
		$senderSeq = $this->db->query('select seq from js_user where user_hash = ?',$msg[KEY_MESSAGE::SENDER_IDX])->row('seq');
		
		if ( !$senderSeq ) {
			Handler::$responsePayload['status'] = STATUS::NO_DATA;
			Handler::$responsePayload['data'][] = array(KEY::RESPONSE_TEXT=>"Invalid Sender Hash");
			return;
		}
		
		/**
		 * DB 작업 시작
		 */
		//메세지 타입
		$mType = floor($msg[KEY_MESSAGE::TYPE] / MESSAGE_TYPE_DIVIDER);
		$mSubType = floor($msg[KEY_MESSAGE::TYPE] % MESSAGE_TYPE_DIVIDER);
		
		//메세지 타입 별로 각자 다	른 테이블에 로깅
		switch($mType) {
		case MESSAGE_TYPE_CHAT :
			$model = new ChatModel();
			break;
		case MESSAGE_TYPE_DOCUMENT :
			$model = new DocumentModel();
			break;
		case MESSAGE_TYPE_SURVEY :
			$model = new SurveyModel();
			break;
		}
		
		$hash = $model->save($msg);
		
		if ( $hash == false ) {
			return;
		}
		
		/**
		 * DB 작업 끝
		 */
		$receiverSeqArray = array();
		foreach ( $msg[KEY_MESSAGE::RECEIVERS_IDX] as $r ) {
			$query = "select seq from js_user where user_hash= ?";
			$receiverSeqArray[] = $this->db->query($query,$r)->row('seq');
		}
		
		/**
		 * GCM에 보내기
		 */
		$regIdArray = array();
		foreach ( $receiverSeqArray as $r ) {
			$regIdArray[] = $this->db->query('select regid from js_device where user_seq = ? and device_type in ("a","i") ',$r)->row('regid');
		}
		
		$msg[KEY_MESSAGE::IDX] = $hash;
		$sendResult = "";
		if ( count($regIdArray) > 0 ) {
			$gcmpayload = array(
					'event'=>'PUSH:MESSAGE',
					'status'=>STATUS::SUCCESS,
					'data'=>array(
							array(KEY::_MESSAGE=>$msg)
					)
			);
			
			if((($mType) == MESSAGE_TYPE_CHAT) && (($mSubType) == TYPE_COMMAND) ) {
				//지시와보고 1:N 채팅

				if ( $msg[KEY_CHAT::CONTENT_TYPE] == ChatModel::CONTENT_TYPE_USER_JOIN ) {
					return;
				}
				
				foreach ( $regIdArray as $key=>$regid ) {

					$gcmpayload['data'][0][KEY::_MESSAGE][KEY_MESSAGE::RECEIVERS_IDX] = array($msg[KEY_MESSAGE::RECEIVERS_IDX][$key]);
					
					$packet = array('payload'=>$gcmpayload);
					$sendResult = $this->_gcmMessage(GCM_API_KEY, array($regid), $packet);
				}
			} else {
				
				$packet = array('payload'=> $gcmpayload);
				$sendResult = $this->_gcmMessage(GCM_API_KEY, $regIdArray, $packet);
			}
			
		}
		
		Handler::$responsePayload['status'] = STATUS::SUCCESS; 
		Handler::$responsePayload['data'] = array( 
												array(KEY_GCM::SEND_RESULT	=>	$sendResult, 
													  KEY_MESSAGE::RECEIVERS_IDX	=>	$msg[KEY_MESSAGE::RECEIVERS_IDX], 
													  KEY_MESSAGE::RECEIVERS_REGISTRATION_IDS	=>	$regIdArray,
													  KEY_MESSAGE::IDX 	=> 	$hash)
												);
	}
	
	protected function _gcmMessage ($auth, $regids, $packet) {
		// 메시지 데이터
		$data = array(	'registration_ids' => $regids,	'data' => $packet	);

		$json = json_encode($data);
		$json = str_replace('"', "\\\"", $json);
		$curl = 'curl';
		$curl .= ' -k';
		$curl .= ' -X POST';
		$curl .= ' -H "Authorization:key=AIzaSyD4LzxiJHYlNFBFcNQalmdDW3FDirnr4N4"';
		$curl .= ' -H "Content-Type: application/json"';
		$curl .= ' http://android.googleapis.com/gcm/send';
		$curl .= ' -d "'.$json.'"';
		
		exec($curl, $result);
		
		$sendResult = json_decode($result[0],true);
		
		$this->handleResult($regids, $sendResult);
		
		return $sendResult;
	}
	
	private function handleResult($regids, $sendResult)
	{
		if ($sendResult['canonical_ids'] > 0)
		{
			foreach( $sendResult['results'] as $key=>$result )
			{
				if (array_key_exists('registration_id', $result))
				{
					$new_regid = $result['registration_id'];
					$this->db->query('update js_device set regid = ? where regid = ?',array($new_regid,$regids[$key]));
				}
			}
		}
	}
	
	private function getUncheckers() {
		$data = Handler::$requestPayload['data'];
		
		if( !isset($data[0][KEY_MESSAGE::TYPE]) || !isset($data[0][KEY_MESSAGE::IDX]) ||
				!$data[0][KEY_MESSAGE::IDX] ) {
		
			Handler::$responsePayload['status'] = STATUS::INSUFFICIENT_ARGUMENTS;
			Handler::$responsePayload['data'][] = array(KEY::RESPONSE_TEXT=>'Insufficient Informations');
			return;
		}
		$messageType = floor($data[0][KEY_MESSAGE::TYPE]/MESSAGE_TYPE_DIVIDER);
		$mType = floor($data[0][KEY_MESSAGE::TYPE] % MESSAGE_TYPE_DIVIDER);
		
		switch($messageType) {
			case MESSAGE_TYPE_DOCUMENT :
				$query = "select seq from js_document where doc_hash = ?";
				$seq = $this->db->query($query,array($data[0][KEY_MESSAGE::IDX]))->row('seq');
				$query = "select 
							b.user_hash ".KEY_USER::IDX."
						from js_document_receiver a
						left join js_user b
							on a.receiver_seq = b.seq
						where a.doc_seq = ? and a.is_checked = 0";
				break;
			case MESSAGE_TYPE_SURVEY :
				$query = "select seq from js_survey where survey_hash = ?";
				$seq = $this->db->query($query,array($data[0][KEY_MESSAGE::IDX]))->row('seq');
				$query = "select 
							b.user_hash ".KEY_USER::IDX."
						from js_survey_receiver a
						left join js_user b
							on a.receiver_seq = b.seq
						where a.survey_seq = ? and a.is_checked = 0";
				break;
		}
		
		if ( !$seq ) {
			Handler::$responsePayload['status'] = STATUS::NO_DATA;
			Handler::$responsePayload['data'][] = array(KEY::RESPONSE_TEXT=>"Invalid msg hash");
			return;
		}
		
		Handler::$responsePayload['data']= $this->db->query($query,$seq)->result_array();
		Handler::$responsePayload['status'] = STATUS::SUCCESS;
	}
	
	private function setMessageChecked() {
		$data = Handler::$requestPayload['data'];
		
		if( !isset($data[0][KEY_MESSAGE::TYPE]) || !isset($data[0][KEY_MESSAGE::IDX]) || !isset($data[0][KEY_USER::IDX]) ||
				 !$data[0][KEY_MESSAGE::IDX] || !$data[0][KEY_USER::IDX] ) {
		
			Handler::$responsePayload['status'] = STATUS::INSUFFICIENT_ARGUMENTS;
			Handler::$responsePayload['data'][] = array(KEY::RESPONSE_TEXT=>'Insufficient Informations');
			return;
		}
		
		$messageType = floor($data[0][KEY_MESSAGE::TYPE] / MESSAGE_TYPE_DIVIDER);
		$userSeq = $this->db->query('select seq from js_user where user_hash = ?',$data[0][KEY_USER::IDX])->row('seq');
		
		if ( !$userSeq ) {
			Handler::$responsePayload['status'] = STATUS::NO_DATA;
			Handler::$responsePayload['data'][] = array(KEY::RESPONSE_TEXT=>'NO VALID USER');
			return;
		}
		
		switch($messageType) {
			case MESSAGE_TYPE_DOCUMENT:
				$q = 'select seq from js_document where document_hash = ?';
				$msgSeq = $this->db->query($q,$data[0][KEY_MESSAGE::IDX])->row('seq');
				$tableName = 'js_document_receiver';
				$sql = 'UPDATE `' . $tableName . '` SET is_checked= 1 , checked_ts = now() WHERE doc_seq='.$msgSeq.' AND receiver_seq='. $userSeq;				
				break;
					
			case MESSAGE_TYPE_SURVEY:
				$q = 'select seq from js_survey where survey_hash = ?';
				$msgSeq = $this->db->query($q,$data[0][KEY_MESSAGE::IDX])->row('seq');
				$tableName = 'js_survey_receiver';
				$sql = 'UPDATE `' . $tableName . '` SET is_checked= 1 , checked_ts = now() WHERE survey_seq='.$msgSeq.' AND receiver_seq='. $userSeq;
		}
	
		$query = $this->db->query($sql);
		
		Handler::$responsePayload['status'] = STATUS::SUCCESS;
		Handler::$responsePayload['data'][] = array(KEY::RESPONSE_TEXT=>"SUCCESS");
	}
	
	
	
}

class ChatModel extends Message {

	const CONTENT_TYPE_TEXT = 1;
	const CONTENT_TYPE_PICTURE = 2;
	const CONTENT_TYPE_USER_LEAVE = 3;
	const CONTENT_TYPE_USER_JOIN = 4;
	
	public function save($chat) {
		$senderSeq = 
			$this->db->query('select seq from js_user where user_hash = ?',$chat[KEY_MESSAGE::SENDER_IDX])->row('seq');
		
		$query =
			"insert into js_chat(room_hash, chat_hash, chat_type, chat_content, chat_content_type, created_ts, creator_seq)
				values( ?, ?, ?, ?, ?, ?, ? ) ";
		
		$this->db->query($query,array(
				$chat[KEY_CHAT::ROOM_CODE],
				$chat[KEY_MESSAGE::IDX],
				$chat[KEY_MESSAGE::TYPE], 
				$chat[KEY_MESSAGE::CONTENT], 
				$chat[KEY_CHAT::CONTENT_TYPE], 
				$chat[KEY_MESSAGE::CREATED_TS], 
				$senderSeq ));
		
		$chat_seq = $this->db->insert_id();
		$chat_hash = $chat[KEY_MESSAGE::IDX];
		
		$query = 
			"insert into js_chat_receiver(chat_seq, receiver_seq, is_checked)
				values ";
		
		$receivers = $chat[KEY_MESSAGE::RECEIVERS_IDX];
		$binds = array();
		foreach ( $receivers as $recv ) {
			$recv_seq = $this->db->query('select seq from js_user where user_hash = ?',$recv)->row('seq');
			$query .= "(?, ?, 0),";
			$binds[] = $chat_seq;
			$binds[] = $recv_seq;
		}
		$query = substr_replace($query,'',-1,1);
		$this->db->query($query,$binds);
		
		return $chat_hash;
	}
	
	public function createRoom() {
		$room_info = Handler::$requestPayload['data'][0];
		
		$room_hash = $room_info[KEY_CHAT::ROOM_CODE];
		$room_member = $room_info[KEY_CHAT::ROOM_MEMBER];
		$room_type = $room_info[KEY_MESSAGE::TYPE];
		$host_hash = $room_info[KEY_USER::IDX];

		$host_seq = $this->db->query('select seq from js_user where user_hash = ? ', $host_hash)->row('seq');
		
		$query = "insert into js_room(room_hash, room_type, room_host_seq) values(?,?,?)";
		$this->db->query($query,array($room_hash,$room_type,$host_seq));
		
		$query = 
			"insert into js_room_chatter (room_hash, user_seq, last_read_ts)
				values (?, ?, now()),";
		$binds = array($room_hash,$host_seq);
		foreach( $room_member as $member ) 
		{
			$user_seq = $this->db->query('select seq from js_user where user_hash = ?',$member)->row('seq');
			if ( !$user_seq ) 
			{
				Handler::$responsePayload['status'] = STATUS::NO_DATA;
				Handler::$responsePayload['data'][] = array(KEY::RESPONSE_TEXT=>"unregistered user hash");
				return;
			} 
			
			$query .= "(?, ?, now()),";
			$binds[] = $room_hash;
			$binds[] = $user_seq;
		}
		$query = substr_replace($query, "", -1,1);
		$this->db->query($query,$binds);
		Handler::$responsePayload['status'] = STATUS::SUCCESS;
		Handler::$responsePayload['data'][] = array(KEY::RESPONSE_TEXT=>"successful");
		return;
	}
	
	public function inviteUser() {
		$input = Handler::$requestPayload['data'][0];
		
		$new_members = $input[KEY_CHAT::ROOM_MEMBER];
		$room_code = $input[KEY_CHAT::ROOM_CODE];
		$inviter = $input[KEY_USER::IDX];
		
		if (count($new_members)==0)
		{
			return;
		}
		
		$query = 'select room_host_seq, room_type from js_room where room_hash = ?';
		$room = $this->db->query($query,array($room_code))->row_array();
		
		if (!$room)
		{
			Handler::$responsePayload['status'] = STATUS::NO_DATA;
			Handler::$responsePayload['data'][] = array(KEY::RESPONSE_TEXT=>"no room ");
			return;
		}
		
		$inviter_seq = $this->db->query("select seq from js_user where user_hash = ?",$inviter)->row('seq');
		
		if ($room['room_type'] == TYPE_MEETING)
		{
			$query = "select regid from js_device d where user_seq in (select user_seq from js_room_chatter where room_hash = ? and user_seq != ?)";
			$result = $this->db->query($query,array($room_code,$inviter_seq))->result_array();
			
			$regIds = array();
			foreach ($result as $row)
			{
				$regIds[] = $row['regid'];
			}
			
			$gcmPayload = array(
					"event"=>"PUSH:USER_JOIN_ROOM",
					"data"=>array(
							array(
								KEY_CHAT::ROOM_CODE=>$room_code,
								KEY_USER::IDX=>$inviter,
								KEY_CHAT::ROOM_MEMBER=>$new_members
					)
				)
			);
			
			$packet = array('payload'=>$gcmPayload);
			$this->_gcmMessage(GCM_API_KEY, $regIds, $packet);
			
		}
		else
		{
			// COMMAND에서는 알림할필요 ㄴㄴ
			if ($room['room_host_seq'] != $inviter_seq)
			{
				Handler::$responsePayload['status'] = STATUS::INSUFFICIENT_ARGUMENTS;
				Handler::$responsePayload['data'][] = array(KEY::RESPONSE_TEXT=>"호스트아니면 초대 ㄴㄴ");
				return;
			}
		}
		
		$query = "insert into js_room_chatter (room_hash, user_seq, last_read_ts)
				values ";
		$binds = array();
		foreach ( $new_members as $newbie ) {
			$query .= "(?, ?, now()),";
			$binds[] = $room_code;
			$binds[] = $this->db->query('select seq from js_user where user_hash = ? ',$newbie)->row('seq');
			
		}
		$query = substr_replace($query,"",-1,1);
		$query .= " on duplicate key update user_seq = user_seq";
		$this->db->query($query,$binds);
		
		Handler::$responsePayload['status'] = STATUS::SUCCESS;
		Handler::$responsePayload['data'][] = array(KEY::RESPONSE_TEXT=>"successful");
	}
	
	public function leaveRoom() {
		$room_code = Handler::$requestPayload['data'][0][KEY_CHAT::ROOM_CODE];
		$user_idx = Handler::$requestPayload['data'][0][KEY_USER::IDX];
		
		$user_seq = $this->db->query("SELECT seq FROM js_user WHERE user_hash = ?",$user_idx)->row('seq');
		
		$query = "SELECT room_type, room_host_seq FROM js_room WHERE room_hash = ?";
		$result = $this->db->query($query,array($room_code))->row_array();
		
		$host_seq = $result['room_host_seq'];
		$room_type = $result['room_type'];
		$regIds = array();
		if($room_type==TYPE_MEETING)
		{
			$query = "DELETE FROM js_room_chatter WHERE room_hash = ? AND user_seq = ?";
			$this->db->query($query,array($room_code, $user_seq));
			
			$query = "SELECT count(seq) as num_chatters FROM js_room_chatter WHERE room_hash = ?";
			$num_chatters = $this->db->query($query,$room_code)->row('num_chatters');
			if ($num_chatters==0)
			{
				$query = "DELETE FROM js_room WHERE room_hash = ?";
				$this->db->query($query,$room_code);
			}
			else
			{
				$query = "SELECT regid FROM js_device WHERE user_seq IN ( SELECT user_seq FROM js_room_chatter WHERE room_hash = ? )";
				$result = $this->db->query($query,array($room_code))->result_array();
				
				foreach ($result as $row)
				{
					$regIds[] = $row['regid'];
				}
			}
		}
		else
		{
			if ($user_seq == $host_seq)
			{
				$query = "SELECT regid FROM js_device WHERE user_seq IN ( SELECT user_seq FROM js_room_chatter WHERE room_hash = ? AND user_seq != ? )";
				$result = $this->db->query($query,array($room_code,$host_seq))->result_array();
				
				foreach ($result as $row)
				{
					$regIds[] = $row['regid'];
				}
				
				$query = "DELETE FROM js_room_chatter WHERE room_hash = ?";
				$this->db->query($query,$room_code);
				$query = "DELETE FROM js_room WHERE room_hash = ?";
				$this->db->query($query,$room_code);
			}
			else
			{
				$query = "DELETE FROM js_room_chatter WHERE room_hash = ? AND user_seq = ?";
				$this->db->query($query,array($room_code, $user_seq));
				
				$query = "SELECT count(seq) as num_chatters FROM js_room_chatter WHERE room_hash = ?";
				$num_chatters = $this->db->query($query,$room_code)->row('num_chatters');
				if ($num_chatters==0)
				{
					$query = "DELETE FROM js_room WHERE room_hash = ?";
					$this->db->query($query,$room_code);
				}
				
				$query = "SELECT regid FROM js_device WHERE user_seq = ?";
				$result = $this->db->query($query,$host_seq)->row_array();
				$regIds = array($result['regid']);
			}
		}
		
		if (count($regIds) > 0)
		{
			$payload = array(
					'event'=>'PUSH:USER_LEAVE_ROOM',
					'data'=>array(array(
							KEY_CHAT::ROOM_CODE		=>	$room_code,
							KEY_USER::IDX			=>	$user_idx
					))
			);
			
			$send_result = $this->_gcmMessage(GCM_API_KEY, $regIds, array('payload'=>$payload));
		}
		
		
		Handler::$responsePayload['status'] = STATUS::SUCCESS;
		Handler::$responsePayload['data'][] = array(KEY::RESPONSE_TEXT=>"successful");
	}
	
	public function updateLastReadTS(){
		$input = Handler::$requestPayload['data'][0];
		$user_seq = $this->db->query('select seq from js_user where user_hash = ?',$input[KEY_USER::IDX])->row('seq');
		
		$last_read_ts = floor( $input[KEY_CHAT::LAST_READ_TS] );
		$query = "update js_room_chatter set last_read_ts = from_unixtime(?) where room_hash= ? and user_seq = ?";
		$this->db->query($query,array($last_read_ts, $input[KEY_CHAT::ROOM_CODE],$user_seq));
		
		$payload = array(
				'event'=>'PUSH:UPDATE_LAST_READ_TS',
				'data'=>array(array(
						KEY_CHAT::LAST_READ_TS=>$last_read_ts,
						KEY_CHAT::ROOM_CODE=>$input[KEY_CHAT::ROOM_CODE],
						KEY_USER::IDX=>$input[KEY_USER::IDX]
						))
				);
		$data = array( 'payload'=>$payload );
		
		$query = "select room_type from js_room where room_hash = ? ";
		$room_type = $this->db->query($query,$input[KEY_CHAT::ROOM_CODE])->row('room_type');
		
		if ( $room_type == TYPE_MEETING ) {
			$regIds = array();
			
			$query = "select regid from js_device where user_seq in ( select user_seq from js_room_chatter where room_hash = ? and user_seq != ?)";
			$result = $this->db->query($query,array($input[KEY_CHAT::ROOM_CODE],$user_seq))->result_array();
			
			foreach ( $result as $row ) {
				$regIds[] = $row['regid'];
			}
			
			$result = $this->_gcmMessage(GCM_API_KEY, $regIds, $data);
			
		} else {
			
			$query = "select room_host_seq from js_room where room_hash = ? ";
			$room_host_seq = $this->db->query($query,$input[KEY_CHAT::ROOM_CODE])->row('room_host_seq');
			if ( $room_host_seq == $user_seq ) {
				
				$regIds = array();
					
				$query = "select regid from js_device where user_seq in ( select user_seq from js_room_chatter where room_hash = ? )";
				$result = $this->db->query($query,$input[KEY_CHAT::ROOM_CODE])->result_array();
					
				foreach ( $result as $row ) {
					$regIds[] = $row['regid'];
				}
					
				$result = $this->_gcmMessage(GCM_API_KEY, $regIds, $data);				
				
			} else { 
				$regIds = array();
					
				$query = "select regid from js_device where user_seq = ? ";
				$result = $this->db->query($query,$room_host_seq)->result_array();
					
				foreach ( $result as $row ) {
					$regIds[] = $row['regid'];
				}
					
				$result = $this->_gcmMessage(GCM_API_KEY, $regIds, $data);				
			}
			
		}		
		
		
		Handler::$responsePayload['status'] = STATUS::SUCCESS;
		Handler::$responsePayload['data'][] = array(KEY::RESPONSE_TEXT=>"successful");
		return;
	}
	
	public function pullLastReadTS() {
		$room_info = Handler::$requestPayload['data'][0];
		
		if ( !isset($room_info[KEY_CHAT::ROOM_CODE]) || !$room_info[KEY_CHAT::ROOM_CODE] ) {
			Handler::$responsePayload['status'] = STATUS::INSUFFICIENT_ARGUMENTS;
			Handler::$responsePayload['data'][] = array(KEY::RESPONSE_TEXT=>"insufficient arguments");
			return;
		}
		
		$room_hash = $room_info[KEY_CHAT::ROOM_CODE];
		$user_idx = $room_info[KEY_USER::IDX];
		
		$query = "select 
					user_hash ".KEY_USER::IDX.", 
					unix_timestamp(last_read_ts) ".KEY_CHAT::LAST_READ_TS."
				from js_room_chatter c left join js_user u on u.seq = c.user_seq
				where c.room_hash = ? and u.user_hash != ?";
		$result = $this->db->query($query,array($room_hash, $user_idx))->result_array();
		
		Handler::$responsePayload['status'] = STATUS::SUCCESS;
		Handler::$responsePayload['data'] = $result;
	}
	
	public function notifyLastReadTS()
	{
		$info = Handler::$requestPayload['data'][0];
		$room_code = $info[KEY_CHAT::ROOM_CODE];
		$user_idx = $info[KEY_USER::IDX];
		$last_read_ts = $info[KEY_CHAT::LAST_READ_TS];
		
		$user_seq = $this->db->query('SELECT seq FROM js_user WHERE user_hash = ?',$user_idx)->row('seq');
				
		$query = 'UPDATE js_room_chatter SET last_read_ts = from_unixtime(?) WHERE room_hash = ? AND user_seq = ?';
		$this->db->query($query,array($last_read_ts, $room_code, $user_seq));
		
		$query = 'SELECT regid FROM js_device WHERE user_seq in (SELECT user_seq FROM js_room_chatter WHERE room_hash = ? AND user_seq != ?)';
		$result = $this->db->query($query,array($room_code, $user_seq))->result_array();
		
		$regIds = array();
		foreach ($result as $row)
		{
			$regIds[] = $row['regid'];
		}
		
		$payload = array(
					'event'=>'PUSH:UPDATE_LAST_READ_TS', 
					'data'=>array(
							KEY_CHAT::LAST_READ_TS	=>	$last_read_ts,
							KEY_CHAT::ROOM_CODE		=>	$room_code,
							KEY_USER::IDX			=>	$user_idx
							)
				);
		
		$send_result = $this->_gcmMessage(GCM_API_KEY, $regIds, array('payload'=>$payload));
		
		Handler::$responsePayload['status'] = STATUS::SUCCESS;
		Handler::$responsePayload['data'][] = array(KEY::RESPONSE_TEXT=>"successful");
	}
}

class DocumentModel extends Message {
	public function save($doc) {
		$senderSeq =
			$this->db->query('select seq from js_user where user_hash = ?',$doc[KEY_MESSAGE::SENDER_IDX])->row('seq');
		
		$query =
		"insert into js_document(doc_type, doc_title, doc_content, created_ts, creator_seq)
				values( ?, ?, ?, ?, ? ) ";
		$this->db->query($query,array($doc[KEY_MESSAGE::TYPE], $doc[KEY_MESSAGE::TITLE], $doc[KEY_MESSAGE::CONTENT], $doc[KEY_MESSAGE::CREATED_TS], $senderSeq ));
		$doc_seq = $this->db->insert_id();
		$doc_hash = md5('js_document'.$doc_seq);
		
		$query = "update js_document set doc_hash = ? where seq = ?";
		$this->db->query($query, array($doc_hash,$doc_seq) );
		
		$query =
		"insert into js_document_receiver(doc_seq, receiver_seq, is_checked)
				values ";
		
		$receivers = $doc[KEY_MESSAGE::RECEIVERS_IDX];
		$binds = array();
		foreach ( $receivers as $recv ) {
			$recv_seq = $this->db->query('select seq from js_user where user_hash = ?',$recv)->row('seq');
			$query .= "(?, ?, 0),";
			$binds[] = $doc_seq;
			$binds[] = $recv_seq;
		}
		$query = substr_replace($query,'',-1,1);
		$this->db->query($query,$binds);
		
		//forward
		if ( isset($doc[KEY_DOCUMENT::FORWARDS]) ) {
			$forwards = $doc[KEY_DOCUMENT::FORWARDS];
			if ( count($forwards)>0 ) {
				$query =
				"insert into js_document_forward(doc_seq, forwarder_seq, forward_comment, forward_ts)
					values ";
				
				$binds = array();
				foreach ( $forwards as $fwd ) {
					$fwder_seq = $this->db->query('select seq from js_user where user_hash = ?',$fwd[KEY_DOCUMENT::FORWARDER_IDX])->row('seq');
					$query .= "(?, ?, ?, ?),";
					$binds[] = $doc_seq;
					$binds[] = $fwder_seq;
					$binds[] = $fwd[KEY_DOCUMENT::FORWARD_CONTENT];
					$binds[] = $fwd[KEY_DOCUMENT::FORWARD_TS];
				}
				$query = substr_replace($query,'',-1,1);
				$this->db->query($query,$binds);
			}
		}
		
		if ( isset($doc[KEY_DOCUMENT::FILES]) ) {
			//attach
			$files = $doc[KEY_DOCUMENT::FILES];
			if ( count($files)>0 ) {
				$query =
				"insert into js_document_attachments(doc_seq, file_type, file_name, file_hash, file_size_in_byte)
					values ";
					
				$binds = array();
				foreach ( $files as $file ) {
					$query .= "(?, ?, ?, ?),";
					$binds[] = $doc_seq;
					$binds[] = $file[KEY_DOCUMENT::FILE_TYPE];
					$binds[] = $file[KEY_DOCUMENT::FILE_NAME];
					$binds[] = $file[KEY_DOCUMENT::FILE_HASH];
					$binds[] = $file[KEY_DOCUMENT::FILE_SIZE];
				}
				$query = substr_replace($query,'',-1,1);
				$this->db->query($query,$binds);
			}
		}
		return $doc_hash;
	}
}
class SurveyModel extends Message {
	
	public function save($svy) {
		
		$senderSeq =
			$this->db->query('select seq from js_user where user_hash = ?',$svy[KEY_MESSAGE::SENDER_IDX])->row('seq');

		$query =
		"insert into js_survey(survey_title, survey_content, created_ts, creator_seq, open_ts, close_ts, is_result_public)
				values( ?, ?, from_unixtime(?), ?, from_unixtime(?), from_unixtime(?), ? ) ";
		$this->db->query($query,
				array( 
						$svy[KEY_MESSAGE::TITLE], 
						$svy[KEY_MESSAGE::CONTENT],
						$svy[KEY_MESSAGE::CREATED_TS], 
						$senderSeq,
						$svy[KEY_SURVEY::FORM][KEY_SURVEY::OPEN_TS],
						$svy[KEY_SURVEY::FORM][KEY_SURVEY::CLOSE_TS],
						$svy[KEY_SURVEY::FORM][KEY_SURVEY::IS_RESULT_PUBLIC]	));

		$svy_seq = $this->db->insert_id();
		$svy_hash = md5('js_survey'.$svy_seq);
		
		$query = "update js_survey set survey_hash = ? where seq = ?";
		$this->db->query($query, array($svy_hash,$svy_seq) );
		
		$query =
		"insert into js_survey_receiver(survey_seq, receiver_seq, is_checked, is_answered)
				values ";
		
		$receivers = $svy[KEY_MESSAGE::RECEIVERS_IDX];
		$binds = array();
		foreach ( $receivers as $recv ) {
			$recv_seq = $this->db->query('select seq from js_user where user_hash = ?',$recv)->row('seq');
			$query .= "(?, ?, 0, 0),";
			$binds[] = $svy_seq;
			$binds[] = $recv_seq;
		}
		$query = substr_replace($query,'',-1,1);
		$this->db->query($query,$binds);
		
		//question insert
		$questions = $svy[KEY_SURVEY::FORM][KEY_SURVEY::QUESTIONS];
		if ( count($questions) > 0 ) {
			$query = "insert into js_survey_question (survey_seq, question_title, is_multiple, question_idx) 
					values (?, ?, ?, ?)";
			$questionIdx = 0;
			foreach ( $questions as $q ) {
				$binds = array(
							$svy_seq,
							$q[KEY_SURVEY::QUESTION_TITLE],
							$q[KEY_SURVEY::IS_MULTIPLE],
							$questionIdx
						);
				
				$this->db->query($query,$binds);
				$question_seq = $this->db->insert_id();
				
				$options = $q[KEY_SURVEY::OPTIONS];
				
				//option insert
				if ( count($options) > 0 ) {
					$sql = "insert into js_survey_question_option (question_seq, option_content, option_idx)
							values ";
					$binds = array();
					$optionIdx = 0;
					foreach ( $options as $opt ) {
						$sql .= "(?, ?, ?),";
						$binds[] = $question_seq;
						$binds[] = $opt;
						$binds[] = $optionIdx;
						$optionIdx++;
					}
					$sql = substr_replace($sql,'',-1,1);
					$this->db->query($sql,$binds);
				}
				
				$questionIdx++;
			}
		}
		return $svy_hash;
	}
	
	public function getSurveyResult() {
		$survey_hash = Handler::$requestPayload['data'][0][KEY_MESSAGE::IDX];
		$user_hash = Handler::$requestPayload['data'][0][KEY_USER::IDX];
		$survey_seq = $this->db->query('select seq from js_survey where survey_hash = ?',$survey_hash)->row('seq');
		$user_seq = $this->db->query('select seq from js_user where user_hash = ?',$user_hash)->row('seq');
		
		$data = array( array(
				KEY_SURVEY::NUM_RECEIVERS=>0,
				KEY_SURVEY::NUM_UNCHECKERS=>0,
				KEY_SURVEY::NUM_CHECKERS=>0,
				KEY_SURVEY::NUM_GIVE_UP=>0,
				KEY_SURVEY::NUM_RESPONDERS=>0,
				KEY_SURVEY::RESULT=> array()
				) );
		
		$query = 
			 "SELECT 
			count(seq) n
			FROM daondb.js_survey_receiver
			WHERE survey_seq = ?";
		$data[0][KEY_SURVEY::NUM_RECEIVERS] = $this->db->query($query,$survey_seq)->row('n');
		
		$query = 
			 "SELECT 
			count(seq) n
			FROM daondb.js_survey_receiver
			WHERE survey_seq = ? and is_checked = 0";
		$data[0][KEY_SURVEY::NUM_UNCHECKERS] = $this->db->query($query,$survey_seq)->row('n');
		$data[0][KEY_SURVEY::NUM_CHECKERS] = $data[0][KEY_SURVEY::NUM_RECEIVERS] - $data[0][KEY_SURVEY::NUM_UNCHECKERS];
		
		$query = 
			 "SELECT 
			count(seq) n
			FROM daondb.js_survey_receiver
			WHERE survey_seq = ? and is_answered = 1 and is_checked = 1";
		$data[0][KEY_SURVEY::NUM_RESPONDERS] = $this->db->query($query,$survey_seq)->row('n');
		$data[0][KEY_SURVEY::NUM_GIVE_UP] = $data[0][KEY_SURVEY::NUM_CHECKERS] - $data[0][KEY_SURVEY::NUM_RESPONDERS];

		$query =
			"SELECT 
			seq 
			FROM daondb.js_survey_question
			WHERE survey_seq = ?
			ORDER BY question_idx";
		$question_seqs = $this->db->query($query,$survey_seq)->result_array();
		
		foreach ( $question_seqs as $question_idx=>$q ) {
			$query =
			"SELECT
				poll
			FROM daondb.js_survey_question_option
			WHERE question_seq = ?
			ORDER BY option_idx";
			$polls = $this->db->query($query,$q['seq'])->result_array();
			
			$data[0][KEY_SURVEY::RESULT][$question_idx] = array();
			foreach ( $polls as $option_idx=>$poll ) {
				$data[0][KEY_SURVEY::RESULT][$question_idx][$option_idx] = (int)$poll['poll'];
			}
		}
		
		Handler::$responsePayload['status'] = STATUS::SUCCESS;
		Handler::$responsePayload['data'] = $data;
		return;
	}
	
	public function getSurveyContent() {

		$survey_hash = Handler::$requestPayload['data'][0][KEY_MESSAGE::IDX];

		$data = array();
			
		if (is_array($survey_hash))
		{

			foreach ($survey_hash as $hash)
			{
				$s = $this->getSurveyEntity($hash);
				$data[] = array(KEY::_MESSAGE=>$s);
			}
		}
		else
		{

			$s = $this->getSurveyEntity($hash);
			$data = array(
						array(
								KEY::_MESSAGE=>$s
								)
					);
		}

		Handler::$responsePayload['status'] = STATUS::SUCCESS;
		Handler::$responsePayload['data'] = $data;
		return;
	}
	
	private function getSurveyEntity($survey_hash)
	{
		$survey_seq = $this->db->query('select seq from js_survey where survey_hash = ?',$survey_hash)->row('seq');
		
		$data = array( array(
				KEY::_MESSAGE=>
				array(
						KEY_MESSAGE::TYPE=>MESSAGE_TYPE_SURVEY*MESSAGE_TYPE_DIVIDER,
						KEY_MESSAGE::IDX=>"",
						KEY_MESSAGE::TITLE=>"",
						KEY_MESSAGE::CONTENT=>"",
						KEY_MESSAGE::SENDER_IDX=>"",
						KEY_MESSAGE::CREATED_TS=>0,
						KEY_SURVEY::NUM_UNCHECKERS=>0,
						KEY_SURVEY::FORM=>
						array(
								KEY_SURVEY::IS_RESULT_PUBLIC=>0,
								KEY_SURVEY::OPEN_TS=>0,
								KEY_SURVEY::CLOSE_TS=>0,
								KEY_SURVEY::QUESTIONS=> array()
						),
				)
		) );
		
		$query =
		"SELECT
			survey_hash, survey_title, survey_content, is_result_public ,
				unix_timestamp(a.created_ts) created_ts,
				b.user_hash,
				unix_timestamp(open_ts) open_ts,
				unix_timestamp(close_ts) close_ts
		
			 FROM daondb.js_survey a
			LEFT JOIN js_user b
				ON a.creator_seq = b.seq
			WHERE a.seq = ?";
		$baseInfo = $this->db->query($query,$survey_seq)->row_array();
		$data[0][KEY::_MESSAGE][KEY_MESSAGE::IDX]=$baseInfo['survey_hash'];
		$data[0][KEY::_MESSAGE][KEY_MESSAGE::TITLE]=$baseInfo['survey_title'];
		$data[0][KEY::_MESSAGE][KEY_MESSAGE::CONTENT]=$baseInfo['survey_content'];
		$data[0][KEY::_MESSAGE][KEY_MESSAGE::SENDER_IDX]=$baseInfo['user_hash'];
		$data[0][KEY::_MESSAGE][KEY_MESSAGE::CREATED_TS]=$baseInfo['created_ts'];
		$data[0][KEY::_MESSAGE][KEY_SURVEY::FORM][KEY_SURVEY::OPEN_TS]=$baseInfo['open_ts'];
		$data[0][KEY::_MESSAGE][KEY_SURVEY::FORM][KEY_SURVEY::IS_RESULT_PUBLIC]=intval($baseInfo['is_result_public']);
		$data[0][KEY::_MESSAGE][KEY_SURVEY::FORM][KEY_SURVEY::CLOSE_TS]=$baseInfo['close_ts'];
		
		$query =
		"SELECT
		`js_survey_question`.`seq`,
		`js_survey_question`.`question_title`,
		`js_survey_question`.`question_content`,
		`js_survey_question`.`is_multiple`
		FROM `daondb`.`js_survey_question`
		WHERE survey_seq = ?";
		$questions = $this->db->query($query,$survey_seq)->result_array();
		foreach ( $questions as $q ) {
				
			$query =
			"SELECT
				`js_survey_question_option`.`option_content`
				FROM `daondb`.`js_survey_question_option`
				WHERE question_seq = ?";
			$res = $this->db->query($query,$q['seq'])->result_array();
				
			$options = array();
			foreach ( $res as $row ) {
				$options[] = $row['option_content'];
			}
				
			$question =
			array(
					KEY_SURVEY::IS_MULTIPLE=>$q['is_multiple'],
					KEY_SURVEY::QUESTION_TITLE=>$q['question_title'],
					KEY_SURVEY::QUESTION_CONTENT=>$q['question_content'],
					KEY_SURVEY::OPTIONS=>$options
			);
				
			$data[0][KEY::_MESSAGE][KEY_SURVEY::FORM][KEY_SURVEY::QUESTIONS][] = $question;
		}
		
		$query = "select
							count(a.seq) n
						from js_survey_receiver a
						left join js_user b
							on a.receiver_seq = b.seq
						where a.survey_seq = ? and a.is_checked = 0";
		$uncheckers = $this->db->query($query,$survey_seq)->row('n');
		$data[0][KEY::_MESSAGE][KEY_SURVEY::NUM_UNCHECKERS] = $uncheckers;
		return $data[0][KEY::_MESSAGE];
	}
	
	public function answerSurvey() {
		$answer_sheet = Handler::$requestPayload['data'][0][KEY_SURVEY::ANSWER_SHEET];
		$survey_hash = Handler::$requestPayload['data'][0][KEY_MESSAGE::IDX];
		$survey_seq = $this->db->query('select seq from js_survey where survey_hash = ?',$survey_hash)->row('seq');
		$user_hash = Handler::$requestPayload['data'][0][KEY_USER::IDX];
		$user_seq = $this->db->query('select seq from js_user where user_hash = ?',$user_hash)->row('seq');
		
		if ( !$answer_sheet ) {
			die();
		}
		
		foreach ( $answer_sheet as $questionIdx=>$question ) {
			$query = 
			"update 
				js_survey_question_option a
			left join js_survey_question b
				on a.question_seq = b.seq
			
			set a.poll = a.poll+1
			
			where b.survey_seq = ? and b.question_idx = ? and a.option_idx = ?";
			
			foreach ( $question as $optionIdx ) {
				$this->db->query($query,array($survey_seq,$questionIdx,$optionIdx));
				$query = 'insert into js_survey_answer (survey_seq, question_idx, option_idx, user_seq) values (?, ?, ?, ?)';
				$this->db->query($query,array($survey_seq,$questionIdx,$optionIdx,$user_seq));
			}
		}
		
		$query = "update js_survey_receiver set is_answered = 1 , answered_ts = now() where survey_seq = ? and receiver_seq = ?";
		$this->db->query($query,array($survey_seq,$user_seq));

		Handler::$responsePayload['status'] = STATUS::SUCCESS;
		Handler::$responsePayload['data'][] = array(KEY::RESPONSE_TEXT=>"successful");
		return;
	}
}
/* End of message.php */