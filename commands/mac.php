<?php
global $tg;

if (!isset($tg->getCommand()["args"][0]) || empty($tg->getCommand()["args"][0])) {
	$tg->sendMessage("Lookup the vendor of a MAC address.");
	return;
}
$prettyMAC = strtoupper(str_replace(".", "", str_replace(":", "", $tg->getCommand()["args"][0])));
if (strlen($prettyMAC) < 6) {
	$tg->sendMessage("MAC address seems to be invalid.");
	return;
}

$url = "http://api.macvendors.com/" . urlencode($prettyMAC);
$handle = curl_init();
curl_setopt($handle, CURLOPT_URL, $url);
curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($handle);
curl_close($handle);

if ($response == false || empty($response)) {
	$tg->sendMessage("Vendor not found");
} else {
	if (strlen($prettyMAC) < 12) {
		$prettyMAC .= str_repeat("0", 12-strlen($prettyMAC));
	}
	$prettyMAC = implode(":", str_split($prettyMAC, 2));
	$tg->sendMessage("The MAC `" . $prettyMAC . "` belongs to:\n*" . $response . "*", null, true, false);
}
