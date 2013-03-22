<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


		// Common Constants
	define('NOT_SPECIFIED', -777);
	define('MESSAGE_TYPE_DIVIDER', 100); 
	
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
	
	define('DATE_SEPERATOR', '-');
	define('TIME_SEPERATOR', ':');
	define('DATETIME_SEPERATOR', ' ');
	define('DATETIME_FORMAT', 'Y-m-d H:i:s');
	
class Message extends CI_Controller {
/*
		// Common Constants
	public static final $NOT_SPECIFIED = -777;
	public static final $MESSAGE_TYPE_DIVIDER = 100; 
	
	// Message Type Constants
	public static final $MESSAGE_TYPE_CHAT = 0;
	public static final $MESSAGE_TYPE_DOCUMENT = 1;
	public static final $MESSAGE_TYPE_SURVEY = 2;

	
	public static final $MESSAGE_KEY_CHAT = "CHAT";
	public static final $MESSAGE_KEY_MEETING = "MEETING";
	public static final $MESSAGE_KEY_COMMAND = "COMMAND";
	public static final $MESSAGE_KEY_DOCUMENT = "DOCUMENT";
	public static final $MESSAGE_KEY_SURVEY = "SURVEY";

	// Message Sub Type Constants
	public static final $TYPE_MEETING = 0;
	public static final $TYPE_COMMAND = 1;

	// Message Sub Type Constants
	public static final $TYPE_RECEIVED = 0;
	public static final $TYPE_DEPARTED = 1;
	public static final $TYPE_FAVORITE = 2;
	
	// Message Sub Type Constants
	public static final $TYPE_RECEIVED = 0;
	public static final $TYPE_DEPARTED = 1;
	
	*/
	public function index()
	{
		echo "Message Controller";
	}
	public function sendMessageWithGCM(){
		//echo json_encode(Array('post'=>$_POST, 'request'=>$_REQUEST)); return;
	 /* ------------------------------------------------------------------------------
		* sendMessage()에 $packet과 regid들을 주어 전송하게 한다.
		* 성공여부에 따라 주어진 함수에서 값을 적절히 return한다.
		* ------------------------------------------------------------------------------
		* regid : GCM 서버에 등록되어있는 단말기의 고유 일련 번호.
		* regidid : 우리 DB에 순차적으로 등록되어있는 regid들에 해당하는 index 번호.
		* send Page에서 이 Table을 뿌려주고, Submit할 때 index값을 넘겨준다.
		--------------------------------------------------------------------------------- */
		
		//------------------------
		// 사전 설정 작업
		//------------------------
		// Load DB Module 
		$this->load->database('juliet');
		
		//date_default_timezone_set("UTC");
		date_default_timezone_set("Asia/Seoul");

		//---------------------
		// 각종 값 추출
		//---------------------
		// POST 요청으로 들어온 Payload 객체.
		// JSON -> 연관배열로 Decode
		$payload = json_decode($this->input->post('payload'), true);


		/* -------------
		 * 		Payload
		 * -------------
		 * event			: String	:		"event0 : subEvent : subSubEvnet.."
		 * sender			:	long		:		발신자의 DB상의 idx값
		 * receivers	: long[]	:		수신자들의 DB상의 idx값의 모임
		 * roomCode		:	String	:		"senderIdx : departedTS"
		 * message		:	Object
		 */
		// Payload로부터 발신자, 수신자, 메시지 정보 추출
		$_sender = $payload['sender'];
		$_message = $payload['message'];
		
		// Message Manipulation
		$_event = $payload['event'];
		$_events = explode(":", $_event);
		$log = "";
		switch(strtoupper($_events[0])) {
			case "MESSAGE" :
					$payload = $this->onMessage($payload); $log = "here";
				break;
			default :
				break;
		}
		
		// 현재 시간
		$departedTS = time();
		
		if(empty($_sender)) { echo json_encode("No sufficient info data"); return; }
		
		//--------------------------------
#		// TODO : 단말기 분실 상태
		//-------------------------------- 
		$deviceStatus = -1;

		
		
		
		
		
		
		//----------------------------
#		//  메시지 전송 내역 기록
		//----------------------------
		$event = $payload['event'];
		$sender = $payload['sender'];
		$receivers = $payload['receivers'];
		$message = $payload['message'];
		
		$mIdx = NOT_SPECIFIED;
		if(!empty($message)) {
		$log .= "not empty  ";
					
			$mType = floor($message['type'] / MESSAGE_TYPE_DIVIDER);
			$mSubType = floor($message['type'] % MESSAGE_TYPE_DIVIDER);
			
			$mTitle = $message['title'];
			$mContent = $message['content'];
			$_mAppendix = $message['appendix'];
			$mAppendix = json_encode($_mAppendix);
			$currentTS = $departedTS; //time();
			$currentDT = date(DATETIME_FORMAT, $currentTS);
			$tableName;
			$log .= "switch  ";
			
			switch($mType) {
				case MESSAGE_TYPE_CHAT :
							$tableName = 'chat';
							$sql = 'INSERT INTO '.	$tableName	.				'(content, appendix)'.' VALUES '.'(						 "'.$mContent.'",\''.$mAppendix.'\')';
							break;
				case MESSAGE_TYPE_DOCUMENT :
							$tableName = 'document';
							$sql = 'INSERT INTO '.	$tableName	.'(title, content, appendix)'.' VALUES '.'("'.$mTitle.'","'.$mContent.'",\''.$mAppendix.'\')';
							break;
				case MESSAGE_TYPE_SURVEY :
							$tableName = 'survey';
							$sql = 'INSERT INTO '.	$tableName	.'(title, content, appendix)'.' VALUES '.'("'.$mTitle.'","'.$mContent.'",\''.$mAppendix.'\')';
							break;
			}
			$log .= "before query    ";
			$this->db->query($sql);
			$mIdx = $this->db->insert_id();
			

			$log .= "after query    ";
			if($mIdx >0) {
				$tableName .= '-history';
				for($i = 0; $i<count($receivers); $i++) {
					$log .= "successfully inserted      ";
					switch($mType) {
						case MESSAGE_TYPE_CHAT :
									$tableName = 'chat-history';
									$sql = 'INSERT INTO `'.	$tableName	.'` (sender, receiver, departingTS, chat, checked, TS)'
												.' VALUES '.'('.$sender.','.$receivers[$i].',"'.date(DATETIME_FORMAT,$currentTS).'",'.$mIdx.','. 0 .',"'.$currentDT.'")';
									break;
						case MESSAGE_TYPE_DOCUMENT :
									$tableName = 'document-history';
									$sql = 'INSERT INTO `'.	$tableName	.'` (sender, receiver, departingTS, document, checked, TS)'
												.' VALUES '.'('.$sender.','.$receivers[$i].',"'.date(DATETIME_FORMAT,$currentTS).'",'.$mIdx.','. 0 .',"'.$currentDT.'")';
									break;
						case MESSAGE_TYPE_SURVEY :
									$tableName = 'survey-history';
									$sql = 'INSERT INTO `'.	$tableName	.'` (sender, receiver, departingTS, survey, checked, answered, TS)'
												.' VALUES '.'('.$sender.','.$receivers[$i].',"'.date(DATETIME_FORMAT,$currentTS).'",'.$mIdx.','. 0 .','. 0 .',"'.$currentDT.'")';
									break;
					} // End of switch-case Block
					$log .= "            ".$sql."                  ";
					$this->db->query($sql);
					$log .= "successfully inserted in history also";

					$payload['message']['idx'] = $mIdx;

					$log .= "mIdx : ".$mIdx.", payload[message] : ".$payload['message']['idx']."             ";
					
				}// end of for Block
				
			} // end of if(mIdx > 0) block
			
			// Messgae IDX 대입.

			
		}



				//------------------------------------------------------
				// 각 regidid에 해당하는 regid들을 찾는 작업.
				//------------------------------------------------------
				// receivers > user:idx > regid 검색
				$_receivers = $payload['receivers'];
				
				if(empty($_receivers)) { echo json_encode("No sufficient info data"); return; }
		
				// 실제 regid들을 담을 array
				$regids = array();
					
				// 넘어온 각 regidid들을 DB에서 뒤져서 일치하는 regid를 담는다.
				for($i=0; $i<count($_receivers) ; $i++) {
					
					$sql = 'SELECT userid FROM device WHERE user='. $_receivers[$i] .' AND ( type = "a"  OR type = "i" ) limit 1';
					$query = $this->db->query($sql);
				
					if($query == false) {
						echo json_encode(array('msg'=> 'Querying Error, Quit Process.', 'status' => '0'));
						return;
					}
				
					$rows = $query->result_array();
					$row = $rows[0];
					array_push($regids, $row['userid']);

					}

		//
		if(!empty($message) && (($message['type'] / MESSAGE_TYPE_DIVIDER) == MESSAGE_TYPE_CHAT) && (($message['type'] % MESSAGE_TYPE_DIVIDER) == TYPE_COMMAND) ) {
				
				
				for($i = 0; $i<count($regids); $i++) {
					
					$pl = $payload;
					$_r = $pl['receivers'];
					$r = $_r[i];
					$pl['receivers'] = array($r);
					
					$packet = array('departingTS'=>$departedTS, 'deviceStatus'=>$deviceStatus, 'payload'=>$pl);
					
					//-----------------------
					//  메시지 전송
					//-----------------------
					//GCM 서비스를 위한 세팅; Google Api Key for Server Apps
					$auth = "AIzaSyD4LzxiJHYlNFBFcNQalmdDW3FDirnr4N4";
					
					// 메시지를 보낸다.
					$sendResult = $this->_gcmMessage($auth, $regids, $packet);
				}
					
				
					
					
					
					
		} else {

				// TODO : 전송을 위한 Packet Class 생성 
				$packet = array('departingTS'=>$departedTS, 'deviceStatus'=>$deviceStatus, 'payload'=>$payload);

					
					//-----------------------
					//  메시지 전송
					//-----------------------
					//GCM 서비스를 위한 세팅; Google Api Key for Server Apps
					$auth = "AIzaSyD4LzxiJHYlNFBFcNQalmdDW3FDirnr4N4";
					
					// 메시지를 보낸다.
					$sendResult = $this->_gcmMessage($auth, $regids, $packet);

		}


		
		
		
		
		// -------------------
		// 결과값 리턴, 종료.
		// -------------------
		echo json_encode(array("msg" => $sendResult, "receiverIds"=>$_receivers, "regids" => $regids, 'status' => '1', "messageIdx" => $mIdx, 'log'=>$log));
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
		/*
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://android.googleapis.com/gcm/send");	//
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);							//
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);						//
		curl_setopt($ch, CURLOPT_POST, true);											//
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));	//
		$result = curl_exec($ch);
		curl_close($ch);
		*/
		$jsonData = json_encode($data);
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
		
		
		
		return $result;
	}
	
	private function onMessage($payload) {
		
		$_sender = $payload['sender'];
		$_message = $payload['message'];
		
		// Message Manipulation
		$_event = $payload['event'];
		$_events = explode(":", $_event);
		$subType = $_events[1];
		
		switch(strtoupper($subType)) {
			case "DEPARTED" :
				$_events[1] = "RECEIVED";
				$_event = implode(":", $_events);
				$payload['event'] = $_event;  
				break;
		}
		
		//$payload['message']['appendix'] = json_decode($payload['message']['appendix'],true);
				
		return $payload;
	}
	
	public function getUncheckers() {
		
		//------------------------
		// 사전 설정 작업
		//------------------------
		// Load DB Module 
		$this->load->database('juliet');
		$type = $this->input->get('type');
		$idx = $this->input->get('idx');
		
		$messageType = Math.floor($type % MESSAGE_TYPE_DIVIDER);
		
		$tableName;
		$sql = "";
		switch($messageType) {
			case MESSAGE_TYPE_CHAT:
				$tableName = 'chat-history';
				$sql = 'SELECT receiver, TS FROM ' . $tableName . ' WHERE chat='.$idx.' AND checked=0;'; 
				break;
				
			case MESSAGE_TYPE_DOCUMENT:
				$tableName = 'document-history';
				$sql = 'SELECT receiver, TS FROM ' . $tableName . ' WHERE document='.$idx.' AND checked=0;';
				break;
			
			case MESSAGE_TYPE_SURVEY:
				$tableName = 'survey-history';
				$sql = 'SELECT receiver, TS FROM ' . $tableName . ' WHERE survey='.$idx.' AND checked=0;';
		}
		
		$query = $this->db->query($sql);
		
		$resultArray = $query->result_array();
		// TODO?? divider , => : ??
		
		$uncheckers = array();
		$TSs = array();
		for($i=0; $i<$query->num_rows(); $i++) { // num_rows = > var lize?? for SPEED, performance??
			$unchecker = $resultArray[i];
		 	array_push($uncheckers, $unchecker['receiver']);
			array_push($TSs, $unchecker['TS']);
		}
		
		return json_encode(Array('uncheckers'=>$uncheckers, 'TSs'=>$TSs));
	}
	
	public function setMessageChecked() {
		//------------------------
		// 사전 설정 작업
		//------------------------
		// Load DB Module 
		$this->load->database('juliet');
		$_payload = $this->input->post('payload');
		$payload = json_decode($_payload, true);
		$type = $payload['type'];
		$idx = $payload['idx'];
		$userIdx = $payload['user'];
		$currentTS = time();
		$currentDT = date(DATETIME_FORMAT, $currentTS);
		$messageType = floor($type / MESSAGE_TYPE_DIVIDER);
		
		$tableName;
		$sql = "";
		switch($messageType) {
			case MESSAGE_TYPE_CHAT:
				$tableName = 'chat-history';
				$sql = 'UPDATE `' . $tableName . '` SET checked='. 1 .', TS="'. $currentDT .'" WHERE chat='.$idx.' AND receiver='. $userIdx .';'; 
				break;
				
			case MESSAGE_TYPE_DOCUMENT:
				$tableName = 'document-history';
				$sql = 'UPDATE `' . $tableName . '` SET checked='. 1 .', TS="'.$currentDT  .'" WHERE document='.$idx.' AND receiver='. $userIdx .';';
				break;
			
			case MESSAGE_TYPE_SURVEY:
				$tableName = 'survey-history';
				$sql = 'UPDATE `' . $tableName . '` SET checked='. 1 .', TS="'. $currentDT .'" WHERE survey='.$idx.' AND receiver='. $userIdx .';';
		}
		
		$query = $this->db->query($sql);
		$result = -1;
		if($query == TRUE) {
			$result = 1;
		} else {
			$result = 0;
		}
		echo json_encode(Array('status'=>$result));
	}
}

?>