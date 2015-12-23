<?php
ob_start();
header("Content-Type: application/json");

if (!file_exists("config.php")) {
	die("Please copy config.php-example to config.php and insert your API-Key into it.");
}

require_once "config.php";
require_once "Telegram.class.php";

if (API_KEY == "") {
	die("Please define your API-Key.");
}

global $tg;
$tg = new Telegram(API_KEY);

$availableCommands = [
	"help", "shorturl", "weather", "bot", "encode", "dns", "mac", "ping", "stats", "traceroute", "permission"
];

$commandAlias = [
	"man" => "help",
	"l" => "shorturl",
	"dig" => "dns",
	"trace" => "traceroute"
];

if ($tg->getText() == "debuginfo") {
	$tg->sendMessage(file_get_contents('php://input'));
}

// Request from Telegram-Webhooks
if (isset($_GET["t"]) && $_GET["t"] == "webhook") {

	// Check if message was a command
	if (strpos($tg->getText(), "/") === 0) {
		$command = strtolower(ltrim($tg->getCommand()["command"], "/"));

		if (isset($commandAlias[$command])) {
			$command = $commandAlias[$command];
		}
		if (in_array($command, $availableCommands)) {
			include_once "commands/" . $command . ".php";
		}
	} else if ($tg->getChatType() == "private") {
		// Special commands/action on private messages?
	} else {
		// Normal message. Add to statistics
	}

	echo $tg->webhookAnswer();

	// Chat statistics
	if (!file_exists("data/stats/st" . $tg->getChatID() . ".json")) {
		file_put_contents("data/stats/st" . $tg->getChatID() . ".json", json_encode([]));
	}

	$stats = json_decode(file_get_contents("data/stats/st" . $tg->getChatID() . ".json"), true);
	if (!isset($stats[$tg->getUserID()])) {
		$stats[$tg->getUserID()] = [ "messages" => 1, "date" => time(), "username" => $tg->getUserName()];
	} else {
		if (is_numeric($stats[$tg->getUserID()])) {
			$stats[$tg->getUserID()] = [ "messages" => $stats[$tg->getUserID()] ];
			unset($stats[$tg->getUserID()."_date"]);
			unset($stats[$tg->getUserID()."_username"]);
		}
		$stats[$tg->getUserID()] = [ "messages" => intval($stats[$tg->getUserID()]["messages"]) + 1, "date" => time(), "username" => $tg->getUserName()];
	}
	file_put_contents("data/stats/st" . $tg->getChatID() . ".json", json_encode($stats));
}

$tg->answer();
