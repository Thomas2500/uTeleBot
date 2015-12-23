<?php
global $tg;

if (preg_match("#^(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$#", $tg->getCommand()["args"][0], $matches) !== 1) {
	$tg->sendMessage("Please use a valid domain name.\nE.g. `/dns google.com`", null, true, false);
} else {

	// Don't allow internal domains
	if (preg_match("#(.*)\.(local|lan|box|priv|dev)$#", $tg->getCommand()["args"][0]) && $tg->getPermission() != Telegram::ADMIN_SUPER) {
		$tg->sendMessage("Internal domains are not allowed.");
		return;
	}

	$command = trim("dig " . $tg->getCommand()["args"][0]);
	if (isset($tg->getCommand()["args"][1])) {
		if (in_array($tg->getCommand()["args"][1], [ "A", "AAAA", "NS", "MX", "SOA", "TXT" ])) {
			$command .= " " . $tg->getCommand()["args"][1];
		}
	}
	$exec = trim(shell_exec($command));
	if (trim($exec) == "" || $exec === false) {
		$exec = "Error. Something went wrong";
	}
	// TODO
	// Output should be prettier
	$tg->sendMessage("`" . $exec . "`", null, false, true);
}
