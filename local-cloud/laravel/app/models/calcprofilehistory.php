<?php

class CalcProfileHistory extends PDODatabaseModel
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */
    public $columns = array(
        'user_id',
        'date',
        'follower_count',
        'following_count',
        'reach',
        'board_count',
        'pin_count',
        'repin_count',
        'like_count',
        'comment_count',
        'pins_atleast_one_repin',
        'pins_atleast_one_like',
        'pins_atleast_one_comment',
        'pins_atleast_one_engage',
        'timestamp',
        'estimate',
    );
    public $table = 'calcs_profile_history';
    public
        $user_id,
        $date,
        $follower_count,
        $following_count,
        $reach,
        $board_count,
        $pin_count,
        $repin_count,
        $like_count,
        $comment_count,
        $pins_atleast_one_repin,
        $pins_atleast_one_like,
        $pins_atleast_one_comment,
        $pins_atleast_one_engage,
        $timestamp,
        /**
         * @var $estimate
         * if this is an actual calculation or was estimated by tailwind
         * 1 -- it was calculated
         * 0 -- its real
         */
        $estimate;
    public $primary_keys = array('user_id', 'date');

    /*
    |--------------------------------------------------------------------------
    | Static Methods
    |--------------------------------------------------------------------------
    */

    /**
     * @author  Will
     *
     * @param array|int $user_id
     * @param string    $when
     * @param bool      $force_exact if we want the calculation on that exact timestamp or just the
     *                               first one that's less than that time
     *
     * @return $this
     */
    public static function find(
        $user_id,
        $when = 'latest',
        $force_exact = false
    )
    {
        $calc          = new CalcProfileHistory();
        $calc->user_id = $user_id;

        return $calc->refreshTo($when,$force_exact);
    }

    /**
     * Updates the model with a calc latest calc
     * that is earlier than the time given
     *
     * @param string $when
     *
     * @param bool   $force_exact
     *
     * @return $this
     */
    public function refreshTo($when = 'latest',$force_exact = false)
    {
        /*
         *
         * @todo - check if this date matches send_At
         *
         * If its a string, we check to see if its a sting
         * that needs to be converted to time or if its
         * just the default
         */
        if (is_string($when)) {
            if ($when == 'latest' || $when == 'first') {
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

        if ($force_exact) {

            $STH = $this->DBH->prepare("
                        select * from calcs_profile_history
                        where date = :time
                        AND user_id =:user_id
                    ");

        } else {
            $order_by_direction = ($when == 'first') ? 'ASC' : 'DESC';

            $STH = $this->DBH->prepare("
            select * from calcs_profile_history
            where date < :time
            AND user_id = :user_id
            order by date $order_by_direction
            Limit 1
          ");

        }

        $STH->execute(
            array(
                 ':time'    => $time,
                 ':user_id' => $this->user_id
            )
        );

        if ($STH->rowCount() == 0) {
            return false;
        }

        return $this->loadDBData($STH->fetch());
    }

    /**
     * @author  Will
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
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
*/

class CalcProfileHistoryException extends DBModelException {}
