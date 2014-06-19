<?php

/**
 * Collection of PinsRepins
 *
 * @author Will
 */
class PinsRepins extends DBCollection
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */
    public
        $table = 'data_pins_repins',
        $columns =
        array(
            'pin_id',
            'repinner_user_id',
            'board_id',
            'board_url',
            'board_name',
            'category',
            'board_follower_count',
            'is_collaborative',
            'created_at',
            'timestamp'
        ),
        $primary_keys = array('pin_id', 'repinner_user_id');

    /**
     * Tests if these pins are already in the database
     *
     * @author  Will
     */
    public function existInDB($all=true)
    {
        $repinner_user_ids        = $this->columnToArray('repinner_user_id');
        $repinner_user_ids_string = $this->stringifyField('repinner_user_id');

        $pin_id = $this->first()->pin_id;

        $STH = $this->DBH->prepare("
                    select * from data_pins_repins
                    where repinner_user_id IN ($repinner_user_ids_string)
                    AND pin_id = :pin_id
                ");

        $STH->execute(
            array(
                 ':pin_id' => $pin_id
            )
        );

        if (!$all && $STH->rowCount() > 0) {
            return true;
        }

        if (count($repinner_user_ids) === $STH->rowCount()) {
            return true;
        }

        return false;
    }

    /**
     * If any repins already exist in the database
     * @author  Will
     * @return bool
     */
    public function anyExistInDB() {
        return $this->existInDB(false);
    }

    /**
     * @author  Will
     * @return bool
     */
    public function allDoNotExistInDB() {
        return !$this->anyExistInDB();
    }

    /**
     * @author  Will
     *
     * @see existInDB()
     *
     */
    public function doNotExistInDB() {
        return !$this->existInDB();
    }


    /**
     * @author  Will
     */
    public function saveProfileUserIds()
    {

        $profiles = new Profiles();
        foreach ($this->models as $pin_repin) {
            $profile              = new Profile();
            $profile->user_id     = $pin_repin->repinner_user_id;
            $profile->track_type  = 'repin_user';
            $profile->timestamp   = time();
            $profile->last_pulled = 0;
            $profiles->add($profile);

        }

        return $profiles->saveModelsToDB();

    }
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
*/

class PinsRepinsException extends CollectionException {}
