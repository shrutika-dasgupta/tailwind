<?php

class CalcBoardHistory extends PDODatabaseModel
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */
    public
        $board_id,
        $date,
        $user_id,
        $followers,
        $follower_reach,
        $pins,
        $repins,
        $likes,
        $comments,
        $pins_atleast_one_repin,
        $pins_atleast_one_like,
        $pins_atleast_one_comment,
        $pins_atleast_one_engage,
        $timestamp;
    /*
    |--------------------------------------------------------------------------
    | Table Meta data
    |--------------------------------------------------------------------------
    */
    public $columns = array(
        'board_id',
        'date',
        'user_id',
        'followers',
        'follower_reach',
        'pins',
        'repins',
        'likes',
        'comments',
        'pins_atleast_one_repin',
        'pins_atleast_one_like',
        'pins_atleast_one_comment',
        'pins_atleast_one_engage',
        'timestamp'
    ), $table = 'calcs_board_history',
        $primary_keys = array('board_id', 'date');

    /*
    |--------------------------------------------------------------------------
    | Static Methods
    |--------------------------------------------------------------------------
    */

    /**
     * @author   Will Washburn
     *
     * @param        $board_id
     * @param string $when
     *
     * @internal param $user_id
     * @return $this
     */
    public static function find($board_id, $when = 'latest')
    {
        $calc           = new self;
        $calc->board_id = $board_id;

        return $calc->refreshTo($when);
    }

    /*
    |--------------------------------------------------------------------------
    | Instance methods
    |--------------------------------------------------------------------------
    */

    /**
     * Updates the model with a calc latest calc
     * that is earlier than the time given
     *
     * @author  Will Washburn
     *
     * @param string $when
     *
     * @return $this
     */
    public function refreshTo($when = 'latest')
    {
        /*
         * If its a string, we check to see if its a sting
         * that needs to be converted to time or if its
         * just the default
         */
        if (is_string($when)) {
            if ($when == 'latest') {
                $time = time();
            } else {
                $time = strtotime($when);
            }
            /*
             *If its a number, we expect a time in epoch time
             */
        } else {
            $time = $when;
        }

        $STH = $this->DBH->prepare("
                select * from calcs_board_history
                where date < :time
                AND board_id = :board_id
                order by date DESC
                Limit 1
              ");

        $STH->execute(
            array(
                 ':time'     => $time,
                 ':board_id' => $this->board_id
            )
        );

        if ($STH->rowCount() == 0) {
            return false;
        }


        return $this->loadDBData($STH->fetch());
    }

    /**
     * @author  Will Washburn
     *
     * @param int $count
     */
    public function setCountsTo($count = 0)
    {
        foreach ($this->columns as $column) {
            if (!in_array($column, array('user_id', 'date', 'timestamp'))) {
                $this->$column = $count;
            }
        }
    }

    /**
     * @ Will
     * @return string
     */
    public function viralityScore() {
        return number_format($this->repins / $this->pins,2);
    }
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
*/

class CalcBoardHistoryException extends DBModelException {}
