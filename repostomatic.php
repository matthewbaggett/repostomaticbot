#!/usr/bin/env php
<?php
use Repostomatic\Models;

require_once("vendor/autoload.php");
define('APP_ROOT', __DIR__);
$telegramApiKey = file_get_contents("telegram.key");
$telegram = new Telegram\Bot\Api($telegramApiKey);

if (false) {
    $database = new \Thru\ActiveRecord\DatabaseLayer(array(
        'db_type' => 'Sqlite',
        'db_file' => 'repostomatic.sqlite',
    ));
} else {
    $database = new \Thru\ActiveRecord\DatabaseLayer(array(
        'db_type' => 'Mysql',
        'db_hostname' => 'localhost',
        'db_port' => '3306',
        'db_username' => 'repostomatic',
        'db_password' => 'repostomatic',
        'db_database' => 'repostomatic',
    ));
}

$middlewares = [
    new Repostomatic\RepostChecker($telegram)
];

while (true) {
    //\Kint::dump(
    //    $telegram->getMe()->getUsername(),
    //    $telegram->getUpdates()
    //);
    $lastMessage = Models\Message::search()->order('update_id', 'DESC')->execOne();
    if ($lastMessage) {
        $startingOffset = $lastMessage->update_id + 1;
    } else {
        $startingOffset = null;
    }
    foreach ($telegram->getUpdates($startingOffset) as $update) {
        $message = Models\Message::CreateOrUpdateFromTelegramUpdate($update);
        echo "New Message from {$message->getFrom()->getName()}: {$message->message}\n";
        foreach ($middlewares as $middleware) {
            $middleware->process($message);
        }
    }

    sleep(3);
}
