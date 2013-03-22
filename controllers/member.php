<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Member extends CI_Controller {

	private $memberList;
	private $listFetchTS;

	public function index()
	{
		echo "welcome!";
	}
	public function getUserInfo(){
		$this->load->database('juliet');
		$idx = $this->input->get('idx');
		$fields = $this->input->get('fields');
		print_r($_GET);
		print_r($_POST);
		if(empty($idx) || empty($fields)) {echo json_encode(Array("status"=>"error", "idxs"=>$idx, "fields"=>$fields)); return;}
		$_idx = join(',', $idx);
		$_fields = join(',', $fields);	// 임시용! $fields 변수가 1개짜리 array가 아닐경우 오류를 일으킬 수 있다.
		switch($_fields) {
			case 'TS' :
				$_fields = 'u.TS'; break;
			case '*' : 
			default :
				$_fields = 'u.idx, u.name, u.department, u.rank, u.enabled, u.TS, u.pic, up.filename, up.directory'; break;
		}
//		if($_fields == '*') 
		//var sql = 'SELECT u.idx, u.name, u.department, u.rank, u.enabled, u.TS, u.pic, up.idx, up.filename, up.directory FROM user u LEFT OUTER JOIN `user-pic` up ON u.pic = up.idx WHERE u.idx IN('+ idx +');';
		$sql = 'SELECT '.$_fields. ' FROM `user` u LEFT OUTER JOIN `user-pic` up ON u.pic = up.idx WHERE u.idx IN('. $_idx .');';
		$query = $this->db->query($sql);
		echo json_encode( Array("users"=>$query->result_array()) );
	}
	public function getList() {
		// CACHING http://codeigniter-kr.org/user_guide_2.1.0/libraries/caching.html
		if(empty($this->memberList) ){//|| time() - $listFetchTS > 5000) {
			$this->load->database('juliet');
			
			
			// INSERT DEPARTMENTS
			$result = Array('departments'=>Array());
			
			//$sql = 'SELECT * FROM department WHERE enabled=1 AND shown=1 order by sequence asc';
			$sql = 'SELECT *  FROM department WHERE enabled=1 AND shown=1 order by sequence asc';
			$query = $this->db->query($sql);
			$department = $query->result_array();
			
			
			for($i =0; $i< count($department); $i++) {
				$cRow = $department[$i];
				//echo "RES : " . print_r($result) . "\t CurrentROW : ". print_r($cRow) . "<br>";
				
				$level1 = trim($cRow['level1']);
				if(strlen( $level1) > 0 && !array_key_exists( $level1 , $result['departments'])) {
					$result['departments'][$level1] 
						= Array('sequence' => $cRow['sequence'], 'users' => Array(), 'title' => $level1, 'departments' => Array());
						//echo "<br>level1 key set<br>";
				} 
				
				$level2 = trim($cRow['level2']);
				if(strlen($level2) > 0 && !array_key_exists( $level2, $result['departments'][$level1]['departments']) ) {
					$result['departments'][$level1]['departments'][$level2]
						= Array('sequence' => $cRow['sequence'], 'users' => Array(), 'title' => $level2, 'departments' => Array());
						//echo "<br>level2 key set<br>";
				}
				
				$level3 = trim($cRow['level3']);
				if(strlen($level3) > 0 && !array_key_exists( $level3, $result['departments'][$level1]['departments'][$level2]['departments'] )) {
					$result['departments'][$level1]['departments'][$level2]['departments'][$level3]
						= Array('sequence' => $cRow['sequence'], 'users' => Array(), 'title' => $level3, 'departments' => Array());
						//echo "<br>level3 key set<br>";
				} 
				
				$level4 = trim($cRow['level4']);
				if(strlen($level4) > 0 && !array_key_exists( $level4, $result['departments'][$level1]['departments'][$level2]['departments'][$level3]['departments'] )) {
					$result['departments'][$level1]['departments'][$level2]['departments'][$level3]['departments'][$level4]
						= Array('sequence' => $cRow['sequence'], 'users' => Array(), 'title' => $level4, 'departments' => Array());
						//echo "<br>level4 key set<br>";
				} 
				
				$level5 = trim($cRow['level5']);
				if(strlen($level5) > 0 && !array_key_exists( $level5, $result['departments'][$level1]['departments'][$level2]['departments'][$level3]['departments'][$level4]['departments'] )) {
					$result['departments'][$level1]['departments'][$level2]['departments'][$level3]['departments'][$level4]['departments'][$level5]
						= Array('sequence' => $cRow['sequence'], 'users' => Array(), 'title' => $level5, 'departments' => Array());
						//echo "<br>level5 key set<br>";
				} 
				
				$level6 = trim($cRow['level6']);
				if(strlen($level6) > 0 && !array_key_exists( $level6, $result['departments'][$level1]['departments'][$level2]['departments'][$level3]['departments'][$level4]['departments'][$level5]['departments'] )) {
					$result['departments'][$level1]['departments'][$level2]['departments'][$level3]['departments'][$level4]['departments'][$level5]['departments'][$level6] 
						= Array('sequence' => $cRow['sequence'], 'users' => Array(), 'title' => $level6, 'departments' => Array());
						//echo "<br>level6 key set<br>";
				} 
				
				
			}
			
			
			// INSERT USERS
			//echo json_encode($result);
			
			$sql = 'SELECT user.idx, user.name, user.department, user.rank, user.pic, user.enabled, user.TS, department.level1, department.level2, department.level3, department.level4, department.level5, department.level6 FROM user INNER JOIN department ON user.department = department.idx WHERE user.enabled=1 AND department.enabled = 1 AND department.shown=1';
			$query = $this->db->query($sql);
			$users = $query->result_array();
			
			for($i=0; $i< count($users); $i++) {
				$user = $users[$i];
				//echo print_r($user);
				$uLevel1 = trim($user['level1']);
				$uLevel2 = trim($user['level2']);
				$uLevel3 = trim($user['level3']);
				$uLevel4 = trim($user['level4']);
				$uLevel5 = trim($user['level5']);
				$uLevel6 = trim($user['level6']);
				
				if( strlen( $uLevel1 ) > 0 ) {
					if( strlen( $uLevel2 ) > 0 ) {
						if( strlen( $uLevel3 ) > 0 ) {
								if( strlen( $uLevel4 ) > 0 ) {
									if( strlen( $uLevel5 ) > 0 ) {
										if( strlen( $uLevel6 ) > 0 ) {
											// level 1,2,3,4,5,6 > 0 nothing == null
												array_push($result['departments'][ $uLevel1 ]['departments'][ $uLevel2 ]['departments'][ $uLevel3 ]['departments'][ $uLevel4 ]['departments'][ $uLevel4 ]['departments'][ $uLevel6 ]['users'] , $user);
										} else {
											// level 1,2,3,4,5 > 0 level 6 == null
												array_push($result['departments'][ $uLevel1 ]['departments'][ $uLevel2 ]['departments'][ $uLevel3 ]['departments'][ $uLevel4 ]['departments'][ $uLevel5 ]['users'] , $user);
										}
									} else {
										// level 1,2,3,4 > 0 level 5, 6 == null
										//echo "<br><br><br><br>before<br><br><br><br>";````````````````````````````
										//print_r($result[ $uLevel1 ][ $uLevel2 ][ $uLevel3 ][ $uLevel4 ]);
										array_push($result['departments'][ $uLevel1 ]['departments'][ $uLevel2 ]['departments'][ $uLevel3 ]['departments'][ $uLevel4 ]['users'], $user);
										//echo "<br><br><br><br>after<br><br><br><br>";
										//print_r($result[ $uLevel1 ][ $uLevel2 ][ $uLevel3 ][ $uLevel4 ]);
										//echo "<br><br><br><br>end<br><br><br><br>";
									} 
								} else {
									// level1,2,3 > 0 level4,5,6 == null
										array_push($result['departments'][ $uLevel1 ]['departments'][ $uLevel2 ]['departments'][ $uLevel3 ]['users'] , $user);
								}
						} else {
							// level 1,2 > 0 level 3,4,5,6 == null
								array_push($result['departments'][ $uLevel1 ]['departments'][ $uLevel2 ]['users'], $user);
						}
					}	else {
						// level 1 > 0 level 2,3,4,5,6 == null
						array_push($result['departments'][ $uLevel1 ]['users'] , $user);
					}
				} else {
					// level 1 < 0
					"something went wrong";
				}
			}

			$this->memberList = $result;
			$this->listFetchTS = time();
			
		} else {

		}
		
		echo json_encode($this->memberList);
	}
	
	public function getSubDepartment() {
		$this->load->database('juliet');
		$_payload = $this->input->get('payload');
		$payload = json_decode($_payload, true);

		if(!array_key_exists('departments', $payload)) {
			echo json_encode(array('status'=>'0', 'msg' => 'something wrong'));
			return;
		}
				
		$deps = $payload['departments'];
		$nLevels = count($deps);
		$sql = 'SELECT level'.($nLevels+1).', sequence FROM department WHERE shown=1';
		
		for($i=0; $i<$nLevels; $i++) {
			
//			if($i == 0) {
//				$sql .= ' WHERE';
//			} else {
//				$sql .= ' AND';
//			}

			$sql .= ' AND';

			$sql .= ' level'.($i+1).'='.'"'.$deps[$i].'"';
			
		}
		$sql .= ' GROUP BY level'.($nLevels+1).";";
		
		$query = $this->db->query($sql);	
		
		$_result = $query->result_array();
		$result = array();
		for($i=0; $i<count($_result); $i++) {
			$_dep = $_result[$i];
			array_push($result, array('title'=>$_dep[('level'.($nLevels+1))], 'sequence'=> $_dep['sequence']));
		}
		
		echo json_encode(array('departments'=>$result, 'status'=>'1'));		
	}
	
		
	public function register() {
		$this->load->database('juliet');
		$_payload = $this->input->post('payload');
		$payload = json_decode($_payload,true);

		
		if(empty($payload)) {
			echo json_encode(array("status"=>0, "msg"=>"Insufficient payload."));
			return ;
		}
		
		$name = $payload['name'];
		$levels = $payload['levels'];
		$rank = $payload['rank'];
		$role = "";
		if(array_key_exists('role', $payload) )
			$role = $payload['role'];
				
		$sql = 'SELECT * FROM department WHERE ';
		
		for($i=0; $i<count($levels); $i++) {
			if($i != 0) $sql .= ' AND';
			$sql .= ' level'.($i+1).'="'.$levels[$i].'"';
		}
		
		$sql .= ' LIMIT 1;';

		$query = $this->db->query($sql);
		$_res = $query->result_array();
		$res = $_res[0];
		$depId = $res['idx']; 
		 
		// 해당 user가 존재하는지 질의
		// TODO 만약 같은 부서 안에 같은 사람이 존재하면 어떡하지
		$sql = 'SELECT * FROM user WHERE name="'.$name.'" AND department='.$depId.' AND rank = '. $rank .' LIMIT 1;';

		$query = $this->db->query($sql);
	
		if( $query->num_rows() == 0) {
			// 없다면 삽입
			$sql = 'INSERT INTO user (name, department, rank, TS, enabled';
			if($role && strlen($role)>0) $sql .= ', role';
			$sql .= ')';
			
			$sql .= ' values ("' . $name. '", ' . $depId. ', ' . $rank. ', "' . date('Y-m-d H:i:s') . '", ' . 0 . '';
			if($role && strlen($role)>0) $sql .= ', "' . $role . '"';
			$sql .= ')';
			$query = $this->db->query($sql);
			echo json_encode(Array('status'=>1, 'msg'=>'User Inserted', 'userIdx'=>$this->db->insert_id(), 'departmentIdx'=>$depId));

		} else {
			$_res = $query->result_array();
			$res = $_res[0];
			$userIdx = $res['idx'];
			$departmentIdx = $res['department'];
			echo json_encode(Array('status'=>1,'msg'=>'User Already Exists', 'userIdx'=>$userIdx, 'departmentIdx'=>$departmentIdx));
		}
		
		
		
	}
	
	public function isRegistered() {
		$this->load->database('juliet');
		$_payload = $this->input->get('payload');
		
		$payload = json_decode($_payload,true);
		$idx = $payload['idx'];
		$sql = 'SELECT enabled FROM user WHERE idx = '. $idx .' LIMIT 1;';
		$query = $this->db->query($sql);
		$_res = $query->result_array();
		
		$userExists = 0;
		$userEnabled = 0;
		
		if($query->num_rows() > 0) {
			$userExists = 1;
			$res = $_res[0];
			$userEnabled = $res['enabled'];
		}
		echo json_encode(Array('status' => 1, 'isRegistered'=> $userExists, 'isEnabled' => $userEnabled));
	}
}

?>