<?php
/**
 * NewtifryPro - PHP message push script.
 * for version >= 3 (FCM HTTP V1 protocol)
 */

function iso8601() {
	date_default_timezone_set("UTC");
	$time=time();
	return date("Y-m-d", $time) . 'T' . date("H:i:s", $time) .'.00:00';
}
	

function newtifryProPush(	$pathToServicesJson,
													$deviceIds,  
													$title, 
													$source = NULL, 
													$message = NULL, 
													$priority = 0, 
													$url = NULL, 
													$imageUrl = NULL, 
													$speak = -1, 
													$noCache = false, 
													$state = 0, 
													$notify = -1,
													$tag = NULL) {
	$data = getData($title, $source, $message, $priority, $url, $imageUrl, $speak, $noCache, $state, $notify, $tag);
	$prioString = $priority == 3 ? "high" : "normal";
	if (is_array($deviceIds)) {
		foreach($deviceIds as $id) {
			$ret = sendFCMMessage($data, $pathToServicesJson, $id, $prioString);
			sleep(1);
		}
		return $ret;
	} else {
		return sendFCMMessage($data, $pathToServicesJson, $deviceIds, $prioString);
	}
/*
	$fields = array(  'registration_ids'  => $deviceIds,
										'data'              => $data);
	$toSend = json_encode( $data );
	$totalLength = strlen($toSend);
	if ($totalLength > 2000) {
		$maxSize = 1500;
		$partCount = ceil($totalLength / $maxSize);     
		$hash = hash("md5", $toSend);  
		$part = 0;
		while ($totalLength > 0) {
			//print_r($totalLength);
			$countToSend = ($totalLength >= $maxSize) ? $maxSize :  $totalLength;
			$splitData = array (  "type" => "ntp_message_multi",
														"partcount" => $partCount,
														"hash" => $hash,
														"index" => $part + 1,
														"body" => substr($toSend, $part * $maxSize, $countToSend )
													);
			$fields["data"] = $splitData;
			$ret = newtifryProSend($apikey, $fields); 
			print_r($ret);
			$totalLength -= $countToSend;                                                                                                                                                                                    
			$part++;                                                                                                                                                                                                    
		}
	} else {
		$ret = newtifryProSend($apikey, $fields);
	}
	//echo $ret;
	//Return push response as array
	return $ret;
*/	
}

function getData(	$title, 
					$source, 
					$message, 
					$priority, 
					$url, 
					$imageUrl, 
					$speak, 
					$noCache, 
					$state, 
					$notify,
					$tag) {
	$data = array ( "type" => "ntp_message",
					"timestamp" => iso8601(),
					"NPpriority" => base64_encode(strval($priority)), 
					"title" => base64_encode($title));

	if ($message) {
		$data["message"] = base64_encode($message);
	}
	if ($source) {
		$data["source"] = base64_encode($source);
	}
	if ($url) {
		$data["url"] = base64_encode($url);
	}
	if ($imageUrl) {
		if (is_array($imageUrl)) {
			for ($i = 1; $i < 6; $i++) {
				if ($imageUrl[$i - 1] != null) {
					$data["image" . $i] = base64_encode($imageUrl[$i - 1]);
				}
			}
		} else {
			$data["image"] = base64_encode($imageUrl);
		}
	}

	if ($speak == 0 || $speak == 1) {
		$data["speak"] = base64_encode(strval($speak));
	}
	if ($noCache == true) {
		$data["nocache"] = base64_encode("1");
	}
	if ($state == 1 || $state == 2) {
		$data["state"] = base64_encode(strval($state));
	}
	if ($notify == 0 || $notify == 1) {
		$data["notify"] = base64_encode(strval($notify));
	}      
	if ($tag) {
		$data["tag"] = base64_encode($tag);
	}
	
	return $data;
}
									

///////////////////////////////////////////////////////////
									
function sendFCMMessage($data, $pathToServicesJson, $deviceIdS, $priority = "Normal")
{
    $serviceAccountData = json_decode(file_get_contents($pathToServicesJson), true);
    $jwt = createJWT($serviceAccountData['private_key'], $serviceAccountData['client_email']);
    if (isset($jwt["error"])) {
        return $jwt;
    }
    $accessToken = fetchAccessToken($jwt);
    if (isset($accessToken["error"])) {
        return $accessToken;
    }

//var_dump($data);
    $notificationData = [
//    	"validate_only" => TRUE,
        "message" => [
	       	"token" => $deviceIdS,
            "data" => $data,
            "android" => ["priority" => $priority]
        ]
    ];

    return sendFCM($serviceAccountData['project_id'], $accessToken["token"], $notificationData);
}

function createJWT($privateKey, $serviceAccountEmail)
{
    $timestamp = time();
    $header = base64url_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $payload = base64url_encode(json_encode([
        'iss' => $serviceAccountEmail,
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $timestamp + 3600,
        'iat' => $timestamp
    ]));
    $signature = "";
    $signResult = openssl_sign($header . "." . $payload, $signature, openssl_pkey_get_private($privateKey), OPENSSL_ALGO_SHA256);
    if (!$signResult) {
        $error = openssl_error_string();
        return ["error" => "OpenSSL Signing Error", "error_description" => $error, "where" => "openssl_sign"];
    }
    return $header . "." . $payload . "." . base64url_encode($signature);
}

function fetchAccessToken($jwt)
{
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => http_build_query([
            "grant_type" => "urn:ietf:params:oauth:grant-type:jwt-bearer",
            "assertion" => $jwt
        ])
    ]);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($response["error"])) {
        return ["error" => $response["error"], "error_description" => $response["error_description"], "where" => "Curl Token Request"];
    }
    return ["token" => $response['access_token'], "expiresIn" => $response['expires_in']];
}

function sendFCM($projectId, $accessToken, $notificationData)
{
    $ch = curl_init("https://fcm.googleapis.com/v1/projects/".$projectId."/messages:send");
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ],
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => json_encode($notificationData)
    ]);

    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($response["error"])) {
        return ["error" => $response["error"], "error_description" => $response["error"]["message"], "where" => "Curl FCM Request"];
    }
    return ["message_id" => $response["name"]];
}

function base64url_encode($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
									
									
									
?>
