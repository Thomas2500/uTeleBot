<?php
global $tg;

$permissionDatabase = json_decode(file_get_contents("data/permissions.json"), true);
if ($permissionDatabase == false) {
	$permissionDatabase = [];
}

if (defined("ADMINKEY")) {

}

//$tg->sendMessage(print_r($tg->getPermission(), true));

if (count($tg->getCommand()["args"]) == 0) {
	$level = "";
	switch ($tg->getPermission()) {
		case Telegram::ADMIN_CHAT:
			$level = "ADMIN_CHAT";
			break;
		case Telegram::ADMIN_GLOBAL:
			$level = "ADMIN_GLOBAL";
			break;
		case Telegram::ADMIN_SUPER:
			$level = "ADMIN_SUPER";
			break;
		default:
			$level = "ADMIN_NOTHING";
			break;
	}
	$tg->sendMessage("Your permission level is: *" . $level . "*", null, true, false);
	return;
} else if ($tg->getCommand()["args"][0] == "add" || $tg->getCommand()["args"][0] == "change") {
	if (count($tg->getCommand()["args"]) != 3) {
		$tg->sendMessage("Comman invalid. Please look into the manpage.");
		return;
	}

	// Read usernames from stats
	if (!file_exists("data/stats/st" . $tg->getChatID() . ".json")) {
		$tg->sendMessage("Stats file not found. Please use the chat where you want to define the new permission.");
		return;
	}

	// Check level
	if ($tg->getCommand()["args"][1] == "chat") {
		$level = Telegram::ADMIN_CHAT;
	} else if ($tg->getCommand()["args"][1] == "global") {
		$level = Telegram::ADMIN_GLOBAL;
	} else if ($tg->getCommand()["args"][1] == "super") {
		$level = Telegram::ADMIN_SUPER;
	} else {
		$tg->sendMessage("Please define a permission level.\nchat - Only for this chat\nglobal - For all chats\nsuper - Servercommands");
		return;
	}

	// Check user rights
	//  Normal users ans ADMIN_CHAT have no rights to modify the permission
	if ($tg->getPermission() == Telegram::ADMIN_NOTHING || $tg->getPermission() == Telegram::ADMIN_CHAT) {
		$tg->sendMessage("Insufficient permissions.");
		return;
	} else if ($tg->getPermission() == Telegram::ADMIN_GLOBAL && $level != Telegram::ADMIN_CHAT) {
		//  ADMIN_GLOBAL can only add ADMIN_CHAT users
		$tg->sendMessage("Insufficient permissions. You need the permission ADMIN_GLOBAL or higher to add an ADMIN_CHAT user.");
		return;
	}

	$stats = json_decode(file_get_contents("data/stats/st" . $tg->getChatID() . ".json"), true);

	$selectedUserID = 0;
	foreach ($stats as $userID => $row) {
		if ($row["username"] == $tg->getCommand()["args"][2] || $row["username"] == str_replace("@", "", $tg->getCommand()["args"][2])) {
			$selectedUserID = $userID;
			break;
		}
	}

	if ($selectedUserID == 0) {
		$tg->sendMessage("No user found with this name.\nIs the username correct and has the user sent a message since the bot was added?");
		return;
	}

	// Check if user already has rights to prevent permission-downgrade by other users
	if (isset($permissionDatabase[$selectedUserID])) {
		// Deny modification of ADMIN_SUPER by non-superusers
		if ($permissionDatabase[$selectedUserID] == Telegram::ADMIN_SUPER && $tg->getPermission() != Telegram::ADMIN_SUPER) {
			$tg->sendMessage("Insufficient permissions.\nYou need to be in the group ADMIN_SUPER to modify a ADMIN_SUPER user.");
			return;
		} else if ($permissionDatabase[$selectedUserID] == Telegram::ADMIN_GLOBAL && $tg->getPermission() != Telegram::ADMIN_SUPER) {
			$tg->sendMessage("Insufficient permissions.\nYou need to be in the group ADMIN_SUPER to modify a ADMIN_GLOBAL user.");
			return;
		} else if (isset($permissionDatabase[$selectedUserID][$tg->getChatID()]) && $permissionDatabase[$selectedUserID][$tg->getChatID()] == Telegram::ADMIN_CHAT && $tg->getPermission() <= Telegram::ADMIN_CHAT) {
			$tg->sendMessage("Insufficient permissions.\nYou need to be in the group ADMIN_GLOBAL to modify a ADMIN_CHAT user.");
			return;
		}
	}

	if ($level == Telegram::ADMIN_SUPER) {
		unset($permissionDatabase[$selectedUserID]);
		$permissionDatabase[$selectedUserID] = Telegram::ADMIN_SUPER;
	} else if ($level == Telegram::ADMIN_GLOBAL) {
		unset($permissionDatabase[$selectedUserID]);
		$permissionDatabase[$selectedUserID] = Telegram::ADMIN_GLOBAL;
	} else if ($level == Telegram::ADMIN_CHAT) {
		if (!is_array($permissionDatabase[$selectedUserID])) {
			unset($permissionDatabase[$selectedUserID]);
			$permissionDatabase[$selectedUserID] = [];
		}
		$permissionDatabase[$selectedUserID][$tg->getChatID()] = Telegram::ADMIN_CHAT;
	}

	$tg->sendMessage("User successfully added to permission table.");
	file_put_contents("data/permissions.json", json_encode($permissionDatabase));
	return;
} else if ($tg->getCommand()["args"][0] == "remove") {

	$stats = json_decode(file_get_contents("data/stats/st" . $tg->getChatID() . ".json"), true);

	$selectedUserID = 0;
	foreach ($stats as $userID => $row) {
		if ($row["username"] == $tg->getCommand()["args"][1] || $row["username"] == str_replace("@", "", $tg->getCommand()["args"][1])) {
			$selectedUserID = $userID;
			break;
		}
	}

	if ($selectedUserID == 0) {
		$tg->sendMessage("No user found with this name.\nIs the username correct and has the user sent a message since the bot was added?");
		return;
	}

	// Check if target user is in table
	if (!isset($permissionDatabase[$selectedUserID])) {
		$tg->sendMessage("Nothing to do.\nUser is in group ADMIN_NOTHING");
		return;
	}

	// Deny deletion of an user with higer permission
	if ($permissionDatabase[$selectedUserID] == Telegram::ADMIN_SUPER && $tg->getPermission() != Telegram::ADMIN_SUPER) {
		$tg->sendMessage("Insufficient permissions.\nYou need to be in the group ADMIN_SUPER to remove a ADMIN_SUPER user.");
		return;
	} else if ($permissionDatabase[$selectedUserID] == Telegram::ADMIN_GLOBAL && $tg->getPermission() != Telegram::ADMIN_SUPER) {
		$tg->sendMessage("Insufficient permissions.\nYou need to be in the group ADMIN_SUPER to remove a ADMIN_GLOBAL user.");
		return;
	} else if (isset($permissionDatabase[$selectedUserID][$tg->getChatID()]) && $permissionDatabase[$selectedUserID][$tg->getChatID()] == Telegram::ADMIN_CHAT && $tg->getPermission() <= Telegram::ADMIN_CHAT) {
		$tg->sendMessage("Insufficient permissions.\nYou need to be in the group ADMIN_GLOBAL or higher to remove a ADMIN_CHAT user.");
		return;
	}

	if (!is_array($permissionDatabase[$selectedUserID])) {
		unset($permissionDatabase[$selectedUserID]);
	} else {
		unset($permissionDatabase[$selectedUserID][$tg->getChatID()]);
	}
	$tg->sendMessage("Permission removed.");
	file_put_contents("data/permissions.json", json_encode($permissionDatabase));
	return;
}
