#!/usr/bin/env php
<?php
use Repostomatic\Models;

require_once("vendor/autoload.php");
define('APP_ROOT', __DIR__);
define('TELEGRAM_KEY_FILE', APP_ROOT . "/telegram.key");
if(!file_exists(TELEGRAM_KEY_FILE)){
    die("No telegram.key file!\n");
}
define('DREAMHOST_KEY_FILE', APP_ROOT . "/dreamhost.key");
if(!file_exists(DREAMHOST_KEY_FILE)){
    die("No dreamhost.key file!\n");
}
$dreamhostKey = file_get_contents(DREAMHOST_KEY_FILE);
$telegramApiKey = trim(file_get_contents(TELEGRAM_KEY_FILE));
echo "Telegram API Key: {$telegramApiKey}\n";
$telegram = new Telegram\Bot\Api($telegramApiKey);

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
$database = new \Thru\ActiveRecord\DatabaseLayer($dbConnection);

echo "Waiting for database warmup... ";
sleep(5);
echo "DONE\n";

$dreamhostKey = explode("\n", $dreamhostKey);
$storageClient = \Aws\S3\S3Client::factory(array(
    'base_url' => 'https://objects.dreamhost.com',
    'key'      => $dreamhostKey[0],
    'secret'   => $dreamhostKey[1],
));

$storageAdaptor = new \League\Flysystem\AwsS3v2\AwsS3Adapter($storageClient, 'reposts');

$filesystem = new \League\Flysystem\Filesystem($storageAdaptor);


$middlewares = [
    new Repostomatic\RepostChecker($telegram, $filesystem),
    new Repostomatic\AdminCommands($telegram, $filesystem)
];

echo "Repost-o-matic is Listening... Key is \"{$telegramApiKey}\"\n";

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
        $message = Models\Message::CreateOrUpdateFromTelegramUpdate($update, $filesystem);
        echo "New Message in {$message->getChat()->title} at {$message->getDate("H:i:s")} " .
            "from {$message->getFrom()->getName()}: " .
            "{$message->message}\n";
        foreach ($middlewares as $middleware) {
            $middleware->process($message);
        }
    }

    sleep(3);
}
