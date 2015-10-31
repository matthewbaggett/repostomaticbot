<?php
namespace Repostomatic\Models;

use \Thru\ActiveRecord\ActiveRecord;

/**
 * Class Photo
 * @package Interventio\Models
 * @var $photo_id integer
 * @var $file_id text
 * @var $file_size integer
 * @var $width integer
 * @var $height integer
 * @var $storage_location text nullable
 * @var $md5_sum text nullable
 */
class Photo extends ActiveRecord
{
    protected $_table = "photos";

    public $photo_id;
    public $file_id;
    public $file_size;
    public $width;
    public $height;
    public $storage_location;
    public $md5_sum;

    public static function CreateOrUpdateFromTelegramUpdate($photoArray)
    {
        global $telegram;
        $return = [];
        foreach ($photoArray as $photoArrayElem) {
            $photo = self::search()->where('file_id', $photoArrayElem['file_id'])->execOne();
            if (!$photo) {
                $photo = new self();
            }
            $photo->file_id = $photoArrayElem['file_id'];
            $photo->file_size = $photoArrayElem['file_size'];
            $photo->width = $photoArrayElem['width'];
            $photo->height = $photoArrayElem['height'];
            $photo->save();
            $pixelCount = $photo->height * $photo->width;

            $telegramFile = $telegram->getFile($photo->file_id);
            $return[$pixelCount] = [$photo, $telegramFile];
        }

        krsort($return);
        return $return;
    }

    /**
     * @return Message
     */
    public function getMessage()
    {
        return Message::search()
            ->where('photo_id', $this->photo_id)
            ->execOne();
    }

    /**
     * @return Photo[]
     */
    public function findDuplicates()
    {
        return self::search()
            //->where('photo_id', $this->photo_id, "!=")
            ->where('md5_sum', $this->md5_sum)
            ->order('photo_id', 'ASC')
            ->exec();
    }
}
