<?php
/**
 * A wrapper class for telegram bot api
 *
 * @link    https://github.com/Thomas2500/uTeleBot
 * @author  Thomas Bella
 * @licence AGPL
 *
 */
class Telegram {

    const ACTION_TYPING = 'typing';
    const ACTION_UPLOAD_PHOTO = 'upload_photo';
    const ACTION_RECORD_VIDEO = 'record_video';
    const ACTION_UPLOAD_VIDEO = 'upload_video';
    const ACTION_RECORD_AUDIO = 'record_audio';
    const ACTION_UPLOAD_AUDIO = 'upload_audio';
    const ACTION_UPLOAD_DOC = 'upload_document';
    const ACTION_FIND_LOCATION = 'find_location';

    const BOT_URL = 'https://api.telegram.org/bot';

    const ADMIN_NOTHING = 0;
    const ADMIN_CHAT = 1;
    const ADMIN_GLOBAL = 2;
    const ADMIN_SUPER = 3;

    private $webhook = false;

	private $available_commands = [
		'getMe',
		'sendMessage',
		'forwardMessage',
		'sendPhoto',
		'sendAudio',
		'sendDocument',
		'sendSticker',
		'sendVideo',
		'sendLocation',
		'sendChatAction',
		'getUserProfilePhotos',
		'getUpdates',
		'setWebhook',
		'getFile'
	];

	private $adminList = [];

	private $apiKey = "";

	/*
	[
		[
			"method" => "sendMessage",
			"param" => [...],
			"extra" => [...]
		], ...
	]
	 */
	private $messages = [];

	/* RESPOND TO MESSAGE */
	private $chatID;
	private $userID;
	private $firstName;
	private $lastName;
	private $userName;
	private $chatType;
	private $text;
	private $messageID;

	/**
	 * Initialize the Telegram-API and check if script was accessed by a webhook
	 * @param string $apiKey The API-Key of the bot
	 */
	function __construct($apiKey) {
		$this->apiKey = $apiKey;

		$input = file_get_contents('php://input');
		if (!empty($input)) {
			$this->parseWebhook($input);

			// Load permissions
			if (!file_exists("data/permissions.json")) {
				file_put_contents("data/permissions.json", "[]");
			}
			$this->adminList = json_decode(file_get_contents("data/permissions.json"), true);
		}
	}

	/**
	 * Gives back an answer for webhook requests
	 * @return string
	 */
	public function webhookAnswer() {
		if (empty($this->messages) || empty($this->messages[0])) {
			return "{}";
		}
		return json_encode(array_merge($this->messages[0]["param"], ["method" => $this->messages[0]["method"]]));
	}

	/**
	 * Sends back messages to telegram
	 */
	public function answer() {
		if ($this->webhook) {
			unset($this->messages[0]);
		}
		if (count($this->messages) >= 1) {
			return $this->execute();
		}
	}

	/**
	 * Sends a new message
	 * @param string $text Content of the message
	 * @param int $chatID
	 * @param bool $useMarkdown
	 * @param book $disableWebpagePreview
	 * @param int $replyToMessageID
	 */
	public function sendMessage($text, $chatID = null, $useMarkdown = false, $disableWebpagePreview = false, $replyToMessageID = null) {
		if ($chatID == null) {
			if ($this->chatID != null) {
				$chatID = $this->chatID;
			} else {
				error_log("[TELEGRAM] No chatID given. Dropping message.\n");
				return false;
			}
		}

		if (strtolower($useMarkdown) == "markdown" || $useMarkdown == true || $useMarkdown == 1) {
			$useMarkdown = "Markdown";
		} else {
			$useMarkdown = "";
		}

		$this->messages[] = [
			"method" => "sendMessage",
			"param" => [
				"chat_id" => $chatID,
				"text" => $text,
				"parse_mode" => $useMarkdown,
				"disable_web_page_preview" => $disableWebpagePreview,
				"reply_to_message_id" => $replyToMessageID
			]
		];
	}

	/**
	 * Forwards a message
	 * @param int $fromChatID
	 * @param int $messageID
	 * @param int $chatID
	 */
	public function forwardMessage($fromChatID, $messageID, $chatID) {
		$this->messages[] = [
			"method" => "forwardMessage",
			"param" => [
				"chat_id" => $chatID,
				"from_chat_id" => $fromChatID,
				"message_id" => $messageID
			]
		];
	}

	/**
	 * Sends a new photo
	 * @param string $photo Path to the image on the local disk
	 * @param int $chatID
	 * @param string $caption
	 * @param int $replyToMessageID
	 */
	public function sendPhoto($photo, $chatID = null, $caption = null, $replyToMessageID = null) {
		if ($chatID == null) {
			if ($this->chatID != null) {
				$chatID = $this->chatID;
			} else {
				error_log("[TELEGRAM] No chatID given. Dropping message.\n");
				return false;
			}
		}

		if ($this->webhook) {
			$this->messages[] = null;
		}

		$this->sendChatAction(self::ACTION_UPLOAD_PHOTO);
		$this->messages[] = [
			"method" => "sendPhoto",
			"param" => [
				"chat_id" => $chatID,
				'photo' => $photo,
				'caption' => $caption,
				'reply_to_message_id' => $replyToMessageID
			]
		];
	}

	/**
	 * Sends a new photo
	 * @param string $document Path to the document on the local disk
	 * @param int $chatID
	 * @param int $replyToMessageID
	 */
    public function sendDocument($document, $chatID = null, $replyToMessageID = null) {
		if ($chatID == null) {
			if ($this->chatID != null) {
				$chatID = $this->chatID;
			} else {
				error_log("[TELEGRAM] No chatID given. Dropping message.\n");
				return false;
			}
		}

		if ($this->webhook) {
			$this->messages[] = null;
		}

		$this->sendChatAction(self::ACTION_UPLOAD_DOC);
		$this->messages[] = [
			"method" => "sendDocument",
			"param" => [
				"chat_id" => $chatID,
				'document' => $document,
				'reply_to_message_id' => $replyToMessageID
			]
		];
    }

	/**
	 * Sends a new audio file (must be .mp3 format)
	 * @param string $audio Path to the audio file on the local disk
	 * @param int $chatID
	 * @param int $duaration Duration in seconds
	 * @param string $performer
	 * @param string $track Track name
	 * @param int $replyToMessageID
	 */
	public function sendAudio($audio, $chatID = null, $duration = null, $performer = null, $title = null, $replyToMessageID = null)	{
		if ($chatID == null) {
			if ($this->chatID != null) {
				$chatID = $this->chatID;
			} else {
				error_log("[TELEGRAM] No chatID given. Dropping message.\n");
				return false;
			}
		}

		if ($this->webhook) {
			$this->messages[] = null;
		}

		$this->sendChatAction(self::ACTION_UPLOAD_AUDIO);
		$this->messages[] = [
			"method" => "sendAudio",
			"param" => [
				"chat_id" => $chatID,
				'audio' => $audio,
				'duration' => $duration,
				'performer' => $performer,
				'title' => $title,
				'reply_to_message_id' => $replyToMessageID
			]
		];
	}

	/**
	 * Sends a new (voice) audio file (must be .ogg file encoded with OPUS)
	 * @param string $voice Path to the audio file on the local disk
	 * @param int $chatID
	 * @param int $duration Duration of the audio file in seconds
	 * @param int $replyToMessageID
	 */
	public function sendVoice($voice, $chatID = null, $duration = null, $replyToMessageID = null)	{
		if ($chatID == null) {
			if ($this->chatID != null) {
				$chatID = $this->chatID;
			} else {
				error_log("[TELEGRAM] No chatID given. Dropping message.\n");
				return false;
			}
		}

		if ($this->webhook) {
			$this->messages[] = null;
		}

		$this->sendChatAction(self::ACTION_RECORD_AUDIO);
		$this->messages[] = [
			"method" => "sendVoice",
			"param" => [
				"chat_id" => $chatID,
				'voice' => $voice,
				'duration' => $duration,
				'reply_to_message_id' => $replyToMessageID
			]
		];
	}

	/**
	 * Sends a new video
	 * @param string $video Path to the video on the local disk
	 * @param int $chatID
	 * @param int $replyToMessageID
	 */
	public function sendVideo($video, $chatID = null, $replyToMessageID = null) {
		if ($chatID == null) {
			if ($this->chatID != null) {
				$chatID = $this->chatID;
			} else {
				error_log("[TELEGRAM] No chatID given. Dropping message.\n");
				return false;
			}
		}

		if ($this->webhook) {
			$this->messages[] = null;
		}

		$this->sendChatAction(self::ACTION_UPLOAD_VIDEO);
		$this->messages[] = [
			"method" => "sendVideo",
			"param" => [
				"chat_id" => $chatID,
				'video' => $video,
				'reply_to_message_id' => $replyToMessageID
			]
		];
	}

	/**
	 * Sends a physical location
	 * @param float $latitude Path to the image on the local disk
	 * @param float $longitude Path to the image on the local disk
	 * @param int $chatID
	 * @param int $replyToMessageID
	 */
	public function sendLocation($latitude, $longitude, $chatID = null, $replyToMessageID = null)	{
		if ($chatID == null) {
			if ($this->chatID != null) {
				$chatID = $this->chatID;
			} else {
				error_log("[TELEGRAM] No chatID given. Dropping message.\n");
				return false;
			}
		}

		if ($this->webhook) {
			$this->messages[] = null;
		}

		$this->sendChatAction(self::ACTION_FIND_LOCATION);
		$this->messages[] = [
			"method" => "sendLocation",
			"param" => [
				"chat_id" => $chatID,
				'latitude' => $latitude,
				'longitude' => $longitude,
				'reply_to_message_id' => $replyToMessageID
			]
		];
	}

	/**
	 * Sends a chat action (like typing into keyboard)
	 * @param string $type Type of action. Use ACTION_*
	 * @param int $chatID
	 */
	public function sendChatAction($type, $chatID = null) {
		if ($chatID == null) {
			if ($this->chatID != null) {
				$chatID = $this->chatID;
			} else {
				error_log("[TELEGRAM] No chatID given. Dropping message.\n");
				return false;
			}
		}
		if ($this->webhook && empty($this->messages)) {
			return;
		}

		$this->messages[] = [
			"method" => "sendChatAction",
			"param" => [
				"chat_id" => $chatID,
				"action" => $type
			]
		];
	}

	/**
	 * Get the full content of the received message
	 * @return string
	 */
	public function getText() {
		if (!empty($this->text)) {
			return $this->text;
		}
		return false;
	}

	/**
	 * Get the current chatID
	 * @return int
	 */
	public function getChatID() {
		if (!empty($this->chatID)) {
			return intval($this->chatID);
		}
		return false;
	}

	/**
	 * Get the current userID
	 * @return int
	 */
	public function getUserID() {
		if (!empty($this->userID)) {
			return intval($this->userID);
		}
		return false;
	}

	/**
	 * Get the current Username
	 * @return string
	 */
	public function getUserName() {
		if (!empty($this->userName)) {
			return $this->userName;
		}
		return false;
	}

	/**
	 * Get the current chattype
	 * @return string
	 */
	public function getChatType() {
		if (!empty($this->chatType)) {
			return $this->chatType;
		}
		return false;
	}

	/**
	 * Extract command and arguments out of a message
	 * @param string $delimiter Delimiter to split message (Default: " ")
	 * @return array
	 */
	public function getCommand($delimiter = " ") {
		$fragment = explode($delimiter, $this->text);
		$base = str_replace("@uTeleBot", "", $fragment[0]);
		unset($fragment[0]);
		return [ "command" => $base, "args" => array_values($fragment) ];
	}

	/**
	 * Get the permission/access level of an user
	 * @param int $userID
	 * @param int $chatID
	 * @return int
	 */
	public function getPermission($userID = null, $chatID = null) {
		if ($userID == null) {
			if ($this->userID != null) {
				$userID = $this->userID;
			} else {
				error_log("[TELEGRAM] No userID given. Dropping message.\n");
				return false;
			}
		}
		if ($chatID == null) {
			if ($this->chatID != null) {
				$chatID = $this->chatID;
			} else {
				error_log("[TELEGRAM] No chatID given. Dropping message.\n");
				return false;
			}
		}

		if (!isset($this->adminList[$userID])) {
			return self::ADMIN_NOTHING;
		}
		if ($this->adminList[$userID] == self::ADMIN_SUPER) {
			return self::ADMIN_SUPER;
		}
		if ($this->adminList[$userID] == self::ADMIN_GLOBAL) {
			return self::ADMIN_GLOBAL;
		}
		if (!isset($this->adminList[$userID][$chatID])) {
			return self::ADMIN_NOTHING;
		} else {
			return $this->adminList[$userID][$chatID];
		}
	}

	/**
	 * Send prepared messages to the Telegram-API
	 */
	private function execute() {
		foreach ($this->messages as $message) {

			if ($message["method"] == "sendPhoto") {
				$message["param"]["photo"] = new CURLFile(realpath($message["param"]["photo"]));
			} else if ($message["method"] == "sendDocument") {
				$message["param"]["document"] = new CURLFile(realpath($message["param"]["document"]));
			}

			$handle = curl_init();
			curl_setopt($handle, CURLOPT_URL, self::BOT_URL . $this->apiKey . '/' . $message["method"]);

			if ($message["method"] == "sendPhoto" || $message["method"] == "sendDocument") {
				curl_setopt($handle, CURLOPT_HTTPHEADER, [ "Content-Type:multipart/form-data" ]);
			}

			curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($handle, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($handle, CURLOPT_POST, count($message["param"]));
			curl_setopt($handle, CURLOPT_POSTFIELDS, $message["param"]);
			$result = curl_exec($handle);

			if ($result === false) {
				$errno = curl_errno($handle);
				$error = curl_error($handle);
			}
			$http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
			curl_close($handle);

			if ($http_code >= 500) {
				// Prevent DDoS of telegram servers
				sleep(5);
			} else if ($http_code != 200) {
				error_log("[TELEGRAM] Request failed\n" . print_r($result, true) . "\n\n");
			}
		}
	}

	/**
	 * parse the webhook request and fill local variables
	 * @param string $input JSON object from telegram webhook
	 */
	private function parseWebhook($input) {
		$message = json_decode($input, true);
		if (!empty($message)) {
			$this->webhook = true;

			$this->chatID = $message["message"]["chat"]["id"];
			$this->userID = $message["message"]["from"]["id"];
			if (isset($message["message"]["from"]["first_name"])) {
				$this->firstName = $message["message"]["from"]["first_name"];
			}
			if (isset($message["message"]["from"]["last_name"])) {
				$this->lastName = $message["message"]["from"]["last_name"];
			}
			if (isset($message["message"]["from"]["username"])) {
				$this->userName = $message["message"]["from"]["username"];
			}
			$this->chatType = $message["message"]["chat"]["type"];
			if (isset($message["message"]["text"])) {
				$this->text = $message["message"]["text"];
			} else {
				$this->text = "";
			}
			$this->messageID = $message["message"]["message_id"];
		}
	}
}
