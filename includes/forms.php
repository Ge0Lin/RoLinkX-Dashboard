<?php
/*
*   RoLinkX Dashboard v0.7
*   Copyright (C) 2021 by Razvan Marin YO6NAM / www.xpander.ro
*
*   This program is free software; you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation; either version 2 of the License, or
*   (at your option) any later version.
*
*   This program is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this program; if not, write to the Free Software
*   Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
*/

/*
* Forms module
* Note : Some code borrowed from https://github.com/RaspAP/raspap-webgui
*/


if (isset($_GET['scan'])) echo scanWifi(1);

/* Wi-Fi form */
function getSSIDs() {
	$storedSSID = null;
	$storedPwds = null;

	preg_match_all('/ssid="(\S+)"/', file_get_contents('/etc/wpa_supplicant/wpa_supplicant.conf'), $resultSSID);
	if (empty($resultSSID)) return false;
	foreach ($resultSSID[1] as $key => $ap) {
		if ($key <= 2) {
			  $storedSSID[] = $ap;
		  }
	}

	preg_match_all('/psk="(\S+)"/', file_get_contents('/etc/wpa_supplicant/wpa_supplicant.conf'), $resultPWDS);
	if (empty($resultPWDS)) return false;
	foreach ($resultPWDS[1] as $key => $pw) {
		if ($key <= 2) {
			  $storedPwds[] = $pw;
		  }
	}

	return array($storedSSID, $storedPwds);
}

function scanWifi($ext = 0) {
	$apList = NULL;
	exec('sudo wpa_cli -i wlan0 scan');
	exec('sudo wpa_cli -i wlan0 scan_results', $reply);
	if (empty($reply)) return;
	array_shift($reply);

	foreach ($reply as $network) {
		$arrNetwork = preg_split("/[\t]+/", $network);
		if (!isset($arrNetwork[4])) continue;
		$ssid = trim($arrNetwork[4]);
		if (empty($ssid) || preg_match('[\x00-\x1f\x7f\'\`\´\"]', $ssid)) {
				continue;
		  }
		  $networks[$ssid]['ssid'] = $ssid;
		$networks[$ssid] = array(
				'rssi' => $arrNetwork[2],
					 'protocol' => authType($arrNetwork[3]),
					 'channel' => freqToChan($arrNetwork[1])
				);
	}

	if (!empty($networks)) {
		$cnt = 1;
		if ($ext != 1) {
			$apList = '<div class="accordion mb-3" id="wifiNetworks">
	<div class="accordion-item">
	 <h2 class="accordion-header" id="heading">
		<button class="bg-info text-white accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#availableNetworks" aria-expanded="false" aria-controls="availableNetworks">Available Networks</button>
	 </h2>
	 <div id="availableNetworks" class="accordion-collapse collapse" aria-labelledby="heading" data-bs-parent="#wifiNetworks">
		<div id="updateList" class="accordion-body">';
		 }
			$apList .= '<table class="table"><thead><tr>
			<th scope="col">#</th>
			<th scope="col">SSID</th>
			<th scope="col">RSSI</th>
			<th scope="col">Auth</th>
			<th scope="col">Ch.</th>
			</tr></thead>
			<tbody>';

		foreach ($networks as $name => $data) {
			if ($data['rssi'] >= -80) {
				$lvlQuality = 'class="alert-success"';
			} elseif ($data['rssi'] >= -90) {
				$lvlQuality = 'class="alert-warning"';
			} else {
				$lvlQuality = 'class="alert-light"';
			}

			$apList .= '<tr '. $lvlQuality .'><th scope="row">'. $cnt .'</th>
						<td>'. $name .'</td>
						<td>'. $data['rssi'] .'</td>
						<td>'. $data['protocol'] .'</td>
						<td>'. $data['channel'] .'</td>
						</tr>';
			++$cnt;
		}
		$apList .= '</tbody></table>';
		if ($ext != 1) {
			$apList .= '</div></div></div></div>';
		}
	}
 return $apList;
}

function authType($type) {
	 $options = array();
	 preg_match_all('/\[([^\]]+)\]/s', $type, $matches);

	 foreach ($matches[1] as $match) {
		  if (preg_match('/^(WPA\d?)/', $match, $protocol_match)) {
				$protocol = $protocol_match[1];
				$matchArr = explode('-', $match);
				$options[] = htmlspecialchars($protocol, ENT_QUOTES);
		  }
	 }

	 if (count($options) === 0) {
		  return 'Open';
	 } else {
		  return implode(' / ', $options);
	 }
}

function freqToChan($freq) {
	 if ($freq >= 2412 && $freq <= 2484) {
		  $channel = ($freq - 2407)/5;
	 } elseif ($freq >= 4915 && $freq <= 4980) {
		  $channel = ($freq - 4910)/5 + 182;
	 } elseif ($freq >= 5035 && $freq <= 5865) {
		  $channel = ($freq - 5030)/5 + 6;
	 } else {
		  $channel = -1;
	 }
	 if ($channel >= 1 && $channel <= 196) {
		  return $channel;
	 } else {
		  return 'Invalid Channel';
	 }
}

function wifiForm() {
	$ssidList	= getSSIDs();
	$apsList	= scanWifi();

	$ssidNet_1	= (empty($ssidList[0][0])) ? 'Your SSID' : $ssidList[0][0] . ' (saved)';
	$ssidNet_2	= (empty($ssidList[0][1])) ? 'Your SSID' : $ssidList[0][1] . ' (saved)';
	$ssidNet_3	= (empty($ssidList[0][2])) ? 'Your SSID' : $ssidList[0][2] . ' (saved)';
	$pwdNet_1	= (empty($ssidList[1][0])) ? 'Your key' : preg_replace('/(?!^.?).(?!.{0}$)/', '*',  $ssidList[1][0]);
	$pwdNet_2	= (empty($ssidList[1][1])) ? 'Your key' : preg_replace('/(?!^.?).(?!.{0}$)/', '*',  $ssidList[1][1]);
	$pwdNet_3	= (empty($ssidList[1][2])) ? 'Your key' : preg_replace('/(?!^.?).(?!.{0}$)/', '*',  $ssidList[1][2]);

	$wifiForm = '<h2 class="mt-2 alert alert-info fw-bold">Configurare interfață Wi-Fi</h2>';
	$wifiForm .= '<div id="wifiScanner">' . $apsList . '</div>';
	$wifiForm .= '<div class="card">
		<div class="card-header">Add / Edit networks</div>
		<div class="card-body">
		<div class="input-group input-group-sm mb-3">
		  <span class="input-group-text" style="width: 9rem;">[1] Name (SSID)</span>
		  <input id="wlan_network_1" type="text" class="form-control" placeholder="'. $ssidNet_1 .'" aria-label="Network Name" aria-describedby="inputGroup-sizing-sm">
		</div>
		<div class="input-group input-group-sm mb-3">
		  <span class="input-group-text" style="width: 9rem;">[1] Key (Password)</span>
		  <input id="wlan_authkey_1" type="text" class="form-control" placeholder="'. $pwdNet_1 .'" aria-label="Network key" aria-describedby="inputGroup-sizing-sm">
		</div>
	<hr/>
		<div class="input-group input-group-sm mb-3">
		  <span class="input-group-text" style="width: 9rem;">[2] Name (SSID)</span>
		  <input id="wlan_network_2" type="text" class="form-control" placeholder="'. $ssidNet_2 .'" aria-label="Network Name" aria-describedby="inputGroup-sizing-sm">
		</div>
		<div class="input-group input-group-sm mb-3">
		  <span class="input-group-text" style="width: 9rem;">[2] Key (Password)</span>
		  <input id="wlan_authkey_2" type="text" class="form-control" placeholder="'. $pwdNet_2 .'" aria-label="Network key" aria-describedby="inputGroup-sizing-sm">
		</div>
	<hr/>
		<div class="input-group input-group-sm mb-3">
		  <span class="input-group-text" style="width: 9rem;">[3] Name (SSID)</span>
		  <input id="wlan_network_3" type="text" class="form-control" placeholder="'. $ssidNet_3 .'" aria-label="Network Name" aria-describedby="inputGroup-sizing-sm">
		</div>
		<div class="input-group input-group-sm mb-3">
		  <span class="input-group-text" style="width: 9rem;">[3] Key (Password)</span>
		  <input id="wlan_authkey_3" type="text" class="form-control" placeholder="'. $pwdNet_3 .'" aria-label="Network key" aria-describedby="inputGroup-sizing-sm">
		</div>
		<div class="d-flex justify-content-center mt-4">
			<button id="savewifi" class="m-2 btn btn-danger btn-lg">Salvează</button>
			<button id="rewifi" class="m-2 btn btn-info btn-lg">Restart Wi-Fi</button>
		</div>
		</div>
	</div>' . PHP_EOL;

	$wifiForm .= '<script>
	var auto_refresh = setInterval( function () {
		$("#updateList").load("includes/forms.php?scan");
	}, 6000);
	</script>' . PHP_EOL;
	return $wifiForm;
}

/* SVXLink form */
function svxForm() {
	$profileOption = NULL;

	/* Get current variables */
	$cfgFileData = file_get_contents('/opt/rolink/conf/rolink.conf');

	preg_match('/(HOST=)(\S+)/', $cfgFileData, $varReflector);
	preg_match('/(PORT=)(\d+)/', $cfgFileData, $varPort);
	preg_match('/(CALLSIGN=")(\S+)"/', $cfgFileData, $varCallSign);
	preg_match('/(AUTH_KEY=)"(\S+)"/', $cfgFileData, $varAuthKey);
	preg_match('/(CALLSIGN=)(\w\S+)/', $cfgFileData, $varBeacon);

	/* Short / Long Intervals */
	preg_match('/(SHORT_IDENT_INTERVAL=)(\d+)/', $cfgFileData, $varShortIdent);
	preg_match('/(LONG_IDENT_INTERVAL=)(\d+)/', $cfgFileData, $varLongIdent);

	/* Opus codec bitrate */
	preg_match('/(OPUS_ENC_BITRATE=)(\d+)/', $cfgFileData, $varCodecBitRate);

	$reflectorValue		= (isset($varReflector[2])) ? 'value=' . $varReflector[2] : '';
	$portValue			= (isset($varPort[2])) ? 'value=' . $varPort[2] : '';
	$callSignValue		= (isset($varCallSign[2])) ? 'value=' . $varCallSign[2] : '';
	$authKeyValue		= (isset($varAuthKey[2])) ? 'value=' . $varAuthKey[2] : '';
	$beaconValue		= (isset($varBeacon[2])) ? 'value=' . $varBeacon[2] : '';
	$bitrateValue		= (isset($varCodecBitRate[2])) ? 'value=' . $varCodecBitRate[2] : '';

	/* Profiles section */
	$profilesPath	= dirname(__FILE__) . '/../profiles/';
	$proFiles		= array_slice(scandir($profilesPath), 2);
	if (!empty($proFiles)) {
		$profileOption	= '<hr />
			<div class="input-group input-group-sm mb-3">
			  <label class="input-group-text" for="svx_spn" style="width: 8rem;">Select profile</label>
			  <select id="svx_spn" class="form-select">
				<option value="" selected disabled>Select a profile</option>';
		foreach ($proFiles as $profile) {
			$profileOption .= '<option value="'. $profile .'">'. basename($profile, '.json') .'</option>';
		}
		$profileOption .= '</select>
		<button id="delsvxprofile" class="btn btn-outline-danger" type="button">Delete</button>
		</div>';
	}

	$svxForm = '<h2 class="mt-2 alert alert-warning fw-bold">Configurare SVXLink</h2>
		<div class="input-group input-group-sm mb-3">
		  <span class="input-group-text bg-info text-white" style="width: 8rem;">Profile Name</span>
		  <input id="svx_prn" type="text" class="form-control" placeholder="Name your profile" aria-label="Profile name" aria-describedby="inputGroup-sizing-sm">
		</div>
		<div class="input-group input-group-sm mb-3">
		  <span class="input-group-text" style="width: 8rem;">Reflector (IP/DNS)</span>
		  <input id="svx_ref" type="text" class="form-control" placeholder="svx.439100.ro" aria-label="Adresa server" aria-describedby="inputGroup-sizing-sm" '. $reflectorValue .'>
		</div>
		<div class="input-group input-group-sm mb-3">
		  <span class="input-group-text" style="width: 8rem;">Port</span>
		  <input id="svx_prt" type="text" class="form-control" placeholder="5301" aria-label="Port" aria-describedby="inputGroup-sizing-sm" '. $portValue .'>
		</div>
		<div class="input-group input-group-sm mb-3">
		  <span class="input-group-text" style="width: 8rem;">Callsign</span>
		  <input id="svx_cal" type="text" class="form-control" placeholder="YO1XYZ" aria-label="Call sign" aria-describedby="inputGroup-sizing-sm" '. $callSignValue .'>
		</div>
		<div class="input-group input-group-sm mb-3">
		  <span class="input-group-text" style="width: 8rem;">Auth Key</span>
		  <input id="svx_key" type="text" class="form-control" placeholder="nod_portabil" aria-label="Adresa server" aria-describedby="inputGroup-sizing-sm" '. $authKeyValue .'>
		</div>
		<div class="input-group input-group-sm mb-3">
		  <span class="input-group-text" style="width: 8rem;">Callsign (baliză)</span>
		  <input id="svx_clb" type="text" class="form-control" placeholder="YO1XYZ" aria-label="Call sign" aria-describedby="inputGroup-sizing-sm" '. $beaconValue .'>
		</div>
		<div class="input-group input-group-sm mb-3">
		  <label class="input-group-text" for="svx_sid" style="width: 8rem;">Short Ident</label>
		  <select id="svx_sid" class="form-select">
			 <option value="0">Disabled</option>' . PHP_EOL;
		/* Generate 5 minutes intervals up to 60 & identify stored value on file */
		for ($sid=5; $sid<=120; $sid+=5) {
			$sel = ($sid == $varShortIdent[2]) ? ' selected' : NULL;
			$svxForm .= '<option value="'. $sid .'"' . $sel .'>'. $sid .' minute</option>' . PHP_EOL;
		}
	$svxForm .= '</select>
		</div>
		<div class="input-group input-group-sm mb-3">
		  <label class="input-group-text" for="svx_lid" style="width: 8rem;">Long Ident</label>
		  <select id="svx_lid" class="form-select">
			 <option value="0">Disabled</option>' . PHP_EOL;
		/* Generate 5 minutes intervals up to 60 & identify stored value on file */
		for ($lid=5; $lid<=300; $lid+=5) {
			$sel = ($lid == $varLongIdent[2]) ? ' selected' : NULL;
			$svxForm .= '<option value="'. $lid .'"' . $sel .'>'. $lid .' minute</option>' . PHP_EOL;
		}

		$svxForm .= '</select>
		</div>
		<div class="input-group input-group-sm mb-3">
		  <label class="input-group-text" for="svx_cbr" style="width: 8rem;">Codec Bitrate</label>
		  <select id="svx_cbr" class="form-select">' . PHP_EOL;
		if (isset($varCodecBitRate[2])) {
			/* Generate codec bitrates */
			for ($cbr=8000; $cbr<=32000; $cbr+=2000) {
				$sel = ($cbr == $varCodecBitRate[2]) ? ' selected' : NULL;
				$cbrSuffix = ($cbr == 20000) ? '(default)' : NULL;
				$svxForm .= '<option value="'. $cbr .'"' . $sel .'>'. $cbr / 1000 .' kb/s '. $cbrSuffix .'</option>' . PHP_EOL;
			}
		} else {
			$svxForm .= '<option value="" disabled selected>Unavailable</option>';
		}
	$svxForm .= '
		  </select>
		</div>';
		$svxForm .= $profileOption;
		$svxForm .= '
		<div class="d-flex justify-content-center mt-4">
			<button id="savesvxcfg" type="submit" class="btn btn-danger btn-lg m-2">Salvează</button>
			</div>' . PHP_EOL;
	return $svxForm;
}

/* SA818 radio */
function sa818Form() {
	$config = include 'config.php';
	$ctcssVars = array(
		"1" => "67.0", "2" => "71.9", "3" => "74.4", "4" => "77.0", "5" => "79.7",
		"6" => "82.5", "7" => "85.4", "8" => "88.5", "9" => "91.5", "10" => "94.8",
		"11" => "97.4", "12" => "100.0", "13" => "103.5", "14" => "107.2",
		"15" => "110.9", "16" => "114.8", "17" => "118.8", "18" => "123",
		"19" => "127.3", "20" => "131.8", "21" => "136.5", "22" => "141.3",
		"23" => "146.2", "24" => "151.4", "25" => "156.7", "26" => "162.2",
		"27" => "167.9", "28" => "173.8", "29" => "179.9", "30" => "186.2",
		"31" => "192.8", "32" => "203.5", "33" => "210.7", "34" => "218.1",
		"35" => "225.7", "36" => "233.6", "37" => "241.8", "38" => "250.3"
		);
	$sa818Form = '<h2 class="mt-2 alert alert-danger fw-bold">Configurare modul SA818</h2>
		<div class="form-floating mb-1">
			<select id="sa_grp" class="form-select" aria-label="Frecvenţă (MHz)">
				<option selected disabled>Selectează o valoare</option>';
					/* Generate frequency list (2m & 70cm, no APRS or repeater inputs/outputs) */
					for ($f=144.000; $f<=145.5875; $f+=0.0125) {
						if (sprintf("%0.3f", $f) == '144.800') continue;
						$freqFmt = str_replace('000', '00', sprintf("%0.4f", $f));
						$freqFmt = (strlen($freqFmt) == 8) ? str_replace(',0','', preg_replace('/\d$/', ',$0', $freqFmt)) : $freqFmt;
						$sa818Form .= '<option value="'. sprintf("%0.4f", $f) .'">'. $freqFmt .'</option>' . PHP_EOL;
					}
					for ($f=431.900; $f<=436.925; $f+=0.025) {
						$sa818Form .= '<option value="'. sprintf("%0.4f", $f) .'">'. sprintf("%0.3f",$f) .'</option>' . PHP_EOL;
					}
	$sa818Form .= '</select>
			<label for="sa_grp">Frecvenţă (MHz)</label>
		</div>
		<div class="form-floating mb-1">
			<select id="sa_dev" class="form-select" aria-label="Deviaţie (kHz)">
				<option selected disabled>Selectează o valoare</option>
				<option value="0">12.5</option>
				<option value="1" selected>25</option>
			</select>
			<label for="sa_dev">Deviaţie (kHz)</label>
		</div>
		<div class="form-floating mb-1">
			<select id="sa_tpl" class="form-select" aria-label="CTCSS (Hz)">
				<option selected disabled>Selectează o valoare</option>';
					/* Build CTCSS selects */
					foreach ($ctcssVars as $key => $val) {
						$selected = ($key == 13) ? ' selected' : '';
						$sa818Form .= '<option value="' . sprintf("%04d", $key) . '"'. $selected .'>' . $val . '</option>' . PHP_EOL;
					}
			$sa818Form .= '</select>
			<label for="sa_tpl">CTCSS (Hz)</label>
		</div>
		<div class="form-floating mb-1">
			<select id="sa_sql" class="form-select" aria-label="Squelch">
				<option selected disabled>Selectează o valoare</option>';
					/* Generate squelch values */
					for ($sq=1; $sq<=8; $sq+=1) {
						$selected = ($sq == 5) ? ' selected' : '';
						$sa818Form .= '<option value="'. $sq . '"'. $selected .'>'. $sq .'</option>' . PHP_EOL;
					}
	$sa818Form .= '</select>
			<label for="sa_sql">Squelch</label>
		</div>
		<div class="form-floating mb-1">
			<select id="sa_vol" class="form-select" aria-label="Volum">
				<option value="">Default</option>';
					/* Generate volume values */
					for ($vol=1; $vol<=8; $vol+=1) {
						$selected = ($vol == 2) ? ' selected' : '';
						$sa818Form .= '<option value="'. $vol . '"'. $selected .'>'. $vol .'</option>' . PHP_EOL;
					}
	$sa818Form .= '</select>
			<label for="sa_vol">Volum</label>
		</div>
		<div class="form-floating mb-1">
			<select id="sa_flt" class="form-select" aria-label="Filtre">
				<option value="">Default</option>
				<option value="0,0,0" selected>Bypass All</option>
				<option value="1,0,0">Enable Pre/De-Emphasis</option>
				<option value="0,1,0">Enable High Pass</option>
				<option value="0,0,1">Enable Low Pass</option>
				<option value="0,1,1">Enable Low Pass & High Pass</option>
				<option value="1,1,0">Enable Pre/De-Emphasis & High Pass</option>
				<option value="1,0,1">Enable Pre/De-Emphasis & Low Pass</option>
				<option value="1,1,1">Enable All</option>
			</select>
			<label for="sa_flt">Filtre</label>
		</div>
		<div class="d-flex justify-content-center mt-4">
			<button id="programm" type="button" class="btn btn-danger btn-lg">Programează!</button>
		</div>' . PHP_EOL;
	$sa818Form .= '<div class="mt-3 alert alert-info" role="alert">Note : Using <b>ttyS'. $config['cfgTty'] .'</b> and <b>GPIO'. $config['cfgPttPin'] .'</b> for PTT. You can change these using the config page.</div>' . PHP_EOL;
	return $sa818Form;
}

/* Logs */
function logsForm() {
	$logData = '<h2 class="mt-2 alert alert-dark fw-bold">Logs</h2>';
	$logData .= '<div class="container">
		<div class="row justify-content-center">
		  <div class="col-lg-12">
			 <div class="card bg-light shadow border-0">
				<div class="card-header bg-white">
				 <img id="new_log_line" src="assets/img/new.svg" alt="received" style="display:none;">
				 <div id="log_selector">
					<select id="log">
						<option value="" disabled>-Log file-</option>
						<option value="1" selected>Syslog</option>
						<option value="2">RoLink</option>
					</select>
				</div>
				</div>
				<div class="card-body px-lg-3 py-lg-2 scrolog">
					<div class="small" id="log_data"></div>
			</div>
			 </div>
		</div>
	</div>
</div>';
	return $logData;
}

/* Config */
function cfgForm() {
	$config		= include 'config.php';
	$pinsArray = array(2, 3, 7, 10, 18, 19);
	$ttysArray = array(1, 2, 3);
	$statusPageItems = array(
		'cfgHostname' => 'Hostname',
		'cfgUptime' => 'Uptime',
		'cfgCpuStats' => 'CPU Stats',
		'cfgNetworking' => 'Networking',
		'cfgSsid' => 'Wi-Fi Info',
		'cfgPublicIp' => 'External IP',
		'cfgSvxStatus' => 'SVXLink Status',
		'cfgRefNodes' => 'Connected nodes',
		'cfgCallsign' => 'Callsign'
	);
	$configData = '<h2 class="mt-2 alert alert-warning fw-bold">Configuration</h2>
	<div class="card m-1">
	<h4 class="m-2">Serial & GPIO</h4>
	<div class="form-floating m-2">
		<select id="cfgPttPin" class="form-select" aria-label="GPIO Pin (PTT)">' . PHP_EOL;
	foreach ($pinsArray as $pin) {
		$configData .= '<option value="'. $pin . '"' . ($pin == $config['cfgPttPin'] ? ' selected' : '') . '>'. $pin .'</option>' . PHP_EOL;
	}
	$configData .= '</select>
	<label for="cfgPttPin">GPIO Pin (PTT)</label>
	</div>' . PHP_EOL;
	$configData .= '<div class="form-floating m-2">
			<select id="cfgTty" class="form-select" aria-label="Serial Port (ttyS)">' . PHP_EOL;
	foreach ($ttysArray as $tty) {
		$configData .= '<option value="'. $tty . '"' . ($tty == $config['cfgTty'] ? ' selected' : '') . '>'. $tty .'</option>' . PHP_EOL;
	}
	$configData .= '</select>
	<label for="cfgTty">Serial Port (ttyS)</label>
	</div>
	<h4 class="m-2">Status page content</h4>' . PHP_EOL;
	foreach ($statusPageItems as $cfgName => $cfgTitle) {
		$configData .= '<div class="form-check m-2">
			<input class="form-check-input" type="checkbox" id="'. $cfgName .'"'. ($config[$cfgName] == 'true' ? ' checked' : '') .'>
			<label class="form-check-label" for="'. $cfgName .'">'. $cfgTitle .'</label>
		</div>' . PHP_EOL;
	}
	$configData .= '</div>
	<div class="d-flex justify-content-center mt-4">
		<button id="cfgSave" type="button" class="btn btn-danger btn-lg">Salvează</button>
	</div>' . PHP_EOL;
	return $configData;
}
