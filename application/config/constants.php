<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
define('FILE_READ_MODE', 0644);
define('FILE_WRITE_MODE', 0666);
define('DIR_READ_MODE', 0755);
define('DIR_WRITE_MODE', 0777);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/

define('FOPEN_READ',							'rb');
define('FOPEN_READ_WRITE',						'r+b');
define('FOPEN_WRITE_CREATE_DESTRUCTIVE',		'wb'); // truncates existing file data, use with care
define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE',	'w+b'); // truncates existing file data, use with care
define('FOPEN_WRITE_CREATE',					'ab');
define('FOPEN_READ_WRITE_CREATE',				'a+b');
define('FOPEN_WRITE_CREATE_STRICT',				'xb');
define('FOPEN_READ_WRITE_CREATE_STRICT',		'x+b');


/**
 * 파일 업로드 경로\n
 */
class PATH {
	//! 업로드 폴더 root 
	const UPLOAD = './uploaded/';
	//! 프로필사진 원본 업로드 경로
	const PIC_PROFILE = './uploaded/pic/profile/';
	//! 프로필사진 작은사이즈 저장경로
	const PIC_PROFILE_SMALL = './uploaded/pic/profile/small/';
	//! 프로필사진 중간사이즈 저장경로
	const PIC_PROFILE_MEDIUM = './uploaded/pic/profile/medium/';
	//! 채팅방에서 보낸 사진 경로
	const PIC_CHAT = './uploaded/pic/chat/';
	const PIC_CHAT_SMALL = './uploaded/pic/chat/small/';
	//! 문서 저장 경로
	const DOC = './uploaded/doc/';
}

/**
 * status 코드
 */
class STATUS {
	//! 성공
	const SUCCESS = 1;
	//! 페이로드에 포함된 정보 부족
	const INSUFFICIENT_ARGUMENTS = 2 ;
	//! 찾으려는 정보가 DB에 없음
	const NO_DATA = 3;
	//! 업로드 실패
	const FAILED_TO_UPLOAD =4;
}


/**
 * 서버와의 인터페이스에 쓰이는 KEY 값들 모음
 * payload의 data에 들어가는 객체들의 멤버변수명이거나\n
 * 해쉬맵의 Key 값임.
 */
class KEY {
	
	const RESPONSE_TEXT ="responseText";
	const _MESSAGE ="message";
}
class KEY_MESSAGE {
	const IDX = "Message_idx";
	const TYPE = "Message_type";
	const TITLE = "Message_title";
	const CONTENT = "Message_content";
	const SENDER_IDX = "Message_senderIdx";
	const CREATED_TS = "Message_TS";
	const IS_CHECKED = "Message_checked";
	const CHECK_TS = "Message_checkTS";
	const RECEIVERS_IDX = "Message_receiversIdx";
	const RECEIVERS_REGISTRATION_IDS ="receiversRegistrationIds";
	
}
	
class KEY_GCM {
	const SEND_RESULT ="sendResultFromGCM";
	const MULTICAST_ID ="multicast_id";
	const SUCCESS ="success";
	const FAILURE ="failure";
	const CANONICAL_IDS ="canonical_ids";
	const RESULTS ="results";
	const RESULTS_MESSAGE_ID ="message_id";
	const RESULTS_NEW_REGISTRATION_ID ="registration_id";
	const RESULTS_ERROR ="error";
	
}

class KEY_CHAT {
	const IDX = "Chat_idx";
	const TYPE = "Chat_type";
	const CONTENT = "Chat_content";
	const SENDER_IDX = "Chat_senderIdx";
	const RECEIVERS_IDX = "Chat_receiversIdx";
	const CREATED_TS = "Chat_TS";
	const IS_CHECKED = "Chat_checked";
	const CHECK_TS = "Chat_checkTS";
	const ROOM_CODE = "Chat_roomCode";
	const CONTENT_TYPE = "Chat_contentType";
	const IMAGE_SIZE = "imageSize";

	const ROOM_MEMBER = "roomMember";
	const LAST_READ_TS = "lastReadTS";
}

class KEY_DOCUMENT {
	const IDX = "Document_idx";
	const TYPE = "Document_type";
	const TITLE = "Document_title";
	const CONTENT = "Document_content";
	const SENDER_IDX = "Document_senderIdx";
	const RECEIVERS_IDX = "Document_receiversIdx";
	const CREATED_TS = "Document_TS";
	const IS_CHECKED = "Document_checked";
	const CHECK_TS = "Document_checkTS";
	
	const FORWARDS = "Document_forwards";
	const FORWARDER_IDX ="forwarderIdx";
	const FORWARD_TS ="forwardTS";
	const FORWARD_CONTENT ="forwardContent";
	
	const FILES = "Document_files";
	const FILE_IDX ="fileIdx";
	const FILE_NAME ="fileName";
	const FILE_TYPE ="fileType";
	const FILE_SIZE ="fileSize";
	
	const IS_FAVORITE = "Document_isFavorite";
	
}

class KEY_SURVEY {
	const IDX = "Survey_idx";
	const TYPE = "Survey_type";
	const CONTENT = "Survey_content";
	const TITLE = "Survey_title";
	const SENDER_IDX = "Survey_senderIdx";
	const RECEIVERS_IDX = "Survey_receiversIdx";
	const CREATED_TS = "Survey_TS";
	const IS_CHECKED = "Survey_checked";
	const CHECK_TS = "Survey_checkTS";
	
	const OPEN_TS = "Survey_openTS";
	const CLOSE_TS = "Survey_closeTS";
	
	const FORM = "Survey_form";
	const QUESTIONS ="questions";
	const IS_MULTIPLE ="Question_isMultiple";
	const QUESTION_TITLE = "Question_title";
	const QUESTION_CONTENT = "Question_content";
	const OPTIONS = "Question_options";
	
	const ANSWER_SHEET ="answerSheet";
	const NUM_RECEIVERS = "numReceivers";
	const NUM_UNCHECKERS = "numUnCheckers";
	const NUM_CHECKERS = "numCheckers";
	const NUM_RESPONDERS = "numResponders";
	const NUM_GIVE_UP = "numGiveUp";
	const RESULT = "result";
}

class KEY_USER {
	const IDX = "User_idx";
	const NAME = "User_name";
	const ROLE = "User_role";
	const RANK = "User_rank";
	
	const IS_ENABLED ="isUserEnabled";
	const PROFILE_IMAGE_SIZE = "profileImgSize";
}

class KEY_DEPT {
	const IDX = "Department_idx";
	const SEQUENCE = "Department_deptCode";
	const NAME = "Department_name";
	const FULL_NAME = "Department_nameFull";
	const PARENT_IDX = "Department_parentIdx";
	const FETCH_RECURSIVE ="fetchRecursive";
}

class KEY_DEVICE {
	const IDX ="deviceIdx";
	const UUID ="uuid";
	const GCM_REGISTRATION_ID ="GCMRegistrationId";
	const TYPE ="deviceType";
	const IS_REGISTERED ="isDeviceRegistered";
	const IS_ENABLED ="isDeviceEnabled";
	
}

class KEY_SEARCH {
	const QUERY = "query";
	
}

class KEY_UPLOAD {
	const FILE_IDX = "fileHash";
	const FILE_TYPE = "fileType";
	
}
/* End of file constants.php */
/* Location: ./application/config/constants.php */