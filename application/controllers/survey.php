<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Survey extends CI_Controller {


	public function index()
	{
		echo "welcome!";
	}
	public function getResult(){
		$this->load->database('juliet');
	
		$idx = $this->input->get('idx');
	/*
		$sql = 'SELECT appendix FROM `survey` WHERE idx='.$idx;
		$query = $this->db->query($sql);
		$_appendix = $query->row_array();
		$appendix = json_decode("'".$_appendix['appendix']."'", false, 1);
		echo $_appendix['appendix'];
		$_survey = $appendix['appendixes'][0]['sValue'];
		$survey = json_decode($_survey);
		
		$questions = $survey['questions'];
		$nQuestions = count($questions);
	*/	
		$sql = 'SELECT receiver FROM `survey-history` WHERE survey='.$idx;
		$query = $this->db->query($sql);
		$nReceivers = $query->num_rows();
		
		$sql = 'SELECT answersheet FROM `survey-history` WHERE survey='.$idx.' AND answered=1';
		$query = $this->db->query($sql);
		$nResponders = $query->num_rows();
		$_ass = $query->result_array();

		$result = array();
		
		for($ri = 0; $ri<$nResponders; $ri++) {
			$_as = $_ass[$ri];
			$as = json_decode($_as['answersheet'], true);
			
			for($qi=0; $qi<count($as); $qi++) {
				
				if(!array_key_exists($qi,$result)) {
					$result[$qi]=array();
				}
				$a = $as[$qi];
				$m = $a['isMultiple'];
				$os = $a['options'];
				
				if($m) {
					for($oi = 0; $oi < count($os); $oi++) {
						if(!array_key_exists($os[$oi],$result[$qi])) {
							$result[$qi][ $os[$oi] ] = 1;
						} else {
							$result[$qi][ $os[$oi] ] += 1;
						}
						
					}
				} else {
					if(!array_key_exists($os,$result[$qi])) {
						$result[$qi][$a['options']] = 1;
					} else {
						$result[$qi][$a['options']] += 1;
					}
				
				}	//$m END
				
			}	// for nQuestions END
			
		}	// for nResponders END
		
		echo json_encode(array('status'=>'1', 'result'=> $result, 'nReceivers'=>$nReceivers, 'nResponders'=>$nResponders));
	}


public function getResultJsonp(){
		$this->load->database('juliet');
	
		$idx = $this->input->get('idx');
	/*
		$sql = 'SELECT appendix FROM `survey` WHERE idx='.$idx;
		$query = $this->db->query($sql);
		$_appendix = $query->row_array();
		$appendix = json_decode("'".$_appendix['appendix']."'", false, 1);
		echo $_appendix['appendix'];
		$_survey = $appendix['appendixes'][0]['sValue'];
		$survey = json_decode($_survey);
		
		$questions = $survey['questions'];
		$nQuestions = count($questions);
	*/	
		$sql = 'SELECT receiver FROM `survey-history` WHERE survey='.$idx;
		$query = $this->db->query($sql);
		$nReceivers = $query->num_rows();
		
		$sql = 'SELECT answersheet FROM `survey-history` WHERE survey='.$idx.' AND answered=1';
		$query = $this->db->query($sql);
		$nResponders = $query->num_rows();
		$_ass = $query->result_array();

		$result = array();
		
		for($ri = 0; $ri<$nResponders; $ri++) {
			$_as = $_ass[$ri];
			$as = json_decode($_as['answersheet'], true);
			
			for($qi=0; $qi<count($as); $qi++) {
				
				if(!array_key_exists($qi,$result)) {
					$result[$qi]=array();
				}
				$a = $as[$qi];
				$m = $a['isMultiple'];
				$os = $a['options'];
				
				if($m) {
					for($oi = 0; $oi < count($os); $oi++) {
						if(!array_key_exists($os[$oi],$result[$qi])) {
							$result[$qi][ $os[$oi] ] = 1;
						} else {
							$result[$qi][ $os[$oi] ] += 1;
						}
						
					}
				} else {
					if(!array_key_exists($os,$result[$qi])) {
						$result[$qi][$a['options']] = 1;
					} else {
						$result[$qi][$a['options']] += 1;
					}
				
				}	//$m END
				
			}	// for nQuestions END
			
		}	// for nResponders END
		
		
		
		echo $this->input->get('callback').'('.json_encode(array('status'=>'1', 'result'=> $result, 'nReceivers'=>$nReceivers, 'nResponders'=>$nResponders)).');';
	}
	
	public function answerSurvey () {
			$this->load->database('juliet');
		
			$_payload = $this->input->post('payload');
			$payload = json_decode($_payload, true);
			$idx = $payload['idx'];
			$answersheet = $payload['answersheet'];
			$userIdx = $payload['userIdx'];
			
		$sql = 'UPDATE `survey-history` SET answersheet="'.$answersheet.'" WHERE survey='.$idx.' AND receiver='.$userIdx;
		$query = $this->db->query($sql);
		
		$result = -1;
		if($query == TRUE) {
			$result = 1;
		} else {
			$result = 0;
		}
		echo json_encode(Array('status'=>$result, 'query'=>$sql, 'payload'=>$_payload));
	}
}
?>