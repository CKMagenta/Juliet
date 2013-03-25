<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Device extends CI_Controller {


	public function index()
	{
		echo "welcome!";
	}
	public function register(){
		$this->load->database('juliet');
		$regid = $this->input->post('regid');
		$uuid = $this->input->post('uuid');
		$user = $this->input->post('user');
		$type = $this->input->post('type');
		
		
		if($regid == '' || strlen($regid) == 0) {
			echo json_encode(array("msg"=>"null regid"));
		} else {
			// 해당 regid 가 존재하는지 질의
			$sql = 'SELECT * FROM device WHERE userid = "'. $regid . '"';
			$query = $this->db->query($sql);
			if( $query->num_rows() ==0) {
				// 없다면,
				
				// 동일한 uuid가 존재하는지 질의
				$sql = 'SELECT * FROM device WHERE uuid = "'. $uuid . '"';
				$query = $this->db->query($sql);
				$row = $query->row();
				if( $query->num_rows() > 0) {
					// 있다면 업데이트
					$sql = 'UPDATE device SET userid = "'. $regid .'" , TS = "'. date('Y-m-d H:i:s') .'" WHERE uuid = "'. $uuid .'"';
					$query = $this->db->query($sql);
					echo json_encode(Array('msg'=>'Device Registration Updated', 'id'=> $row->idx, 'regid'=>$regid, 'uuid' => $uuid));
				} else {
					// 없다면 삽입
					$sql = 'INSERT INTO device (user, userid, type, uuid, joindate)' .
							'values ("' . $user. '", "' . $regid. '", "' . $type. '", "' . $uuid . '", "' . date('Y-m-d H:i:s') . '")';
					$query = $this->db->query($sql);
					echo json_encode(Array('msg'=>'Device Registration Inserted', 'id'=>$this->db->insert_id(), 'regid'=>$regid, 'uuid' => $uuid));
				}
			} else {
			// 있다면 ??
			}
		}
	}
	
	public function unregister() {
		$this->load->database('juliet');
		$regidids = $this->input->post('regidids');
		$uuid = $this->input->post('uuid');
		
		
		if(count($regidIds) > 0) {
			for($i=0; $i<count($regidIds) ; $i++) {
				$sql = 'DELETE FROM device WHERE id =' . $regidIds[$i];
				$query  = $this->db->query($sql);
			}

			echo json_encode(Array('msg'=>'Device Unregistration Deleted'));

		} else {
			echo json_encode(Array('msg'=>'RegId ids count : 0'));
		}		
	}
	
	public function isRegistered() {
		$this->load->database('juliet');
		$_payload = $this->input->get('payload');
		$payload = json_decode($_payload,true);
		$idx = $payload['idx'];
		$regid = $payload['regid'];
		$uuid = $payload['uuid'];
		
		/*
		$sql = 'SELECT enabled FROM user WHERE idx = '. $idx .' LIMIT 1;';
		$query = $this->db->query($sql);
		$_res = $query->result_array();
		$res = $_res[0];
		$userEnabled = $res['enabled'];
		*/
		
		if($idx == NULL || $idx == '' || strlen($idx)==0 ||
		 $regid == NULL || $regid == '' || strlen($regid) == 0 ||
		 $uuid == NULL || $uuid == '' || strlen($uuid)==0 ) {
			 echo json_encode(Array('status' => 0, 'msg' => 'Insufficient Informations'));
			 return;
		 }
		
		$deviceRegistered = 0;
		$deviceEnabled = 0;
		
		$sql = 'SELECT * FROM device WHERE user='.$idx.' AND userid="'.$regid.'" AND uuid="'.$uuid. '" LIMIT 1;';
		$query = $this->db->query($sql);
		if($query->num_rows() > 0) {
			$deviceRegistered = 1;
			$_res = $query->result_array();
			$res = $_res[0];
			$deviceEnabled = $res['enabled'];
			//$permission = $res['permission'];
		}
		echo json_encode(Array('status' => 1, 'isRegistered'=> $deviceRegistered, 'isEnabled'=>$deviceEnabled));//'permission'=> $permission));
	}

}

?>