<?php

namespace takamoriyamashiro\Blayn;

class BlaynMail
{
	
	const REQUEST_OPTIONS = ['encoding' => 'UTF-8', 'escaping' => 'markup'];

	private $access_token = false;
	private $errors = [];
	
	
	public function __construct($id, $password, $apikey)
	{
		$params = [$id, $password, $apikey];
		$request = xmlrpc_encode_request('authenticate.login', $params, self::REQUEST_OPTIONS);
		$context = $this->makeContext($request);
		$file = file_get_contents("https://api.bme.jp/xmlrpc/1.0", false, $context);
		$response = xmlrpc_decode($file);
		$this->access_token = $response;
	}
	
	public function logout()
	{
		$params = [$this->access_token];
		$request = xmlrpc_encode_request('authenticate.logout', $params, self::REQUEST_OPTIONS);
		$context = $this->makeContext($request);
		$file = file_get_contents("https://api.bme.jp/xmlrpc/1.0", false, $context);
		$response = xmlrpc_decode($file);
		$this->access_token = false;
		$this->errors = [];
		return $response;
	}
	
	private function makeContext($request, $method = 'POST', $header = "Content-Type: text/xml")
	{
		$context = stream_context_create([
			'http' => [
				'method' => $method,
				'header' => $header,
				'content' => $request
			]
		]);
		return $context;
	}
	
	public function getToken()
	{
		return $this->access_token;
	}
	
	
	public function addUser($email, $group)
	{
		if (empty($email) || empty($group) || $this->access_token === false) {
			return false;
		}
		$params = [$this->access_token, ['c15' => $email, 'c21' => $group]];
		$request = xmlrpc_encode_request('contact.detailCreate', $params, self::REQUEST_OPTIONS);
		$context = $this->makeContext($request);
		$file = file_get_contents("https://api.bme.jp/xmlrpc/1.0", false, $context);
		$data = xmlrpc_decode($file);
		if($this->checkError($data)){
			return false;
		}
		return $data;
	}
	
	
	const STATUS_HAISHIN = '配信中';
	const STATUS_TEISHI = '配信停止';
	const STATUS_KAIJO = '解除';
	const STATUS_SAKUJO = '削除';
	const STATUS_ERROR_TEISHI = 'エラー停止';
	
	public function changeStatus($id,$status)
	{
		if(
			self::STATUS_HAISHIN != $status &&
			self::STATUS_TEISHI != $status &&
			self::STATUS_KAIJO != $status &&
			self::STATUS_SAKUJO != $status &&
			self::STATUS_ERROR_TEISHI != $status
		){
			return false;
		}
		
		$options = array('encoding' => 'UTF-8', 'escaping'=>'markup');
		$params = array($this->access_token, (int)$id, array('status'=>$status));
		$request = xmlrpc_encode_request('contact.detailUpdate', $params, self::REQUEST_OPTIONS);
		$context = $this->makeContext($request);
		$file = file_get_contents("https://api.bme.jp/xmlrpc/1.0", false, $context);
		$data = xmlrpc_decode($file);
		if($this->checkError($data)){
			return false;
		}
		return $data;
	}
	
	
	private function checkError($data){
		if($data==-3) {
			$this->errors[] = ['code'=>-3,'message'=>'アドレス重複'];
			 return true;
		}else if($data==-2) {
			$this->errors[] = ['code'=>-2,'message'=>'登録上限'];
			return true;
		}else if($data==-1) {
			$this->errors[] = ['code'=>-1,'message'=>'パラメータ不正'];
			return true;
		}else if($data==0) {
			$this->errors[] = ['code'=>0,'message'=>'登録処理に失敗'];
			return true;
		}
		return false;
	}
	
	public function getErrors(){
		return $this->errors;
	}
	
	
}