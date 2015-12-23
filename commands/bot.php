<?php
global $tg;

if (count($tg->getCommand()["args"]) == 1) {
	switch ($tg->getCommand()["args"][0]) {
		case "info":
			if (file_exists("./.git/refs/heads/master")) {
				$version = "git." . substr(trim(file_get_contents("./.git/refs/heads/master")), 0, 7);
			} else {
				$version = "loc." . dechex(floor(filemtime("./index.php")/10240));
			}
			$tg->sendMessage("Hello " . $tg->getUserName() . ".\nMy name is uTeleBot (Version " . $version . ") and I'm created by [Thomas2500](https://telegram.me/Thomas2500).\nYou can grab my source code from GitHub at:\nhttps://github.com/Thomas2500/uTeleBot", null, true);
			break;
		default:
			break;
	}
}
