<?php

##########################################
#   Author: Andre Duclos
#  Created: 2014-08-16
# Modified: 2014-08-23
#
#  Version: 0.1
#    https://github.com/Shyrka973/Netatmo
##########################################

$stationNo = 0;
$moduleNo = -1;
$display = 0;

if (isset($_GET['station'])){
	$stationNo = $_GET['station'];
}

if (isset($_GET['module'])){
	$moduleNo = $_GET['module'];
}

if (isset($_GET['display'])){
	$display = 1;
}

$password = "password";
$username = "user";

$app_id = "id";
$app_secret = "secret";

$token_url = "https://api.netatmo.net/oauth2/token";
$postdata = http_build_query(
        array(
            'grant_type' => "password",
            'client_id' => $app_id,
            'client_secret' => $app_secret,
            'username' => $username,
            'password' => $password
    )
);

$opts = array('http' =>
    array(
        'method'  => 'POST',
        'header'  => 'Content-type: application/x-www-form-urlencoded',
        'content' => $postdata
    )
);

$context  = stream_context_create($opts);
$response = file_get_contents($token_url, false, $context);

$params = json_decode($response, true);
$api_url = "https://api.netatmo.net/api/getuser?access_token=" . $params['access_token'];
$requete = file_get_contents($api_url);

$url_devices = "https://api.netatmo.net/api/devicelist?access_token=" .  $params['access_token'];
$resulat_device = file_get_contents($url_devices);

$json_devices = json_decode($resulat_device,true);

$url_mesures = "";
$err = "";

$stations = $json_devices["body"]["devices"];

if ($display == 1) {
  $msg = "";
  $num = 0;
  $arrstation = array();

  $msg .= '<table border="2">';
  $msg .= '<caption><h2>Stations</h2></caption>';
  $msg .= '<tr>';
  $msg .= '<th>ID</th>';
  $msg .= '<th>Name</th>';
  $msg .= '</tr>';
  
  foreach ($stations as $station) {
  
    $stationname = utf8_decode($station["station_name"]);
    $arrstation[$station["_id"]] = $stationname;

    $msg .= '<tr>';
    $msg .= '<td>' . $num . '</td>';
    $msg .= '<td>' . utf8_decode($station["station_name"]) . '</td>';
    $msg .= '</tr>';
    $num += 1;
  }

  $msg .= '</table>';
  $msg .= '<br/>';
  
  $modules = $json_devices["body"]["modules"];
  $num = 0;

  $msg .= '<table border="2">';
  $msg .= '<caption><h2>Modules</h2></caption>';
  $msg .= '<tr><th>ID</th><th>Name</th><th>Station</th></tr>';

  foreach ($modules as $module) {
  
    $msg .= '<tr>';
    $msg .= '<td>' . $num . '</td>';
#    $msg .= '<td>' . utf8_decode($module["module_name"]) . '</td>';
    $msg .= '<td>' . $module["module_name"] . '</td>';
    $msg .= '<td>' . $arrstation[$module['main_device']]. '</td>';
    $msg .= '</tr>';
    $num += 1;
  }
  $msg .= "</table>";

  $head = '<head>';
  $head .= '<title>Stations & Modules ID</title>';
  $head .= '</head>';
  echo '<html>' . $head . $msg . '</html>';
  exit;
}

if ($stationNo < count($stations)) {
	$station = $stations[$stationNo];
	$stationID = $station["_id"];

  $modules = $json_devices["body"]["modules"];

	if ($moduleNo != -1) {
		if ($moduleNo < count($modules)) {
			$module = $modules[$moduleNo];
			$moduleID = $module["_id"];

			switch($module["type"]) {
			case "NAModule1": # outdoor module
				$type = "Temperature,Humidity";
				break;
			case "NAModule4": # indoor module
				$type = "Temperature,CO2,Humidity";
				break;
			case "NAModule3": # rain gauge moule
				$type = "Rain";
				break;
			case "NAPlug": # thermostat plug
				$type = "Temperature,Sp_Temperature,BoilerOn,BoilerOff";
				break;
			case "NATherm1": # thermostat module
				$type = "Temperature,Sp_Temperature,BoilerOn,BoilerOff";
				break;
		  default:
			$err = "Type of module unknown";
			}
	  } else $err = "Unknown module";
  } else $type = "Temperature,CO2,Humidity,Pressure,Noise";
} else $err = "Unknown station";

if ($err != "") {
  $mesures = '{"status":"ko","error":"'.$err.'"}';
}
else {
  $url_mesures = "https://api.netatmo.net/api/getmeasure?access_token=" . $params['access_token'] .
			           "&scale=max" .
			           "&type=" . $type .
			           "&date_end=last";

  if ($moduleNo == -1) 
    $url_mesures .= "&device_id=" . $stationID;
  else
    $url_mesures .= "&device_id=" . $module["main_device"] .
                    "&module_id=" . $moduleID;

  $mesures = file_get_contents($url_mesures);
}

echo $mesures;
?>