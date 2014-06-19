<?php

/**
 * Class CalcProfileHistories
 */
class CalcProfileHistories extends DBCollection
{
    const MODEL = 'CalcProfileHistory';
    const TABLE = 'calcs_profile_history';

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


    /**
     * Get all the history calculations for a given profile
     *
     * @author  Will
     *
     * @param Profile $profile
     *
     * @return self
     */
    public static function all(Profile $profile)
    {
        $DBH = DatabaseInstance::DBO();
        $STH = $DBH->prepare("
            SELECT * FROM calcs_profile_history
            WHERE user_id = :user_id
        ");

        $STH->execute([':user_id'=>$profile->user_id]);

        return CalcProfileHistories::createFromDBData($STH->fetchAll());
    }

}

/**
 * Class CalcProfileHistoriesException
 */
class CalcProfileHistoriesException extends CollectionException {}
