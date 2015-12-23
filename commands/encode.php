<?php
global $tg;

if (count($tg->getCommand()["args"]) != 1) {
	$tg->sendMessage("Please add chars that should be encoded by base64.");
} else {
	$tg->sendMessage(base64_encode($tg->getText()), null, false);
}
