<?php

require_once("mysql_class/Db.class.php");
require("settings.inc.php");

//On Boot, send out an hello world
	sendUpdate("Starting Aruba " . ORGANISATION . " Logger");

//Start the listener

#!/usr/bin/php -q
do {
	$input = fgets(STDIN);
	
	parseAruba($input);
}
while ( !feof( STDIN ) );




function parseAruba($message) {
	$explodedmsg = explode(" ", $message);

	if(!empty($explodedmsg[5])) {
		switch($explodedmsg[5]) {
			case "recv_sta_online:":
				//Station online
				$ssid = returnSSID($message);
				$macaddr = getBetween($message,"mac-"," bssid");

				stateChangeMac($macaddr,$ssid,"1"," joined ");
				break;
			case "recv_sta_offline:":
				//Station offline
				$ssid = returnSSID($message);
				$macaddr = getBetween($message,"mac-"," bssid");

				stateChangeMac($macaddr,$ssid,null," left ");
				break;
			case "recv_sta_ageout_offline:":
				//Station offline due to timeout
				$ssid = returnSSID($message);
				$macaddr = getBetween($message,"mac-"," bssid");

				stateChangeMac($macaddr,$ssid,null," timed out on ");
				break;
			case "recv_sta_update:":
				//We currently dont do anything with updates
				break;
			case "recv_stm_sta_update:":
				//We currently dont do anything with updates
				break;
			case "hostname":
				//Station announced its hostname
				$hostname = $explodedmsg[8];
				$macaddr = getBetween($message,"client "," hostname");

				hostnameChange($macaddr,$hostname);
				break;
			case "name":
				//Station announced its hostname
				$hostname = $explodedmsg[8];
				$macaddr = getBetween($message,"client "," name");

				hostnameChange($macaddr,$hostname);
				break;
			default:
				if(DEBUG == "1") { sendUpdate("DEBUG : trigger is : " . $explodedmsg[5]); }
		}
	}
}

function getBetween($content,$start,$end){
    $r = explode($start, $content);
    if (isset($r[1])){
        $r = explode($end, $r[1]);
        return $r[0];
    }
    return '';
}

function returnSSID($message) {
	$essid = getBetween($message,"essid-",".");
	if(!empty($essid)) {
		$ssid = $essid;
	} else {
		$ssid = getBetween($message," ssid-",".");
	}

	return $ssid;
}

function stateChangeMac($macaddr,$ssid,$state,$message) {
	$db = new Db();
	if(!empty($ssid)) {
		//Get privacy settings for SSID
		$db->bind("ssid",$ssid);
		$db->query("INSERT INTO ssids (ssid) VALUES(:ssid) ON DUPLICATE KEY UPDATE ssid = VALUES(ssid)");

		$db->bind("ssid",$ssid);
		$log = $db->row("SELECT ssid,log,announce FROM ssids WHERE ssid = :ssid");
	
		if(!is_null($log["log"])) {
			$db->bind("macaddr",$macaddr);
			$db->bind("online",$state);
			$db->query("INSERT INTO macaddresses (macaddress,online) VALUES(:macaddr,:online) ON DUPLICATE KEY UPDATE online = VALUES(online)");

			if(!is_null($log["announce"])) {
				$db->bind("macaddr",$macaddr);
				$hostname = $db->single("SELECT hostname FROM macaddresses WHERE macaddress = :macaddr");

				if(!empty($hostname)) {
					$hostname = preg_replace('/\s+/', ' ', trim($hostname));
					sendUpdate($hostname . $message . $ssid);
				} else {
					sendUpdate($macaddr . $message . $ssid);
				}
			}
		}
	}
}

function hostnameChange($macaddr,$hostname) {
	$db = new Db();

	$db->bind("macaddr",$macaddr);
	$db->bind("hostname",$hostname);

	$db->query("INSERT INTO macaddresses (macaddress,hostname) VALUES(:macaddr,:hostname) ON DUPLICATE KEY UPDATE hostname = VALUES(hostname)");

	if(ANNOUNCE_HOSTNAME == "1") { sendUpdate($macaddr . " linked to hostname : " . $hostname); }
}

function sendUpdate($message) {
	$send = array('payload' => '{"channel": "' . SLACK_CHAN . '", "text": "'  . $message . '"}');

	$ch = curl_init(SLACK_URL);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $send);

	$response = curl_exec($ch);
	curl_close($ch);
}
?>
