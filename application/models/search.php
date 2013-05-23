<?php
class Search extends CI_Model {
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
			case "USER":
				$this->user();
				break;
			default:
				break;
		}
	}
	
	public function user(){
		$input = $this->requestPayload['data'][0];
		$q = $input[KEY_SEARCH::QUERY];
		
		$query = 
			'select 
				user_hash '.KEY_USER::IDX.' , 
				user_name '.KEY_USER::NAME.' , 
				dept_name '.KEY_DEPT::NAME.' , 
				trim(replace(dept_full_name,":"," ")) '.KEY_DEPT::FULL_NAME.' , 
				user_role  '.KEY_USER::ROLE.' ,
				user_rank '.KEY_USER::RANK.' 
				from js_user u
				left join js_department d
					on u.dept_seq = d.seq
				where user_name like ? and user_status = 1';
		$result = $this->db->query($query,'%'.$q.'%')->result_array();
		
		foreach ( $result as $key=>$row ) {
			$result[$key][KEY_USER::RANK] = (int) $row[KEY_USER::RANK];
		}
		
		$this->responsePayload['status'] = STATUS::SUCCESS;
		$this->responsePayload['data'] = $result;
	}
}