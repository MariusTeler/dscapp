<?php
class SMSPushNotification  {

	const URL_SEND = 'https://ro.sopranodesign.com/cgphttp/servlet/sendmsg'; // URL To SEND SMS
	const URL_QUERY_STATUS = 'https://ro.sopranodesign.com/cgphttp/servlet/querymsg'; // URL To QUERY STATUS
	const MSG_VALIDITY = 240;

	private static $optionals = array( // all optional
		/*
			Request a Delivery Receipt (DR) acknowledgment for message. Returns msg_id, user and status to the customer via the specified channel for acknowledgments
				0
				Off, No delivery receipt requested (default)
				1
				Network DR requested where final delivery is success or failure
				2
				Network DR requested only if the message is not delivered when it reaches the final state
				3
				Intermediate notification requested of a message delivery attempt
				4
				Send both intermediary and network DRs
				5
				Send both intermediary and network DRs where the final delivery outcome is delivery failure
			*/
		'registered',
		/*
			The validity period of the message, after which time the message will not be sent.
			Relative validity (from message submission time) in minutes (positive integer greater than 0, default = 10080 [7 days])
		*/
		'valid',
		/*
			Specifies the envelope type to be used to send HTTP response
			0 Text/plain (Default)
			1 Text/XML
		*/
		'responseType',
		/*
			Integer
			Enum [1,2] means [International,National]
			Specify 1 if you want set the destination MSISDN format to international E.164 format.
			Specify 2 if you want only use domestic numbers.
			Not providing a destinationTON value (default) number can fit into multiple countries. User's MSISDN Conversation preference is used and this is set in the portal.
		*/
		'destinationTON'
	);

	public function __construct($username, $password) {
		if (!$username && !$password) {
				throw new SMSPushNotificationException("Credentials not set in constructor");
		}
		$this->credentials = base64_encode("{$username}:{$password}");
	}

	/**
	 * Parses the payload
	 * @param  array $aPayload can only have the keys 'messageType' and 'destination'
	 * @return array with only data an notification
	 */
	private static function _parsePayload($aPayload) 
	{
		if (
			(! is_array($aPayload) ||  
			count($aPayload) === 0)
			|| (! (array_key_exists("text", $aPayload) && array_key_exists("destination", $aPayload) || array_key_exists("msg_id", $aPayload)))
		) {
			throw new SMSPushNotificationException("Invalid mandatory payload : destination, text, msg_id");
		}
		// msg_id
		if (array_key_exists("msg_id", $aPayload) && is_array($aPayload['msg_id']) && count($aPayload['msg_id'])) {
			$aPayload['msg_id'] = implode(',', $aPayload['msg_id']);
		}
		return $aPayload;
	}

	/**
	 * Parses the conditionals
	 * @param  array $conditionals
	 * @return array with only keys that can be used (self::$optional)
	 */
	private static function _parseOptionals($optionals) 
	{
		$arrReturn = array();
		foreach ($optionals as $key => $value) {
			if (in_array($key, self::$optionals)) {
				$arrReturn[$key] = $value;
			} 
			else {
				throw new SMSPushNotificationException('Invalid Options: unkown key "'.$key.'"');
			}
		}
		if(!in_array('registered', $arrReturn)) $arrReturn['registered'] = 1;
		if(!in_array('valid', $arrReturn)) $arrReturn['valid'] = self::MSG_VALIDITY;
		$arrReturn['responseType'] = 0;
		if(!in_array('destinationTON', $arrReturn)) $arrReturn['destinationTON'] = 2;
		return $arrReturn;
	}

	/**
	 * private send method
	 * @param  array 	$aData send data
	 * @return array 	server response data
	 */
	private function _send($http, $aData)
	{
		$aData = http_build_query($aData);
		// Prepare headers
		$aHeaders = array(
			'Authorization: Basic '.$this->credentials,
			'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
			'Content-Length: '.strlen($aData)
		);
		// Curl request
		$ch				= curl_init();
		curl_setopt($ch, CURLOPT_URL, $http);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeaders);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $aData);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response 		= curl_exec($ch);
		$httpcode		= curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		// Process result
		if ($httpcode == "401") { // unauthorized
			error_log($httpcode . " : " . $response . ":" .$aData);
			throw new SMSPushNotificationException("There was an error authenticating the sender account.", 401);
		} else if ($httpcode == "200") { // OK
			if($http == self::URL_SEND) return $this->parseSendResponse($response);
			if($http == self::URL_QUERY_STATUS) return $this->parseQyeryResponse($response);
			return [];
		} else { // Unkown
			error_log($httpcode . " : " . $response . ":" .$aData);
			throw new SMSPushNotificationException($response, $httpcode);				
		}
	}

	private function parseSendResponse($response) {
		$arrReturn = [];
		//error_log(print_r($response, true));
		$lines = preg_split("/\\r\\n|\\r|\\n/", $response);
		//error_log(print_r($lines, true));
		if(is_array($lines) && count($lines) > 0) {
			$firstLine = explode(' ', $lines[0]);
			//error_log(print_r($firstLine, true));
			$arrReturn['success'] = false;
			$arrReturn['code'] = '';
			$arrReturn['text'] = 'ERROR - unknown';
			$arrReturn['msg_id'] = 0;
			//error_log(print_r(count($firstLine), true));
			if(count($firstLine) == 3) {
				$arrReturn['success'] = $firstLine[0] == 0 ? true : false;
				$arrReturn['code'] = $firstLine[1];
				$arrReturn['text'] = $firstLine[2];
			}
			array_shift($lines);
			$secondLine = explode(' ', $lines[0]);
			//error_log(print_r($secondLine, true));
			//error_log(print_r(count($secondLine), true));
			if(count($secondLine) == 2) {
				$arrReturn['msg_id'] = $secondLine[1];
			}
			else {
				$arrReturn['success'] = false;
				$arrReturn['text'] = $lines[0];
			}
		}
		return $arrReturn;
	}
	
	private function parseQyeryResponse($response) {
		$arrReturn = [];
		$lines = explode('\n', $response);
		if(is_array($lines) && count($lines) > 0) {
			$firstLine = explode(' ', $lines[0]);
			$arrReturn['success'] = false;
			$arrReturn['code'] = '';
			$arrReturn['text'] = 'ERROR - unknown';
			if(count($firstLine) == 3) {
				$arrReturn['success'] = $firstLine[0] == 0 ? true : false;
				$arrReturn['code'] = $firstLine[1];
				$arrReturn['text'] = $firstLine[2];
			}
			array_shift($lines);
			$arrReturn['messages'] = [];
			foreach($lines as $line) {
				$lineParts = explode(' ', $line);
				if(count($lineParts) == 2) {
					$arrReturn['messages']['id'] = $lineParts[0];
					$arrReturn['messages']['status'] = $lineParts[1];
				}
			}
		}
		return $arrReturn;
	}
	/**
	 * Send sms notification to single destination
	 * @param  array	$aPayload           
	 * @param  array	$conditionals           
	 * @return array 	server response data
	 */
	public function sendSMS($aPayload, $optionals = [])
	{
		$aData			= self::_parsePayload($aPayload);
		$optionals		= self::_parseOptionals($optionals);
		return $this->_send(self::URL_SEND, array_merge($aData, $optionals));
	}

	/**
	 * Query sms status
	 * @param  array	$aPayload           
	 * @param  array	$conditionals           
	 * @return array 	server response data
	 */
	public function querySMS($aPayload)
	{
		$aData			= self::_parsePayload($aPayload);
		return $this->_send(self::URL_QUERY_STATUS, $aData);
	}
}
	/**
	 * Exception class for SMSPushNotification
	 */
	class SMSPushNotificationException extends \Exception {};
?>