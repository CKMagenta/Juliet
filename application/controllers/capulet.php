<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Capulet extends CI_Controller {

	function __construct() {
		parent::__construct();
		$this->load->database();
	}
	
	public function index() {

	$query = 'select
					if(u.is_enabled,"승인","미승인") is_enabled,
					user_hash,
					user_name,
					r.title user_rank,
					user_role,
					trim(replace(dept_full_name,":"," ")) dept_name,
					u.created_ts,
					u.modified_ts
				from js_user u
				left join js_department d
					on u.dept_seq = d.seq
				left join js_user_rank r
					on r.seq = u.user_rank';
		$users = $this->db->query($query)->result_array();
		$query = 'select b.seq, user_name, content, from_unixtime(b.created_ts) from js_bug_report b 
				left join js_user u on u.user_hash = b.user_idx where b.is_open=1 order by b.created_ts desc
				';
		$bugs=  $this->db->query($query)->result_array();
		$this->load->view('admin/auth',get_defined_vars());
	}
	
	public function auth(){
	}
	
	public function device(){
		$user_hash = $this->input->get('u',true);
		$query = 'select seq, user_name from js_user where user_hash = ?';
		$user_info = $this->db->query($query,$user_hash)->row_array();
		$query = 'select 
				if(is_enabled,"승인","미승인") is_enabled,
				(case device_type when "a" then "Android" when "i" then "iOS" else "기타" end) device_type,
				uuid,
				regid,
				created_ts,
				modified_ts
				from js_device where user_seq = ?';
		$device_info = $this->db->query($query,$user_info['seq'])->result_array();
		$this->load->view('admin/device',get_defined_vars());
	}
	
	public function set_user_status() {
		$user_hashes = $this->input->post('u',true);
		$isEnabled = $this->input->post('e',true);
		$user_seqs = array();
		if ( count($user_hashes) > 0 ) {
		
			$query = 'select seq from js_user where user_hash in(';
			$binds = array();
			foreach ( $user_hashes as $user  ) {
				$query .= "?,";
				$binds[]=$user;
			}
			$query = substr_replace($query,"",-1,1);
			$query .= ")";
			$res = $this->db->query($query,$binds)->result_array();
			
			foreach ( $res as $row ) {
				$user_seqs[] = $row['seq'];
			}
			
			$query = "update js_user set is_enabled = ".$this->db->escape($isEnabled)." where seq in (";
			foreach ( $user_seqs as $user  ) {
				$query .= "?,";
			}
			$query = substr_replace($query,"",-1,1);
			$query .= ")";
			$res = $this->db->query($query,$user_seqs);
			echo "완료되었습니다.";
		}
	}
	
	public function set_dev_status(){
		$dev_uuids = $this->input->post('d',true);
		$isEnabled = $this->input->post('e',true);
		$dev_seqs = array();
		if ( count($dev_uuids) > 0 ) {
		
			$query = 'select seq from js_device where uuid in(';
			$binds = array();
			foreach ( $dev_uuids as $dev  ) {
				$query .= "?,";
				$binds[]=$dev;
			}
			$query = substr_replace($query,"",-1,1);
			$query .= ")";
			$res = $this->db->query($query,$binds)->result_array();
				
			foreach ( $res as $row ) {
				$dev_seqs[] = $row['seq'];
			}
				
			$query = "update js_device set is_enabled = ".$this->db->escape($isEnabled)." where seq in (";
			foreach ( $dev_seqs as $dev  ) {
				$query .= "?,";
			}
			$query = substr_replace($query,"",-1,1);
			$query .= ")";
			$res = $this->db->query($query,$dev_seqs);
			echo "완료되었습니다.";
		}
	}
	
	public function adjust_dept(){
		$query_select =	'select d1.seq from ';
		$table = 'js_department d1';
		$current_depth = 1;
		$where = ' where d'.$current_depth.'.seq is not null ';
		
		$this->db->query('update js_department set dept_full_name=":", dept_full_path=":"');
		while($this->db->query($query_select.$table.$where)->num_rows()>0) {
			//depth update
			$query_update_depth = 
				'update js_department 
					set depth = ?
					where seq in( select * from ( '.$query_select.$table.$where.') tmp )';
			$this->db->query($query_update_depth,$current_depth);
			
			$query_update_leaf_info =
				'update '.$table.
				' set 
					d1.dept_full_path = concat( ifnull(concat(":",d'.$current_depth.'.seq),"" ) ,d1.dept_full_path), 
					d1.dept_full_name = concat( ifnull(concat(":",d'.$current_depth.'.dept_name),"" ) ,d1.dept_full_name)
				where d1.seq in ( select * from ( '.$query_select.$table.$where.') tmp )';
			$this->db->query($query_update_leaf_info);
			
			$current_depth++;
			$table .= ' left join js_department d'.$current_depth.' on d'.($current_depth-1).'.parent_seq = d'.$current_depth.'.seq ';
			$where = ' where d'.$current_depth.'.seq is not null ';
			
		}
	}
	
	public function delete_bug_report()
	{
		$seq = $this->input->post('seq',TRUE);
		if(!$seq) return;
		$this->load->database();
		$query = 'update js_bug_report set is_open = 0 where seq = ?';
		$this->db->query($query,$seq);
	}
}