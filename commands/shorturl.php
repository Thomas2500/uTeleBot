<?php
global $tg;

// load saved data
$savedUrls = json_decode(file_get_contents("data/shorturl.json"), true);
if ($savedUrls == false) {
	$savedUrls = [];
}

// no parameters set, print help
if (empty($tg->getCommand()["args"])) {

	$message = "";
	if (!isset($savedUrls[$tg->getChatID()]) || empty($savedUrls[$tg->getChatID()])) {
		$message .= "*No url saved.*\nUse /l _name_ _url_ to add a new one.";
	} else {
		$message .= "*Saved urls:*\n";
		foreach ($savedUrls[$tg->getChatID()] as $name => $url) {
			$message .= "/l *" . $name . "* " . $url . "\n";
		}
	}
	$tg->sendMessage($message, null, true, true);
} else if ($tg->getCommand()["args"][0] == "remove") {

	$message = "";
	if (isset($savedUrls[$tg->getChatID()][$tg->getCommand()["args"][1]])) {
		changeUrl($tg->getChatID(), $tg->getCommand()["args"][1], null);
		$tg->sendMessage("Url removed.");
	} else {
		$tg->sendMessage("Error deleting url. Name unknown.");
	}

} else if (count($tg->getCommand()["args"]) == 2) {

	if (mb_strlen($tg->getCommand()["args"][0]) <= 0) {
		$tg->sendMessage("Error adding url. Please provide a simple name.");
	} else if (filter_var($tg->getCommand()["args"][1], FILTER_VALIDATE_URL) === false) {
		$tg->sendMessage("Error adding url. Please provide a valid url.");
	} else {
		changeUrl($tg->getChatID(), $tg->getCommand()["args"][0], $tg->getCommand()["args"][1]);
		$tg->sendMessage("Url added.\nUse /l " . $tg->getCommand()["args"][0] . " to access it.");
	}

} else if (count($tg->getCommand()["args"]) == 1) {

	if (isset($savedUrls[$tg->getChatID()][$tg->getCommand()["args"][0]])) {
		$tg->sendMessage($savedUrls[$tg->getChatID()][$tg->getCommand()["args"][0]]);
	} else {
		$tg->sendMessage("Url not found. Use `/l " . $tg->getCommand()["args"][0] . " https://example.com` to add it.", null, true);
	}

} else {

	$tg->sendMessage("Command not found.\nPlease use the help at /help shorturl");
}

function changeUrl($chatID, $name, $value = null) {
	$savedUrls = json_decode(file_get_contents("data/shorturl.json"), true);
	if ($savedUrls == false) {
		$savedUrls = [];
	}

	if (!isset($savedUrls[$chatID])) {
		$savedUrls[$chatID] = [];
	}

	if ($value == null) {
		unset($savedUrls[$chatID][$name]);
	} else {
		$savedUrls[$chatID][$name] = $value;
	}
	return file_put_contents("data/shorturl.json", json_encode($savedUrls));
}

/*
	$list = [
	"calendar" => "https://dv.tl/IO",
	"drive" => "https://dv.tl/xL",
	"shorturl" => "To shorten a url, please use https://dv.tl/"
	]
*/



