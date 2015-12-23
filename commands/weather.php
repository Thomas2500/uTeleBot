<?php
global $tg;

$location = trim(implode(" ", $tg->getCommand()["args"]));
if (empty($location)) {
	$location = "Siegenfeld";
}

$wd = json_decode(file_get_contents("http://api.openweathermap.org/data/2.5/weather?q=" . $location . "&appid=b5168337a03a7efd4637b0f6203dc761&lang=en&units=metric"), true);
$message = "The temperature in " . $wd["name"] . " (".$wd["sys"]["country"].") is *" . $wd["main"]["temp"] . "°C*\n";

$message .= "Condition: _" . $wd["weather"][0]["description"] . "_ ";
if ($wd["weather"][0]["main"] == "Clear") {
	$message .= '☀';
} else if ($wd["weather"][0]["main"] == "Clouds") {
	$message .= '☁☁';
} else if ($wd["weather"][0]["main"] == "Rain") {
	$message .= '☔';
} else if ($wd["weather"][0]["main"] == "Thunderstorm") {
	$message .= '☔☔☔☔';
} else {
	$message .= $wd["weather"][0]["main"];
}

$message .= "\nData provided by [OpenWeatherMap](https://openweathermap.org/)";

$tg->sendMessage($message, null, true, true);
