<?php
global $tg;

if (empty($tg->getCommand()["args"])) {
	$message = "*Available commands:*\n";
	foreach (glob("commands/*.json") as $file) {
		$cmd = json_decode(file_get_contents($file), true);
		$message .= "/" . str_replace("commands/", "", str_replace(".json", "", $file)) . "\n";
	}
	$message .= "\nFor detailed information please enter\n*/help* _[command-name]_";
	$tg->sendMessage($message, null, true);
	return;
}

$availableCommands = [];
foreach (glob("commands/*.json") as $command) {
	$availableCommands[] = str_replace("commands/", "", str_replace(".json", "", $command));
}

if (in_array($tg->getCommand()["args"][0], $availableCommands)) {
	$desctiption = json_decode(file_get_contents("commands/" . $tg->getCommand()["args"][0] . ".json"), true);
	$message = "*MANPAGE*\nPackage: *" . $tg->getCommand()["args"][0] . "*\n" . $desctiption["short"] . "\n\n";
	$message .= $desctiption["long"];
	$tg->sendMessage($message, null, true);
} else {
	$tg->sendMessage("*MANPAGE*\nCommand was not found or doesn't has a manpage", null, true);
}
