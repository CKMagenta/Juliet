<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

//! Payload data 핸들링
/*!
 * 모든 데이터를 이 곳으로 받아 기본적인 검증을 수행한 후 해당 모델을 호출하여 작업을 처리한다. 
 */
class Handler extends CI_Controller {

	//응답용 payload 구조. 각각의 모델에서 적절히 event, status, data의 값을 할당
	public static $responsePayload =
		array(
				"event"=>NULL,
				"status"=>NULL,
				"data"=>NULL
				);
	
	function __construct() {
		parent::__construct();		
	}
	
	public function index() {
		
		echo substr_replace('1234567890','',-2,2);
		
	}
	
	public function call() {

		//페이로드 객체 as json
		$requestPayload = $this->input->get_post('payload',TRUE);
		
		if ( $requestPayload == NULL ) {
			die();
		}

		//TODO:VALIDATION, LOGGING

		$this->response($requestPayload);
	}

	private function response($requestPayload) {

		//event 별로 분류하고 requestPayload에는 requestPayload에 있는 것과 똑같은 event를 담아 응답함
		$event = strtoupper($requestPayload['event']);
		self::$responsePayload['event'] = $event;
		$eventAr = explode(':',$event);
		
		switch( $eventAr[0] ) {
			case "DEVICE":
				$model = 'device';
				break;
			case "USER":
				$model = 'user';
				break;
			case "MESSAGE":
				$model = 'message';
				break;
			case "SURVEY":
				$model = 'survey';
				break;
			default:
				die();
		}
		
		$this->load->model( $model );
		//각각의 모델은 act 메소드에서 해당 event에 대한 작업을 처리하고 그 결과를 Handler의 static member인 responsePayload에
		//저장하고, handler에서 그 배열을 json으로 바꿔서 echo
		$this->$model->act( $this, $eventAr, $requestPayload );

		echo json_encode( self::$responsePayload );
	}	
}

?>