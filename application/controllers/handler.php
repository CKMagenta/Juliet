<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Payload Handler
 * 클라이언트에서 http get 또는 post 요청으로 payload object를 json의 형태로 전송하면\n 
 * 이 컨트롤러의 call 모듈로 받아서 requestPayload['event'] 에 설정된 event type을 가지고\n
 * 적절한 모델을 호출하여 요청을 처리한다. 그리고 각각의 모델에서는 이 객체의 static 변수인 responsePayload에\n
 * 결과를 저장하고 마지막에 handler는 responsePayload를 json으로 변환하여 응답한다.
 *  
 * @author 최영우
 * @since 2013.3.29
 */
class Handler extends CI_Controller {

	/**
	 * 응답용 payload 객체.\n
	 * @b Event / event / requestPayload에서 설정된 것과 똑같은 event 객체\n
	 * @b Int / status / 해당 요청에 의한 실행 결과 코드\n
	 * @b ArrayList<HashMap<String,Object>> / data / 결과를 담은 객체. 이벤트별로 구조가 다름 \n
	 */
	public static $responsePayload =
		array(
				"event"=>NULL,	//! Event
				"status"=>NULL, // Int
				"data"=>array() // ArrayList< HashMap<String,Object> >
				);
	public static $requestPayload = array();
	
	function __construct() {
		parent::__construct();
	}
	
	public function index() {
		
	}
	
	/**
	 * 진입점 역할을 하는 함수. requestPayload를 json의 형태로 get이나 post로 받는다.
	 */
	public function call() {
		
		//페이로드 객체 as json
		$requestPayloadJson = $this->input->get_post('payload',TRUE);
		
		$requestPayload = json_decode($requestPayloadJson,true);

		if ( !$requestPayload ) {
			
			self::$responsePayload['event'] = NULL;
			self::$responsePayload['status'] = FALSE;
			self::$responsePayload['data'] = "NULL request payload";
			die( json_encode(self::$responsePayload) );
		}
		//TODO:VALIDATION, LOGGING

		self::$requestPayload = $requestPayload;
		$this->response();
	}

	/**
	 * requestPayload의 event에 따라 해당되는 모델을 호출하고 모델 내에서 작업을 처리한 후\n
	 * 설정된 responsePayload를 json으로 변환하여 클라이언트로 응답한다
	 * @param Payload $requestPayload 
	 */
	public function response() {

		//event 별로 분류하고 requestPayload에는 requestPayload에 있는 것과 똑같은 event를 담아 응답함
		$event = strtoupper(self::$requestPayload['event']);
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
			case "SEARCH":
				$model = 'search';
				break;
			case "UPLOAD":
				$model = 'upload';
				break;
			default:
				die('invalid first event category');
		}
		
		$this->load->model( $model );

		//각각의 모델은 act 메소드에서 해당 event에 대한 작업을 처리하고 그 결과를 Handler의 static member인 responsePayload에
		//저장하고, handler에서 그 배열을 json으로 바꿔서 echo
		$this->$model->act( $this, $eventAr, self::$requestPayload );
		
		echo json_encode( self::$responsePayload );
	}	
}