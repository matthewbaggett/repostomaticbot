<?php
namespace Repostomatic\Models;

use League\Flysystem\AdapterInterface;
use \Thru\ActiveRecord\ActiveRecord;

/**
 * Class Message
 * @package Interventio\Models
 * @var $message_id integer
 * @var $telegram_message_id integer
 * @var $update_id integer
 * @var $from_id integer
 * @var $chat_id integer
 * @var $date date
 * @var $message text nullable
 * @var $document_id nullable
 * @var $photo_id nullable
 */
class Message extends ActiveRecord
{
    protected $_table = "messages";

    public $message_id;
    public $telegram_message_id;
    public $update_id;
    public $from_id;
    public $chat_id;
    public $date;
    public $message;
    public $document_id = 0;
    public $photo_id = 0;

    private $_from;
    private $_chat;
    private $_photo;
    private $_document;

    /**
     * @return Person
     */
    public function getFrom()
    {
        if (!$this->_from) {
            $this->_from = Person::search()->where('person_id', $this->from_id)->execOne();
        }
        return $this->_from;
    }

    /**
     * @return Chat
     */
    public function getChat()
    {
        if (!$this->_chat) {
            $this->_chat = Chat::search()->where('chat_id', $this->chat_id)->execOne();
        }
        return $this->_chat;
    }

    /**
     * @return Photo
     */
    public function getPhoto()
    {
        if (!$this->_photo) {
            $this->_photo = Photo::search()->where('photo_id', $this->photo_id)->execOne();
        }
        return $this->_photo;
    }

    /**
     * @return Document
     */
    public function getDocument()
    {
        if (!$this->_document) {
            $this->_document = Document::search()->where('document_id', $this->document_id)->execOne();
        }
        return $this->_document;
    }

    /**
     * @param \Telegram\Bot\Objects\Update $update
     * @return Message
     */
    public static function CreateOrUpdateFromTelegramUpdate(\Telegram\Bot\Objects\Update $update, \League\Flysystem\Filesystem $storageAdaptor)
    {

        global $telegram;

        $message = Message::search()->where('telegram_message_id', $update->getMessage()->getMessageId())->execOne();

        $user = Person::CreateOrFetch(
            $update->getMessage()->getFrom()->getId(),
            $update->getMessage()->getFrom()->getUsername(),
            $update->getMessage()->getFrom()->getFirstName(),
            $update->getMessage()->getFrom()->getLastName()
        );
        $chat = Chat::CreateOrFetch(
            $update->getMessage()->getChat()->getId(),
            $update->getMessage()->getChat()->getTitle()
        );
        if (!$message) {
            $message = new Message();
        }
        $message->telegram_message_id = $update->getMessage()->getMessageId();
        $message->update_id = $update->getUpdateId();
        $message->from_id = $user->person_id;
        $message->chat_id = $chat->chat_id;
        $message->date = date("Y-m-d H:i:s", $update->getMessage()->getDate());
        $message->message = $update->getMessage()->getText();

        if ($update->getMessage()->getDocument()) {
            echo "Found a document!\n";
            /** @var $document Document */
            list($document, $telegramFile) = Document::CreateOrUpdateFromTelegramUpdate($update->getMessage()->getDocument());
            $url = "https://api.telegram.org/file/bot{$telegram->getAccessToken()}/{$telegramFile->get('file_path')}";
            $downloadedData = file_get_contents($url);
            $outputPath = APP_ROOT . "/download/{$chat->chat_id}/";
            $filePath = "docs/" . $message->getChat()->chat_id . "/" . str_replace("document/", "", $document->file_name);
            echo "Writing to {$filePath}\n";
            $storageAdaptor->put($filePath, $downloadedData);
            $storageAdaptor->setVisibility($filePath, 'public');
            $message->document_id = $document->document_id;
        }

        if ($update->getMessage()->getPhoto()) {
            echo "Found a photo!\n";
            /** @var $photo Photo */
            $photos = Photo::CreateOrUpdateFromTelegramUpdate($update->getMessage()->getPhoto());
            krsort($photos);
            foreach ($photos as $pixelCount => list($photo, $telegramFile)) {

                $url = "https://api.telegram.org/file/bot{$telegram->getAccessToken()}/{$telegramFile->get('file_path')}";
                $downloadedData = file_get_contents($url);
                $outputFile = "photos/" . $message->getChat()->chat_id . "/" . $photo->file_id . "_" . $pixelCount;

                if (self::is_jpeg($downloadedData)) {
                    $outputFile .= ".jpg";
                }
                if (self::is_png($downloadedData)) {
                    $outputFile .= ".png";
                }

                echo "Writing to {$outputFile}\n";
                $storageAdaptor->put($outputFile, $downloadedData);
                $storageAdaptor->setVisibility($outputFile, 'public');
                $photo->storage_location = $outputFile;
                $photo->md5_sum = md5($downloadedData);
                $photo->save();
            }

            $biggest = reset($photos);
            list($biggestPhoto, $biggestTelegramFile) = $biggest;

            $message->photo_id = $biggestPhoto->photo_id;
        }
        $message->save();
        return $message;
    }

    private static function is_jpeg(&$pict)
    {
        return (bin2hex($pict[0]) == 'ff' && bin2hex($pict[1]) == 'd8');
    }

    private static function is_png(&$pict)
    {
        return (bin2hex($pict[0]) == '89' && $pict[1] == 'P' && $pict[2] == 'N' && $pict[3] == 'G');
    }
}
