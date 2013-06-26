<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

define('THUMBNAIL_IMAGE_MAX_WIDTH', 50);
define('THUMBNAIL_IMAGE_MAX_HEIGHT', 50);

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
		$this->responsePayload = &Handler::$responsePayload;
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
			case "UPLOAD_PROFILE_IMG":
				$this->uploadProfileImg();
				break;
			case "GET_PROFILE_IMG":
				$this->getProfileImg();
				break;
			case "BUG_REPORT":
				$this->bugReport();
				break;
			default:
				echo "invalid event";
				return;
		}
	}

	private function getUserInfo(){

		$data = $this->requestPayload['data'];

		foreach ( $data as $hashMap ) {
				
			$query = "select
			user_hash ".KEY_USER::IDX.",
			user_name ".KEY_USER::NAME.",
			d.dept_hash ".KEY_DEPT::IDX.",
			trim(replace(d.dept_full_name,':',' '))  ".KEY_DEPT::FULL_NAME.",
			d.dept_name ".KEY_DEPT::NAME.",
			user_rank ".KEY_USER::RANK.",
			user_role ".KEY_USER::ROLE.",
			u.is_enabled ".KEY_USER::IS_ENABLED."

			from js_user u
			left join js_department d
				on u.dept_seq = d.seq
			where u.user_hash = ?";
			$row = $this->db->query($query,$hashMap[KEY_USER::IDX])->row_array();
			if ( $row ) {
				$row[KEY_USER::IS_ENABLED] = $row[KEY_USER::IS_ENABLED] == 1 ? true:false;
				$this->responsePayload['data'][] = $row;
			}
			else
			{
				$this->responsePayload['data'][] = array(
						KEY_USER::IDX=>"nulluseridx",
						KEY_USER::NAME=>"(탈퇴한 회원)",
						KEY_DEPT::IDX=>"",
						KEY_DEPT::FULL_NAME=>"",
						KEY_DEPT::NAME=>"",
						KEY_USER::RANK=>"11",
						KEY_USER::ROLE=>"",
						KEY_USER::IS_ENABLED=>1
						);
			}
			
		}

		$this->responsePayload['status'] = STATUS::SUCCESS;
	}

	private function getDeptInfo() {

		$data = $this->requestPayload['data'];

		foreach ( $data as $hashMap ) {
				
			$query = "select
				c.dept_hash ".KEY_DEPT::IDX.",
				c.dept_name ".KEY_DEPT::NAME.",
				trim(replace(c.dept_full_name,':',' '))  ".KEY_DEPT::FULL_NAME.",
				c.dept_code ".KEY_DEPT::SEQUENCE.",
				p.dept_hash ".KEY_DEPT::PARENT_IDX."
				from js_department c
				left join js_department p
					on c.parent_seq = p.seq
				where c.dept_hash = ? and c.is_enabled = 1";
			$row = $this->db->query($query,$hashMap[KEY_DEPT::IDX])->row_array();
				
			$this->responsePayload['data'][] = $row;
		}

		$this->responsePayload['status'] = STATUS::SUCCESS;
	}

	private function register() {
		$data = $this->requestPayload['data'];

		$deptSeq = $this->db->query('select seq from js_department where dept_hash = ?',$data[0][KEY_DEPT::IDX])->row('seq');

		if ( !$deptSeq ) {
			$this->responsePayload['status'] = STATUS::NO_DATA;
			$this->responsePayload['data'][] = array( KEY::RESPONSE_TEXT=>'unregistered department' );
			return;
		}

		$query = 'select user_hash from js_user where user_name = ? and dept_seq = ? and user_rank = ? and user_role = ?';
		$user_hash = $this->db->query($query,array($data[0][KEY_USER::NAME],
				$deptSeq,
				$data[0][KEY_USER::RANK],$data[0][KEY_USER::ROLE]))->row('user_hash');
		
		if ( !$user_hash ) {
		
			$query = "INSERT INTO `daondb`.`js_user`
				(`user_name`,
				`dept_seq`,
				`user_rank`,
				`user_role`,
				`is_enabled`,
				`created_ts`) values( ?, ?, ?, ?, 0, now())";
			$this->db->query($query, array($data[0][KEY_USER::NAME],
					$deptSeq,
					$data[0][KEY_USER::RANK],$data[0][KEY_USER::ROLE]));
			$user_seq = $this->db->query('select last_insert_id() a')->row('a');
	
			$user_hash = md5('js_user'.$user_seq);
	
			$query = "update js_user set user_hash = ? where seq = ?";
			$this->db->query($query,array($user_hash,$user_seq));
			
		}
		
		$this->responsePayload['status'] = STATUS::SUCCESS;
		$this->responsePayload['data'][]
				=
				array(
						KEY_USER::IDX=> $user_hash
				);
	}

	private function getChildDepts() {
		$data = $this->requestPayload['data'];

		if ( !isset($data[0][KEY_DEPT::IDX]) ) {
			$this->responsePayload['status'] = STATUS::INSUFFICIENT_ARGUMENTS;
			$this->responsePayload['data'][] = array(KEY::RESPONSE_TEXT=> 'Insufficient Informations');
			return;
		}

		$dept_seq = $this->db->query('select seq from js_department where dept_hash = ?',$data[0][KEY_DEPT::IDX])->row('seq');

		if ( $data[0][KEY_DEPT::IDX] ) {
			$query ="select
					dept_hash ".KEY_DEPT::IDX.",
					dept_code ".KEY_DEPT::SEQUENCE.",
					dept_name ".KEY_DEPT::NAME.",
					trim(replace(dept_full_name,':',' '))  ".KEY_DEPT::FULL_NAME."
					 from js_department
				where parent_seq = ? and is_enabled = 1
				order by dept_code";
			$res = $this->db->query($query,$dept_seq)->result_array();
		} else {
			$query = "select
						dept_hash ".KEY_DEPT::IDX.",
						dept_code ".KEY_DEPT::SEQUENCE.",
						dept_name ".KEY_DEPT::NAME.",
					trim(replace(dept_full_name,':',' '))  ".KEY_DEPT::FULL_NAME."
							 from js_department
				where parent_seq is NULL and is_enabled = 1
				order by dept_code";
			$res = $this->db->query($query)->result_array();
		}


		$this->responsePayload['status'] = STATUS::SUCCESS;
		$this->responsePayload['data'] = $res;
	}

	private function getMembers() {
		$data = $this->requestPayload['data'];

		if ( !isset($data[0][KEY_DEPT::IDX]) || !isset($data[0][KEY_DEPT::FETCH_RECURSIVE]) ) {
			$this->responsePayload['status'] = STATUS::INSUFFICIENT_ARGUMENTS;
			$this->responsePayload['data'][] = array( KEY::RESPONSE_TEXT => 'Insufficient Informations' );
			return;
		}

		$deptSeq = $this->db->query('select seq from js_department where dept_hash = ?',$data[0][KEY_DEPT::IDX])->row('seq');

		$user_idx = isset($data[0][KEY_USER::IDX])?$data[0][KEY_USER::IDX] : 'none';
		
		if ( $data[0][KEY_DEPT::FETCH_RECURSIVE] ) {

			if ($data[0][KEY_DEPT::IDX] == null || $data[0][KEY_DEPT::IDX] == "")
			{
				$query = "select
						user_hash ".KEY_USER::IDX.",
						user_name ".KEY_USER::NAME.",
						user_rank ".KEY_USER::RANK.",
						d.dept_hash ".KEY_DEPT::IDX.",		
						user_role ".KEY_USER::ROLE."
						from js_user u
						left join js_department d
						on u.dept_seq = d.seq
								where u.is_enabled = 1 and u.user_hash != ?";
			}
			else
			{
				$query = "select
						user_hash ".KEY_USER::IDX.",
						user_name ".KEY_USER::NAME.",
						user_rank ".KEY_USER::RANK.",
						user_role ".KEY_USER::ROLE."
					from js_user u
					left join js_department d
					on u.dept_seq = d.seq
					where dept_full_path like '%:{$deptSeq}:%' and u.is_enabled = 1 and u.user_hash != ? 
					order by user_rank, user_name";
			}

			$res = $this->db->query($query,$user_idx)->result_array();
				
		} else {
				
			$query = "select
							user_hash ".KEY_USER::IDX.",
							user_name ".KEY_USER::NAME.",
							user_rank ".KEY_USER::RANK.",
							user_role ".KEY_USER::ROLE."
						from js_user
						where dept_seq = ? and is_enabled = 1 and user_hash != ?
						order by user_rank, user_name";
			$res = $this->db->query($query,array($deptSeq, $user_idx))->result_array();
		}
	
		$this->responsePayload['status'] = STATUS::SUCCESS;
		$this->responsePayload['data'] = $res;
	}
	
	private function bugReport() {
		$data = $this->requestPayload['data'];
		$idx = $data[0][KEY_USER::IDX];
		$content = $data[0][KEY_MESSAGE::CONTENT];
		$ts = $data[0][KEY_MESSAGE::CREATED_TS];
		$query = 'insert into js_bug_report(user_idx, content, created_ts) values(?,?,?)';
		$this->db->query($query,array($idx,$content,$ts));
		$this->responsePayload['status'] = STATUS::SUCCESS;
		$this->responsePayload['data'] = $res;
	}
}


/* End of file user.php */