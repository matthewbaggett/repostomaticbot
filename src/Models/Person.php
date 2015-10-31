<?php
namespace Repostomatic\Models;

use \Thru\ActiveRecord\ActiveRecord;

/**
 * Class Person
 * @package Interventio\Models
 * @var $person_id integer
 * @var $telegram_id integer
 * @var $username text nullable
 * @var $firstname text nullable
 * @var $lastname text nullable
 */
class Person extends ActiveRecord
{
    protected $_table = "people";

    public $person_id;
    public $telegram_id;
    public $username;
    public $firstname;
    public $lastname;

    public function getName()
    {
        if ($this->username) {
            return "@{$this->username}";
        } else {
            return trim(" ", "{$this->firstname} {$this->lastname}");
        }
    }

    /**
     * @param $telegram_id
     * @param string $username
     * @param string $firstname
     * @param string $lastname
     * @return Person
     */
    public static function CreateOrFetch($telegram_id, $username = '', $firstname = '', $lastname = '')
    {
        $person = Person::search()->where('telegram_id', $telegram_id)->execOne();
        if (!$person) {
            $person = new Person();
            $person->telegram_id = $telegram_id;
            $person->save();
        }
        if ($person->username != $username) {
            $person->username = $username;
            $person->save();
        }
        if ($person->firstname != $firstname || $person->lastname != $lastname) {
            $person->firstname = $firstname;
            $person->lastname = $lastname;
            $person->save();
        }
        return $person;
    }
}
