<?php
class Upload extends CI_Model {
	private $eventAr;
	private $requestPayload;
	private $responsePayload; //controller에 있는 responsePayload를 참조하는 변수
	
	function __construct() {
		parent::__construct();
		$this->load->database();
	}
	
	public function act($handler,$eventAr,$requestPayload) {
		$this->eventAr = $eventAr;
		//sub event별 분류
		switch( $eventAr[1] ) {
			case "IMAGE":
				$this->image();
				break;
			default:
				break;
		}
	}

	/**
	 * 업로드 파일 타입
	 */
	const IMAGE_PROFILE = 3;
	const IMAGE_CHAT = 5;

	public function image(){
		$file_info = Handler::$requestPayload['data'][0];
		if ( !isset($file_info[KEY_UPLOAD::FILE_TYPE]) || !isset($file_info[KEY_UPLOAD::FILE_IDX])
				|| !$file_info[KEY_UPLOAD::FILE_IDX] ) {
			Handler::$responsePayload['status'] = STATUS::INSUFFICIENT_ARGUMENTS;
			Handler::$responsePayload['data'][] = array(KEY::RESPONSE_TEXT=> 'Insufficient Informations');
			return;
		}
		$file_hash = $file_info[KEY_UPLOAD::FILE_IDX];
		$file_type = $file_info[KEY_UPLOAD::FILE_TYPE];
		switch($file_type) {
			case self::IMAGE_PROFILE:
				$this->profile($file_hash);
				break;
			case self::IMAGE_CHAT:
				$this->chat($file_hash);
				break;
			default:
				Handler::$responsePayload['status'] = STATUS::FAILED_TO_UPLOAD;
				Handler::$responsePayload['data'][] = array(KEY::RESPONSE_TEXT=> 'INVALID FILE TYPE');
				return;
		}
	}
	
	private function profile($user_hash) {
	
		$userSeq = $this->db->query('select seq from js_user where user_hash = ?',$user_hash)->row('seq');
	
		if ( !$userSeq  ) {
			Handler::$responsePayload['status'] = STATUS::NO_DATA;
			Handler::$responsePayload['data'][] = array(KEY::RESPONSE_TEXT => 'unregistered user');
		}
	
		//TODO: 업로드 파일 사진 크기 제한
		$config['upload_path'] = PATH::PIC_PROFILE;
		$config['allowed_types'] = '*';
		$config['file_name'] = $user_hash;
		$config['overwrite'] = TRUE;
		//$config['max_size']	= '100';
		//$config['max_width']  = '1024';
		//$config['max_height']  = '768';
	
		$this->load->library('upload', $config);
	
		if ( ! $this->upload->do_upload('file_0')) {
			Handler::$responsePayload['status'] = STATUS::FAILED_TO_UPLOAD;
			Handler::$responsePayload['data'][] = array( KEY::RESPONSE_TEXT => $this->upload->display_errors() );
			return;
	
		} else {
			$uploadResult = $this->upload->data();
			chmod($uploadResult['full_path'],0755);
			$fileName = $uploadResult['file_name'];
	
			$this->load->helper('image');
	
			/**
			 * png파일이면 jpg로 변환하고 png파일은 삭제
			*/
			$quality = 100;
			if ( $uploadResult['file_ext'] == ".png" ) {
				png2jpg($uploadResult['full_path'], $uploadResult['file_path'].$uploadResult['raw_name'].'.jpg',
				$quality);
			}
	
			/**
			 * 두 종류의 썸네일 생성
			 */
			$smallThumbnailPath = PATH::PIC_PROFILE_SMALL.$uploadResult['file_name'];
			$mediumThumbnailPath = PATH::PIC_PROFILE_MEDIUM.$uploadResult['file_name'];
			$smallWidth = 50;
			$smallHeight = 50;
			$mediumWidth = 100;
			$mediumHeight = 100;
	
			makeThumbnails($uploadResult['full_path'],$smallThumbnailPath,$smallWidth,$smallHeight);
			makeThumbnails($uploadResult['full_path'],$mediumThumbnailPath,$mediumWidth,$mediumHeight);
	
			Handler::$responsePayload['status'] = STATUS::SUCCESS;
			Handler::$responsePayload['data'][] = array(KEY::RESPONSE_TEXT=>"successful");
		}
	}
	
	private function chat($file_hash) {
		
		//TODO: 업로드 파일 사진 크기 제한
		$config['upload_path'] = PATH::PIC_CHAT;
		$config['allowed_types'] = 'jpg|png|jpeg';
		$config['file_name'] = $file_hash;
		$config['overwrite'] = TRUE;
		//$config['max_size']	= '100';
		//$config['max_width']  = '1024';
		//$config['max_height']  = '768';
		$this->load->library('upload', $config);
		
		if ( ! $this->upload->do_upload('file_0')) {
			Handler::$responsePayload['status'] = STATUS::FAILED_TO_UPLOAD;
			Handler::$responsePayload['data'][] = array( KEY::RESPONSE_TEXT => $this->upload->display_errors() );

			return;
		
		} else {
			$uploadResult = $this->upload->data();
			chmod($uploadResult['full_path'],0777);

			$this->load->helper('image');
		
			/**
			 * png파일이면 jpg로 변환하고 png파일은 삭제
			*/
			$quality = 100;
			if ( $uploadResult['file_ext'] == ".png" ) {
				png2jpg($uploadResult['full_path'], $uploadResult['file_path'].$uploadResult['raw_name'].'.jpg',
				$quality);

				$uploadResult['file_name'] = $uploadResult['raw_name'].'.jpg';
				
				$uploadResult['full_path'] = $uploadResult['file_path'].$uploadResult['file_name'];
			}
		
			/**
			 * 두 종류의 썸네일 생성
			 */
			$smallThumbnailPath = PATH::PIC_CHAT_SMALL.$uploadResult['file_name'];
			$smallWidth = 80;
			$smallHeight = 80;
			
			makeThumbnails($uploadResult['full_path'],$smallThumbnailPath,$smallWidth,$smallHeight);
		
			Handler::$responsePayload['status'] = STATUS::SUCCESS;
			Handler::$responsePayload['data'][] = array(KEY::RESPONSE_TEXT=>"successful");
		}
	}
}