<?php
class WinmentorRestApi  {

	private $credentials;

	//POST
	const URL_ADD_PARTENER = 'http://192.168.20.203:8098/datasnap/rest/TServerMethods/InfoPartener//';
	const URL_ADD_FACTURA =  'http://192.168.20.203:8098/datasnap/rest/TServerMethods/IesiriClienti';
	const URL_ADD_CHITANTA = 'http://192.168.20.203:8098/datasnap/rest/TServerMethods/CasaBanca';
	const URL_GET_SOLD =  	 'http://192.168.20.203:8098/datasnap/rest/TServerMethods/%22GetSolduriClienti%22';
	//GET
	const URL_GET_SOLDURI =  'http://192.168.20.203:8098/datasnap/rest/TServerMethods/GetSolduriClienti';

	public function __construct($username, $password) {
		if (!$username && !$password) {
				throw new WinmentorRestApiException("Credentials not set in constructor");
		}
		$this->credentials = base64_encode("{$username}:{$password}");
	}

	/**
	 * private send method
	 * @param  array 	$aData send data
	 * @return array 	server response data
	 */
	private function _sendPost($http, $payload)
	{
		$ch = curl_init();
		curl_setopt_array($ch, array(
            CURLOPT_URL => $http,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $payload,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => array(
				"Authorization: Basic ".$this->credentials,
                "Content-Type: application/json",
                "Connection: keep-alive",
                "Cache-Control: no-cache"
            )
        ));
		
		$response 		= curl_exec($ch);
		$httpcode		= curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		// Process result
		if ($httpcode == "401") { // unauthorized
			file_put_contents('/var/www/logs/app/wmeRest.log', print_r(['data' => "[ ".date('d-m-Y H:i:s')." ]", 'errorWmeRestApi' => 1, 'httpcode' => $httpcode, 'response' => $response, 'payload' => $payload], true) , FILE_APPEND | LOCK_EX);
			throw new WinmentorRestApiException("401 : There was an error authenticating the sender account.");
		} else if ($httpcode == "200") { // OK
			return $this->parseResponse($response);
		} else { // Unkown
			file_put_contents('/var/www/logs/app/wmeRest.log', print_r(['data' => "[ ".date('d-m-Y H:i:s')." ]", 'errorWmeRestApi' => 1, 'httpcode' => $httpcode, 'response' => $response, 'payload' => $payload], true) , FILE_APPEND | LOCK_EX);
			throw new WinmentorRestApiException("{$httpcode} : {$response}");				
		}
	}

	private function _sendGet($http)
	{
		$ch = curl_init();
		curl_setopt_array($ch, array(
            CURLOPT_URL => $http,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => array(
				"Authorization: Basic ".$this->credentials,
                "Content-Type: application/json",
                "Connection: keep-alive",
                "Cache-Control: no-cache"
            )
        ));
		
		$response 		= curl_exec($ch);
		$httpcode		= curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		// Process result
		if ($httpcode == "401") { // unauthorized
			file_put_contents('/var/www/logs/app/wmeRest.log', print_r(['data' => "[ ".date('d-m-Y H:i:s')." ]", 'errorWmeRestApi' => 1, 'httpcode' => $httpcode, 'response' => $response], true) , FILE_APPEND | LOCK_EX);
			throw new WinmentorRestApiException("401 : There was an error authenticating the sender account.");
		} else if ($httpcode == "200") { // OK
			return $this->parseResponse($response, false);
		} else { // Unkown
			file_put_contents('/var/www/logs/app/wmeRest.log', print_r(['data' => "[ ".date('d-m-Y H:i:s')." ]", 'errorWmeRestApi' => 1, 'httpcode' => $httpcode, 'response' => $response], true) , FILE_APPEND | LOCK_EX);
			throw new WinmentorRestApiException("{$httpcode} : {$response}");				
		}
	}

	private function parseResponse($response, $asArray = true) {
		$arrReturn = json_decode($response, $asArray);
		switch (json_last_error()) {
			case JSON_ERROR_DEPTH:
				throw new WinmentorRestApiException("JSON : Maximum stack depth exceeded : {$response}");
			case JSON_ERROR_STATE_MISMATCH:
				throw new WinmentorRestApiException("JSON : Underflow or the modes mismatch : {$response}");
			case JSON_ERROR_CTRL_CHAR:
				throw new WinmentorRestApiException("JSON : Unexpected control character found : {$response}");
			case JSON_ERROR_SYNTAX:
				throw new WinmentorRestApiException("JSON : Syntax error, malformed JSON : {$response}");
			case JSON_ERROR_UTF8:
				throw new WinmentorRestApiException("JSON : Malformed UTF-8 characters, possibly incorrectly encoded : {$response}");
		}
		return $arrReturn;
	}
	
	/**
	 * Send factura
	 * @param  string	$payload
	 */
	public function sendFactura($payload)
	{
		return $this->_sendPost(self::URL_ADD_FACTURA, $payload);
	}

	/**
	 * Send chitanta
	 * @param  string	$payload
	 */
	public function sendChitanta($payload)
	{
		return $this->_sendPost(self::URL_ADD_CHITANTA, $payload);
	}

	/**
	 * Send partener
	 * @param  string	$payload
	 */
	public function sendPartener($payload)
	{
		return $this->_sendPost(self::URL_ADD_PARTENER, $payload);
	}

	/**
	 * Get sold
	 * @param  string	$payload
	 */
	public function getSold($payload)
	{
		return $this->_sendPost(self::URL_GET_SOLD, $payload);
	}

	/**
	 * Get solduri
	 */
	public function getSolduri()
	{
		return $this->_sendGet(self::URL_GET_SOLDURI);
	}
}
	/**
	 * Exception class for WinmentorRestApiException
	 */
	class WinmentorRestApiException extends \Exception {};
?>