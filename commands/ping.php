<?php
global $tg;

if (filter_var($tg->getCommand()["args"][0], FILTER_VALIDATE_IP) === false && preg_match("#^(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$#", $tg->getCommand()["args"][0], $matches) !== 1) {
	$tg->sendMessage("Please enter a valid IPv4, IPv6 address or domain name.");
	return;
}

if (filter_var($tg->getCommand()["args"][0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
	if ($tg->getCommand()["args"][0] == "127.0.0.1") {
		$tg->sendPhoto("images/127.jpg");
		return;
	}
	if (!filter_var($tg->getCommand()["args"][0], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)  && $tg->getPermission() != Telegram::ADMIN_SUPER) {
		$tg->sendMessage("Internal ping is not allowed");
		return;
	}
	$tg->sendMessage("```" . trim(shell_exec("ping -c 3 -i 0.2 -W1 -n " . $tg->getCommand()["args"][0])) . "```", null, true, false);
	return;
}

if (filter_var($tg->getCommand()["args"][0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
	if ($tg->getCommand()["args"][0] == "::1") {
		$tg->sendPhoto("images/127.jpg");
		return;
	}
	if (!filter_var($tg->getCommand()["args"][0], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) && $tg->getPermission() != Telegram::ADMIN_SUPER) {
		$tg->sendMessage("Internal ping is not allowed");
		return;
	}
	$tg->sendMessage("```" . trim(shell_exec("ping6 -c 2 -i 0.2 " . $tg->getCommand()["args"][0])) . "```", null, true, false);
	return;
}

if ($tg->getCommand()["args"][0] == "localhost") {
	$tg->sendPhoto("images/127.jpg");
	return;
}
// Don't allow internal domains
if (preg_match("#(.*)\.(local|lan|box|priv|dev)$#", $tg->getCommand()["args"][0]) && $tg->getPermission() != Telegram::ADMIN_SUPER) {
	$tg->sendMessage("Internal domains are not allowed.");
	return;
}

$tg->sendMessage("```" . trim(shell_exec("ping -c 3 -i 0.2 -W1 -n " . $tg->getCommand()["args"][0])) . "```", null, true, false);
