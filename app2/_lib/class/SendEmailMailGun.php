<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class SendEmailMailGun  {

	const MAILGUN_URL = "https://api.eu.mailgun.net/v3/info.curierdragonstar.ro/messages";
	public static function send($emailFrom, $emailConfirmTo, $emailReplayTo = null, $emailsToSent = [], $emailsCC = [], $emailsBCC = [], $filesAttachements = [], $emailSubject, $emailBody, $emailDebug = false) {

        $client = new \GuzzleHttp\Client();
        $emailReplayTo = $emailReplayTo ?: $emailFrom;
        $payload = [
            "from" => $emailFrom,
            "to" => implode(',', $emailsToSent),
            "subject" => $emailSubject,
            "html" => $emailBody,
            "h:Reply-To" => $emailReplayTo
        ];

        if (!empty($emailsCC)) {
            $payload['cc'] = implode(',', $emailsCC);
        }
        if (!empty($emailsBCC)) {
            $payload['bcc'] = implode(',', $emailsBCC);
        }

        $multipartPayload = self::buildMultipartPayload($payload, $filesAttachements);

        try {
            $response = $client->request('POST', self::MAILGUN_URL, [
                'auth' => ['api', getenv('MAILGUN_API_KEY')],
                'multipart' => $multipartPayload,
            ]);

            if ($response->getStatusCode() >= 400) {
                return [false, "sendEmailMailgun : HTTP Error - " . $response->getBody()->getContents()];
            }
            return [true, "sendEmailMailgun : HTTP Response - " . $response->getBody()->getContents()];
        } catch (GuzzleHttp\Exception\RequestException $e) {
        	return [false, "sendEmailMailgun : RequestException Error - " . $e->getMessage()];
        }
    }

    private static function buildMultipartPayload($payload, $filesAttachements) {
        $multipart = [];

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $multipart[] = [
                        'name' => $key,
                        'contents' => $item
                    ];
                }
            } else {
                $multipart[] = [
                    'name' => $key,
                    'contents' => $value
                ];
            }
        }

        if (!empty($filesAttachements)) {
            foreach ($filesAttachements as $filePath) {
                $fileName = basename($filePath);
                $multipart[] = [
                    'name'     => 'attachment',
                    'contents' => fopen($filePath, 'r'),
                    'filename' => $fileName,
                ];
            }
        }

        return $multipart;
    }
}