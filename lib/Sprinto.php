<?php
require "Resty.php";
class Sprinto extends Resty
{
	protected $api = "https://inetgateway.woollymammoth.net/testapi/";
	protected $userid = "paulk";
	protected $usergroup = "admin";
	protected $machine = "wooluser26";
	protected $password = "DbioM10";
	
	public function __construct() {
		parent::__construct();
		if(!empty($_SESSION['user_name'])) {
			$this->userid = $_SESSION['user_name'];
			$this->usergroup = $_SESSION['user_group'];
			$this->password = $_SESSION['password'];
		}
		parent::setCredentials($this->userid.':'.$this->usergroup.':'.$this->machine,$this->password);
		parent::setBaseURL($this->api);
	}

	public function user_groups($user_name = null) {
		if(!empty($user_name)) {
			$response = $this->get('authenticationservice/usergroups/',array('username' => $user_name));
			return $response;
		}
		else return null;
	}
	
	public function user_authenticate($user_name = null, $password = null, $user_group = null) {
		if(!empty($user_name) && !empty($password) && !empty($user_group)) {
			$body = $this->build_xml(array('authentication_request' => array('password' => $password, 'user_group' => $user_group, 'user_name' => $user_name)));
			$response = $this->post("authenticationservice/authenticate/",$body);
			if($response->IsAuthenticated == 'true') {
				$_SESSION['user_name'] = $user_name;
				$_SESSION['password'] = $password;
				$_SESSION['user_group'] = $user_group;
				$this->__construct();
				return true;
			}
			else return false;
		}
		else return false;
	}
	
	public function constituent_search($query,$type) {
		$type = $type == 'json' ? 'application/json' : 'application/xml';
		$response = parent::get('ConstituentService/Constituents/Search?type=fluent&q='.$query,null,array('Accept' => $type));
		print_r($response);
		return $response['body'];
		print_r($response);
	}

	public function execute($procedure_no = null, $parameters = null) {
		//Convert the request paramaters into required MSSQL formatting
		$param_str = '';
		if(!empty($parameters)) {
			foreach($parameters as $param => $value) {
				$param_str .= '@'.$param.'='.$value;
				if($value !== end($parameters)) {
					$param_str .= '&amp;';
				}
			}
		}
		
		//Construct the request body in XML
		$body = '<LocalProcedureRequest>
					<Parameters>'.$param_str.'</Parameters>
					<ProcedureId>'.$procedure_no.'</ProcedureId>
				 </LocalProcedureRequest>';
		
		//Send request
		$request = parent::post("dataservice/execute/",$body);
		$response = $this->process_request($request);
		foreach ($response as $table) {
			$return[] = $table;
		}
		return $return;
	}
	
	private function build_xml_words($word) {
		ucwords(str_replace("_"," ",$word));
		$word = str_replace(" ","",ucwords(str_replace("_"," ",$word)));
		return $word;		
	}
	
	
	private function build_xml($collection) {
		//Build a one level xml body for post
		$xml = null;
		foreach($collection as $s => $b) {
			$s = $this->build_xml_words($s);
			$xml .= '<'.$s.'>';
			foreach($b as $i => $v) {
				$i = $this->build_xml_words($i);
				$xml .= '<'.$i.'>'.$v.'</'.$i.">";
			}
			$xml .= '</'.$s.'>';
		}
		return $xml;
	}
	
	public function post($url, $querydata=null) {
		$request = parent::post($url,$querydata,array('Content-Type' => 'text/xml'));
		$response = $this->process_request($request);
		return $response;
	}
	
	public function get($url, $querydata=null) {
		if(!empty($querydata)) {
			$param_str = '?';
			foreach($querydata as $p => $v) {
				$param_str .=  $p.'='.$v.'&';
			}
		}
		else $param_str = '';
		$request = parent::get($url.$param_str);
		$response = $this->process_request($request);
		return	$response;
	}
	
	
	private function process_request($request) {
		if($request['status'] != '200') {
		}
		else return $request['body'];
	}

}