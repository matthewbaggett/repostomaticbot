#!/usr/bin/env php
<?php
use Repostomatic\Models;

require_once("vendor/autoload.php");
define('APP_ROOT', __DIR__);
define('TELEGRAM_KEY_FILE', APP_ROOT . "/telegram.key");
if(!file_exists(TELEGRAM_KEY_FILE)){
    die("No telegram.key file!\n");
}
$telegramApiKey = file_get_contents(TELEGRAM_KEY_FILE);
$telegram = new Telegram\Bot\Api($telegramApiKey);
echo "Repostomatic is Listening...\n";

if(isset($_SERVER['DB_PORT'])){
    $hostUrl = parse_url($_SERVER['DB_PORT']);
    $dbConnection = array(
        'db_type' => 'Mysql',
        'db_hostname' => $hostUrl['host'],
        'db_port' => $hostUrl['port'],
        'db_username' => $_SERVER['DB_ENV_MYSQL_USER'],
        'db_password' => $_SERVER['DB_ENV_MYSQL_PASSWORD'],
        'db_database' => $_SERVER['DB_ENV_MYSQL_DATABASE'],
    );
}else {
    $dbConnection = array(
        'db_type' => 'Mysql',
        'db_hostname' => 'localhost',
        'db_port' => '3306',
        'db_username' => 'repostomatic',
        'db_password' => 'repostomatic',
        'db_database' => 'repostomatic',
    );
}
\Kint::dump($dbConnection);
$database = new \Thru\ActiveRecord\DatabaseLayer($dbConnection);

$middlewares = [
    new Repostomatic\RepostChecker($telegram),
    new Repostomatic\AdminCommands($telegram)
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
