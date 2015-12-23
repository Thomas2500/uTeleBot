<?php
global $tg;

if (!file_exists("data/stats/st" . $tg->getChatID() . ".json")) {
	$tg->sendMessage("I'm sorry. I have no statistic for you.");
	return;
}

$stats = json_decode(file_get_contents("data/stats/st" . $tg->getChatID() . ".json"), true);

if ($tg->getChatType() == "private") {
	$message = "*STATISTICS*\n";
	$message .= "Messages sent in this private chat: *" . $stats[$tg->getUserID()]["messages"] . "*";
	$tg->sendMessage($message, null, true, false);
	return;
}

usort($stats, function($a, $b) {
    return $b['messages'] <=> $a['messages'];
});

$message = "*TOPLIST*\n";
$i = 1;
$messageCount = 0;
$activeLast24Hours = 0;
$activeLastHour = 0;
foreach ($stats as $listitem) {

	if (!isset($listitem["messages"])) {
		continue;
	}

	$message .= "*#" . $i . "* " . $listitem["username"] . ": " . $listitem["messages"] . "\n";
	$messageCount += intval($listitem["messages"]);
	if ($listitem["date"] >= time()-60*60) {
		$activeLastHour++;
	}
	if ($listitem["date"] >= time()-60*60*24) {
		$activeLast24Hours++;
	}
	$i++;
}

$tg->sendMessage("*Summary*\n" . $messageCount . " messages sent\n" . $activeLastHour . " users active within the last hour\n" . $activeLast24Hours . " users active within the last 24 hours", null, true, false);
$tg->sendMessage($message, null, true, false);
