<?php
namespace Repostomatic\Models;

use \Thru\ActiveRecord\ActiveRecord;

/**
 * Class Chat
 * @package Interventio\Models
 * @var $chat_id integer
 * @var $telegram_id integer
 * @var $title text nullable
 * @var $report_reposts ENUM("Yes","No")
 */
class Chat extends ActiveRecord
{
    protected $_table = "chats";

    public $chat_id;
    public $telegram_id;
    public $title;
    public $report_reposts = "No";

    /**
     * @param $telegram_id
     * @param string $title
     * @return Chat
     */
    public static function CreateOrFetch($telegram_id, $title)
    {
        $chat = Chat::search()->where('telegram_id', $telegram_id)->execOne();
        if (!$chat) {
            $chat = new Chat();
            $chat->telegram_id = $telegram_id;
            $chat->save();
        }
        if ($chat->title != $title) {
            $chat->title = $title;
            $chat->save();
        }
        return $chat;
    }
}
