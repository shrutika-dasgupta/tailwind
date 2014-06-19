<?php

/**
 * Class CalcBoardHistories
 */
class CalcBoardHistories extends DBCollection
{
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

    const TABLE = 'calcs_board_history';
    const MODEL = 'CalcBoardHistory';

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
    ),
    $table = 'calcs_board_history',
    $primary_keys = array('board_id', 'date');

    /**
     * @param Board $board
     *
     * @return self
     */
    public static function all(Board $board) {
        $DBH = DatabaseInstance::DBO();
        $STH = $DBH->prepare("
            SELECT * FROM calcs_board_history
            WHERE board_id = :board_id
        ");

        $STH->execute([':board_id'=>$board->board_id]);

        return CalcBoardHistories::createFromDBData($STH->fetchAll());
    }

}

/**
 * Class CalcBoardHistoriesException
 */
class CalcBoardHistoriesException extends CollectionException {}
