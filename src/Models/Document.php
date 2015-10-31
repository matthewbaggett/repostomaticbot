<?php
namespace Repostomatic\Models;

use \Thru\ActiveRecord\ActiveRecord;

/**
 * Class Message
 * @package Interventio\Models
 * @var $document_id integer
 * @var $file_name text
 * @var $mime_type text
 * @var $file_id text
 * @var $file_size integer
 */
class Document extends ActiveRecord
{
    protected $_table = "documents";

    public $document_id;
    public $file_name;
    public $mime_type;
    public $file_id;
    public $file_size;

    public static function CreateOrUpdateFromTelegramUpdate($documentArray)
    {
        global $telegram;
        $document = self::search()->where('file_id', $documentArray['file_id'])->execOne();
        if (!$document) {
            $document = new self();
        }
        $document->file_name = $documentArray['file_name'];
        $document->mime_type = $documentArray['mime_type'];
        $document->file_id = $documentArray['file_id'];
        $document->file_size = $documentArray['file_size'];
        $document->save();

        $telegramFile = $telegram->getFile($document->file_id);

        return [$document, $telegramFile];

    }
}
