<?php

$botToken = "7480497994:AAFVkWlOEo-ZeUYgKdjuW64uZ4YdXuU3rMc"; // Replace this
$channel = "@sohilscripter"; // Replace this
$apiURL = "https://api.telegram.org/bot$botToken/";
$update = json_decode(file_get_contents("php://input"), true);

$chatId = $update["message"]["chat"]["id"] ?? null;
$userId = $update["message"]["from"]["id"] ?? null;
$text = $update["message"]["text"] ?? null;
$document = $update["message"]["document"] ?? null;

$stateFile = "states.json";
$states = file_exists($stateFile) ? json_decode(file_get_contents($stateFile), true) : [];

// Check if user joined the channel
if (!isUserInChannel($userId, $channel)) {
    $button = [
        'inline_keyboard' => [
            [['text' => "Join Channel", 'url' => "https://t.me/$channel"]],
        ]
    ];
    sendMessage($chatId, "Please join our channel to use this bot.", $button);
    exit;
}

// Rename Feature
if ($text === "/start") {
    sendMessage($chatId, "Welcome! Send a file to rename it.");
}
elseif ($document) {
    $file_id = $document["file_id"];
    $states[$chatId] = [
        "file_id" => $file_id,
        "waiting_for" => "filename"
    ];
    file_put_contents($stateFile, json_encode($states));

    sendMessage($chatId, "Please send the **new filename** (with extension).");
}
elseif (isset($states[$chatId]) && $states[$chatId]["waiting_for"] === "filename") {
    $newFileName = $text;
    sendDocument($chatId, $states[$chatId]["file_id"], $newFileName);

    unset($states[$chatId]);
    file_put_contents($stateFile, json_encode($states));
}
else {
    sendMessage($chatId, "Send a file to begin renaming.");
}

// --- Functions ---

function isUserInChannel($userId, $channel) {
    global $apiURL;
    $check = json_decode(file_get_contents($apiURL . "getChatMember?chat_id=$channel&user_id=$userId"), true);
    $status = $check["result"]["status"] ?? "";

    return in_array($status, ["member", "administrator", "creator"]);
}

function sendMessage($chat_id, $text, $keyboard = null) {
    global $apiURL;

    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => "Markdown"
    ];

    if ($keyboard) {
        $data['reply_markup'] = json_encode($keyboard);
    }

    file_get_contents($apiURL . "sendMessage?" . http_build_query($data));
}

function sendDocument($chat_id, $file_id, $file_name) {
    global $apiURL;

    $post_fields = [
        'chat_id' => $chat_id,
        'document' => $file_id,
        'caption' => "Here's your renamed file!",
        'filename' => $file_name
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type:multipart/form-data"]);
    curl_setopt($ch, CURLOPT_URL, $apiURL . "sendDocument");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_exec($ch);
    curl_close($ch);
}
?>
