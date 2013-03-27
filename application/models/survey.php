<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Survey extends CI_Model {
	
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
			case "GET_RESULT":
				$this->getResult();
				break;
			case "ANSWER_SURVEY":
				$this->answerSurvey();
				break;
			default:
				die();
		}
	}
	
	private function getResult(){
		$data = $this->requestPayload['data'];
		
		if( !isset($data['survey_hash']) || !$data['survey_hash'] ) {
		
			$this->responsePayload['status'] = INSUFFICIENT_ARGUMENTS;
			$this->responsePayload['data'] = 'Insufficient Informations';
			return;
		}
		
		$seq = $this->db->query('select seq from js_survey where survey_hash = ?',$data['survey_hash'])->row('seq');
		
		$sql = 'SELECT receiver_seq FROM `js_survey_history` WHERE survey_seq='.$seq;
		$query = $this->db->query($sql);
		$nReceivers = $query->num_rows();
	
		$sql = 'SELECT answersheet FROM `js_survey_history` WHERE survey_seq='.$seq.' AND answered=1';
		$query = $this->db->query($sql);
		$_ass = $query->result_array();
		$nResponders = count($_ass);
		
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
	
		$this->responsePayload['status'] = SUCCESS;
		$this->responsePayload['data'] = array('result'=> $result, 'nReceivers'=>$nReceivers, 'nResponders'=>$nResponders);
	}
	
	private function answerSurvey () {
		$data = $this->requestPayload['data'];
		
		if( !isset($data['user_hash']) || !isset($data['survey_hash']) || !isset($data['answersheet']) || 
				!$data['user_hash'] || !$data['survey_hash'] || !$data['answersheet'] ) {
		
			$this->responsePayload['status'] = INSUFFICIENT_ARGUMENTS;
			$this->responsePayload['data'] = 'Insufficient Informations';
			return;
		}
		$userSeq = $this->db->query('select seq from js_user where user_hash = ?',$data['user_hash'])->row('seq');
		$surveySeq = $this->db->query('select seq from js_survey where survey_hash = ?',$data['survey_hash'])->row('seq');
		$answersheet = $data['answersheet'];
			
		$sql = 'UPDATE `js_survey_history` SET answersheet="'.$answersheet.'" , answered_ts = now() WHERE survey_seq ='.$surveySeq.' AND receiver_seq ='.$userSeq;
		$this->db->query($sql);
		
		$this->responsePayload['status'] = SUCCESS;
		$this->responsePayload['data'] = "success";
	}
}


/* End of file user.php */