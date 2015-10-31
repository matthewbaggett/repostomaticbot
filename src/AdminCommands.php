<?php
namespace Repostomatic;

use Coduo\PHPHumanizer\Number;
use Coduo\PHPHumanizer\DateTime;

use Repostomatic\Models\Message;
use Repostomatic\Models\Person;
use Telegram\Bot\Api;
use Repostomatic\Models\Photo;

class AdminCommands
{

    /** @var Api */
    private $telegram;

    public function __construct(Api $telegram)
    {
        $this->telegram = $telegram;
    }

    public function process(Message $message)
    {
        #echo "Checking for admin commands...";
        if(self::adminCheck($message->getFrom())){
            #echo " Is Admin ...\n";
            $chat = $message->getChat();
            if(strtolower($message->message) == '/enablerepostdetection'){
                $chat->report_reposts = 'Yes';
                $chat->save();
                $this->telegram->sendMessage($chat->telegram_id, "Repost detection enabled in {$chat->title}", false, $message->telegram_message_id);
                return;
            }
            if(strtolower($message->message) == '/disablerepostdetection'){
                $chat->report_reposts = 'No';
                $chat->save();
                $this->telegram->sendMessage($chat->telegram_id, "Repost detection disabled in {$chat->title}", false, $message->telegram_message_id);
                return;
            }
            if(strtolower($message->message) == '/catface'){
                echo "detected catface\n";
                $this->telegram->sendMessage($chat->telegram_id, ":3", false, $message->telegram_message_id);
                return;
            }
        }
        #echo " Is dirty pleb. No commands for you\n";
        return;
    }

    static public function adminCheck(Person $person)
    {
        if(strtolower($person->username) == 'greyscale'){
            return true;
        }

        return false;
    }
}
