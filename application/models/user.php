<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class User extends CI_Model {
	
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
			case "GET_USER_INFO":
				$this->getUserInfo();
				break;
			case "GET_DEPT_INFO":
				$this->getDeptInfo();
				break;
			case "REGISTER":
				$this->register();
				break;
			case "GET_CHILD_DEPTS":
				$this->getChildDepts();
				break;
			case "GET_MEMBERS":
				$this->getMembers();
				break;
			default:
				die();
		}
	}

	private function getUserInfo(){
		/**
		 ○ request
		 -event : USER:GET_INFO
		 -data :
		 	-user_hash :		유저코드(기존의 idx)
		 */
		
		$data = $this->requestPayload['data'];
		
		if( !isset($data['user_hash']) || !$data['user_hash']  ) {
		
			$this->responsePayload['status'] = INSUFFICIENT_ARGUMENTS;
			$this->responsePayload['data'] = 'Insufficient Informations';
			return;
		}

		if ( is_array($data['user_hash']) ) {
			$res = array();
			
			foreach ( $data['user_hash'] as $hash ) {
				$query = "select
				user_hash,
				user_name,
				d.dept_hash,
				d.dept_full_name,
				d.dept_name,
				user_rank,
				user_duty,
				pic_filename,
				u.is_enabled
		
				from js_user u
				left join js_department d
					on u.dept_seq = d.seq
				where u.user_hash = ? and u.is_enabled = 1 ";
				$res[] = $this->db->query($query,array($hash))->row_array();
			} 
			
		} else {
			$query = "select
				user_hash,
				user_name,
				d.dept_hash,
				d.dept_full_name,
				d.dept_name,
				user_rank,
				user_duty,
				pic_filename,
				u.is_enabled
			
				from js_user u
				left join js_department d
					on u.dept_seq = d.seq
				where u.user_hash = ? and u.is_enabled = 1 ";
			$res = $this->db->query($query,array($data['user_hash']))->row_array();
		}
		

		
		if ( !$res ) {
			$this->responsePayload['status'] = NO_DATA;
			$this->responsePayload['data'] = 'unregistered user';
		} else {
			$this->responsePayload['status'] = SUCCESS;
			$this->responsePayload['data'] = $res;			
		}
	}
	
	private function getDeptInfo() {
		
		$data = $this->requestPayload['data'];
		
		if( !isset($data['dept_hash']) || !$data['dept_hash']  ) {
		
			$this->responsePayload['status'] = INSUFFICIENT_ARGUMENTS;
			$this->responsePayload['data'] = 'Insufficient Informations';
			return;
		}
		
		if ( is_array($data['dept_hash']) ) {
			$res = array();
			foreach ( $data['dept_hash'] as $hash ) {
				$query = "select
					c.dept_hash,
					c.dept_name,
					c.dept_full_name,
					c.dept_code,
					c.is_enabled,
					p.dept_hash as parent_hash
					from js_department c
					left join js_department p
						on c.parent_seq = p.seq
					where c.dept_hash = ? and c.is_enabled = 1";
				$res[] = $this->db->query($query,$hash)->row_array();	
			}
				
		} else {
			$query = "select
				c.dept_hash,
				c.dept_name,
				c.dept_full_name,
				c.dept_code,
				c.is_enabled,
				p.dept_hash as parent_hash
				from js_department c
				left join js_department p
					on c.parent_seq = p.seq
				where c.dept_hash = ? and c.is_enabled = 1";
			$res = $this->db->query($query,$data['dept_hash'])->row_array();
		}
		
		
		
		if ( !$res ) {
			$this->responsePayload['status'] = NO_DATA;
			$this->responsePayload['data'] = 'unregistered department';
		} else {
			$this->responsePayload['status'] = SUCCESS;
			$this->responsePayload['data'] = $res;
		}
		
	}
	
	private function register() {
		/**
			○ request
			-event : USER:REGISTER
			-data :
				-user_name	유저이름
				-dept_code	dept_code
				-rank		계급
				-role		직위
		 */
		
		$data = $this->requestPayload['data'];
		
		if ( !isset($data['user_name']) || !isset($data['dept_hash']) || !isset($data['role']) || !isset($data['rank']) || 
				!$data['user_name'] || !$data['dept_hash'] || !$data['role'] || !$data['rank'] ) {
			$this->responsePayload['status'] = INSUFFICIENT_ARGUMENTS;
			$this->responsePayload['data'] = 'Insufficient Informations';
			return;
		}
		
		$deptSeq = $this->db->query('select seq from js_department where dept_hash = ?',$data['dept_hash'])->row('seq');
		
		if ( !$deptSeq ) {
			$this->responsePayload['status'] = NO_DATA;
			$this->responsePayload['data'] = 'unregistered department';
			return;
		}
		
		$query = "INSERT INTO `daondb`.`js_user`
			(`user_name`,
			`dept_seq`,
			`user_rank`,
			`user_duty`,
			`is_enabled`,
			`created_ts`) values( ?, ?, ?, ?, 0, now())";
		$this->db->query($query, array($data['user_name'],$deptSeq,$data['rank'],$data['role']));
		
		$this->responsePayload['status'] = SUCCESS;
		$this->responsePayload['data'] = array('user_seq'=>$this->db->query('select last_insert_id() a')->row('a'), 'dept_hash'=>$data['dept_hash']);
	}
	
	private function getChildDepts() {
		
		$data = $this->requestPayload['data'];
		
		if ( !isset($data['dept_hash']) ) {
			$this->responsePayload['status'] = INSUFFICIENT_ARGUMENTS;
			$this->responsePayload['data'] = 'Insufficient Informations';
			return;
		}
		
		$deptSeq = $this->db->query('select seq from js_department where dept_hash = ?',$data['dept_hash'])->row('seq');
		
		if ( $data['dept_hash'] ) {
			$query = 'select dept_hash, dept_code, dept_name, dept_full_name from js_department
				where parent_seq = ? and is_enabled = 1
				order by dept_code';
			$res = $this->db->query($query,$deptSeq)->result_array();	
		} else {
			$query = 'select dept_hash, dept_code, dept_name, dept_full_name from js_department
				where parent_seq is NULL and is_enabled = 1
				order by dept_code';
			$res = $this->db->query($query)->result_array();
		}
		
		$this->responsePayload['status'] = SUCCESS;
		$this->responsePayload['data'] = $res;
	}
	
	private function getMembers() {
		$data = $this->requestPayload['data'];
		
		if ( !isset($data['dept_hash']) || !isset($data['fetch_all']) || !$data['dept_hash'] ) {
			$this->responsePayload['status'] = INSUFFICIENT_ARGUMENTS;
			$this->responsePayload['data'] = 'Insufficient Informations';
			return;
		}
		
		$deptSeq = $this->db->query('select seq from js_department where dept_hash = ?',$data['dept_hash'])->row('seq');
		
		if ( $data['fetch_all'] ) {
			
			$query = "select user_hash, user_name, user_rank, user_duty, pic_filename
					from js_user
					where dept_full_path like '%:{$deptSeq}:%' and is_enabled = 1
					order by user_rank";
			$res = $this->db->query($query)->result_array();
			
		} else {
			
			$query = "select user_hash, user_name, user_rank, user_duty, pic_filename
					from js_user
					where dept_seq = ? and is_enabled = 1
					order by user_rank";
			$res = $this->db->query($query,$deptSeq)->result_array();
			
		}
		
		$this->responsePayload['status'] = SUCCESS;
		$this->responsePayload['data'] = $res;
	}
}


/* End of file user.php */