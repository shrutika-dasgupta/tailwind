<?php

/**
 * User account tag model.
 * 
 * @author Daniel
 */
class UserAccountTag extends PDODatabaseModel
{
    public $table = 'user_accounts_tags';

    public $columns = array(
        'account_id',
        'name',
        'topic',
        'created_at',
        'updated_at',
    );

    public $primary_keys = array('account_id', 'name', 'topic');

    public $account_id;
    public $name;
    public $topic;
    public $created_at;
    public $updated_at;

    /**
     * Gets an account's tags.
     *
     * @param UserAccount $account
     *
     * @return array
     */
    public static function tags(UserAccount $account)
    {
        $data = self::find(array(
            'account_id' => $account->account_id,
        ));

        $tags = array();
        foreach ($data as $tag) {
            $tags[$tag->name][] = $tag->topic;
        }

        ksort($tags);

        return $tags;
    }

    /**
     * Creates a new account tag.
     *
     * @param UserAccount $account
     * @param string      $name
     * @param string      $topic
     *
     * @return bool
     */
    public static function create(UserAccount $account, $name, $topic)
    {
        $validator = Validator::make(
            array(
                'name'  => $name,
                'topic' => $topic,
            ),
            array(
                'name'  => 'required',
                'topic' => 'required',
            )
        );

        if ($validator->fails()) {
            return false;
        }

        $tag             = new self();
        $tag->account_id = $account->account_id;
        $tag->name       = $name;
        $tag->topic      = $topic;
        $tag->created_at = time();
        $tag->updated_at = time();
        $tag->insertUpdateDB();

        return true;
    }

    /**
     * Deletes an account tag.
     *
     * @param UserAccount $account
     * @param string      $name
     *
     * @return bool
     */
    public static function delete(UserAccount $account, $name)
    {
        $validator = Validator::make(
            array('name' => $name),
            array('name' => 'required')
        );

        if ($validator->fails()) {
            return false;
        }

        $query = "DELETE FROM user_accounts_tags
                  WHERE account_id = ? AND name = ?";
        
        $deleted = DB::delete($query, array($account->account_id, $name));
        if (!$deleted) {
            return false;
        }

        if ($user = User::getLoggedInUser()) {
            $user->recordEvent(
                UserHistory::REMOVE_TAG,
                array('name' => $name)
            );
        }

        return true;
    }
}

class UserAccountTagException extends DBModelException {}
