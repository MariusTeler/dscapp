<?php

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Exception\Messaging as MessagingErrors;

class FCMPushNotification  {

	public const AUTH_JSON_FILE_PATH = '/etc/credentials/cds-architecturecomponents-firebase-adminsdk-xbr1b-87798c872b.json';
	private Messaging $messaging;
	
	private static $Options = array( // all optional
		/*
			collapse_key: String
			This parameter identifies a group of messages (e.g., with collapse_key: "Updates Available") that can be collapsed, so that only the last message gets sent when delivery can be resumed. This is intended to avoid sending too many of the same messages when the device comes back online or becomes active.

			Note that there is no guarantee of the order in which messages get sent.

			Note: A maximum of 4 different collapse keys is allowed at any given time. This means a FCM connection server can simultaneously store 4 different send-to-sync messages per client app. If you exceed this number, there is no guarantee which 4 collapse keys the FCM connection server will keep.
		 */
		'collapse_key',
		/*
			priority: String
			Sets the priority of the message. Valid values are "normal" and "high." On iOS, these correspond to APNs priorities 5 and 10.

			By default, notification messages are sent with high priority, and data messages are sent with normal priority. Normal priority optimizes the client app's battery consumption and should be used unless immediate delivery is required. For messages with normal priority, the app may receive the message with unspecified delay.

			When a message is sent with high priority, it is sent immediately, and the app can wake a sleeping device and open a network connection to your server.

			more info: https://firebase.google.com/docs/cloud-messaging/concept-options#setting-the-priority-of-a-message
		 */
		'priority',
		/*
			content_available: Boolean
			On iOS, use this field to represent content-available in the APNs payload. When a notification or message is sent and this is set to true, an inactive client app is awoken. On Android, data messages wake the app by default. On Chrome, currently not supported.
		 */
		'ttl',
		/*
			restricted_package_name: string
			This parameter specifies the package name of the application where the registration tokens must match in order to receive the message.
		 */
		'restricted_package_name',
		/*
			dry_run: boolean
			This parameter, when set to true, allows developers to test a request without actually sending a message.
			The default value is false.
		 */
		'dry_run'
	);

	public function __construct() {
		$factory = (new Factory)->withServiceAccount(self::AUTH_JSON_FILE_PATH);
		$this->messaging = $factory->createMessaging();
	}

	/**
	 * Parses the payload
	 * @param  array $aPayload can only have the keys 'data' and 'notification'
	 * more info: https://firebase.google.com/docs/cloud-messaging/http-server-ref#downstream-http-messages-json
	 * @return array with only data an notification
	 */
	private static function _parsePayload($aPayload) 
	{
		if (
			(! is_array($aPayload) || 
			 
			 ($aPayload) === 0)
			|| (! (array_key_exists("data", $aPayload) || array_key_exists("notification", $aPayload)))
		) {
			throw new FCMPushNotificationException("Invalid Payload");
		}
		$aReturn = array();
		// Payload 'notification' : https://firebase.google.com/docs/reference/fcm/rest/v1/projects.messages#notification
		if (array_key_exists("notification", $aPayload) && is_array($aPayload['notification']) && count($aPayload['notification'])) {
			$aReturn['notification'] = $aPayload['notification'];
		}
		// Payload 'data'
		if (array_key_exists("data", $aPayload) && is_array($aPayload['data']) && count($aPayload['data'])) {
			$aReturn['data'] = [];
			//	The key should not be a reserved word ("from" or any word starting with "google" or "gcm"). 
			foreach($aPayload['data'] as $key => $value) {
				if ($key == 'from' || str_starts_with($key, 'google') || str_starts_with($key, 'gcm')) {
					throw new FCMPushNotificationException('Invalid Payload: "data" key "'.$key.'" is a reserved keyword');
				}
				if (in_array($key, self::$Options)) {
					throw new FCMPushNotificationException('Invalid Payload: "data" key "'.$key.'" is a reserved keyword for Options');
				}
				$aReturn['data'][$key] = "{$value}";
			}
		}
		
		return $aReturn;
	}

	/**
	 * Parses the options
	 * @param  array $aOptions
	 * @return array with only keys that can be used (self::$Options)
	 * more info: https://firebase.google.com/docs/cloud-messaging/http-server-ref#downstream-http-messages-json
	 */
	private static function _parseOptions($aOptions) 
	{
		if (! is_array($aOptions) || ! count($aOptions)) {
			return array();
		}
		$arrOptions = array();
		foreach ($aOptions as $key => $value) {
			if (in_array($key, self::$Options)) {
				$arrOptions[$key] = $key == 'ttl' ? "{$value}s" : "{$value}";
				continue;
			} 
			throw new FCMPushNotificationException('Invalid Options: unkown key "'.$key.'"');
		}
		return $arrOptions;
	}

	/**
	 * private send method
	 * @param  array 	$message send data
	 * @return array 	server response data
	 */
	private function _send($message)
	{
		try {
			$this->messaging->send($message);
		} 
		catch (MessagingErrors\NotFound $ex) {
			throw new FCMPushNotificationException('The target device could not be found.');
		} 
		catch (MessagingErrors\InvalidMessage $ex) {
			throw new FCMPushNotificationException("The given message is malformatted : {$ex->getMessage()}");
		} 
		catch (MessagingErrors\ServerUnavailable $ex) {
			throw new FCMPushNotificationException('The FCM servers are currently unavailable.');
		} 
		catch (MessagingErrors\ServerError $ex) {
			throw new FCMPushNotificationException('The FCM servers are down.');
		} 
		catch (MessagingException $ex) {
			throw new FCMPushNotificationException("Unable to send message: {$ex->getMessage()}");
		}
		return json_encode($message->jsonSerialize());
	}

	/**
	 * Condition based push notification
	 * @param  string $sCondition topic condition
	 * @param  [type] $aPayload   [description]
	 * @param  [type] $aOptions   [description]
	 * @return [type]             [description]
	 */
	public function sendToCondition($sCondition, $aPayload, $aOptions = null) 
	{
		if (! is_string($sCondition)) {
			throw new FCMPushNotificationException("Invalid Condition");
		}
		try {
			$aData					= self::_parsePayload($aPayload);
			$aOptions				= self::_parseOptions($aOptions);
			$message = CloudMessage::withTarget('condition', $sCondition);
			$message = $message->withAndroidConfig(AndroidConfig::fromArray(array_merge($aOptions, $aData)));
			return $this->_send($message);
		}
		catch (FCMPushNotificationException $ex) {
			throw new Exception($ex->getMessage());
		}
	}

	/**
	 * Send push notification to single device
	 * @param  string	$sRegistrationToken The registation token comes from the client FCM SDKs
	 * @param  array	$aPayload           see https://firebase.google.com/docs/cloud-messaging/http-server-ref#downstream-http-messages-json
	 * @param  array	$aOptions           see https://firebase.google.com/docs/cloud-messaging/http-server-ref#downstream-http-messages-json
	 * @return array 	server response data
	 */
	public function sendToDevice($sRegistrationToken, $aPayload, $aOptions = null)
	{
		if (! is_string($sRegistrationToken)) {
			throw new FCMPushNotificationException("Invalid RegistrationToken");
		}
		try {
			$aData			= self::_parsePayload($aPayload);
			$aOptions		= self::_parseOptions($aOptions);
			$message = CloudMessage::withTarget('token', $sRegistrationToken);
			$message = $message->withAndroidConfig(AndroidConfig::fromArray(array_merge($aOptions, $aData)));

			return $this->_send($message);
		}
		catch (FCMPushNotificationException $ex) {
			throw new Exception($ex->getMessage());
		}
	}

	/**
	 * send to topic
	 * @param  string	 $sTopic  			topic which devices can subscribe to
	 * @param  array	$aPayload           see https://firebase.google.com/docs/cloud-messaging/http-server-ref#downstream-http-messages-json
	 * @param  array	$aOptions           see https://firebase.google.com/docs/cloud-messaging/http-server-ref#downstream-http-messages-json
	 * @return array 	server response data
	 */
	public function sendToTopic($sTopic, $aPayload, $aOptions = null)
	{
		if (! is_string($sTopic)) {
			throw new FCMPushNotificationException("Invalid Topic");
		}
		try {
			$aData			= self::_parsePayload($aPayload);
			$aOptions		= self::_parseOptions($aOptions);
			$message = CloudMessage::withTarget('topic', $sTopic);
			$message = $message->withAndroidConfig(AndroidConfig::fromArray(array_merge($aOptions, $aData)));

			return $this->_send($message);
		}
		catch (FCMPushNotificationException $ex) {
			throw new Exception($ex->getMessage());
		}
	}		
}
/**
 * Exception class for FCMPushNotification
 */
class FCMPushNotificationException extends \Exception {};