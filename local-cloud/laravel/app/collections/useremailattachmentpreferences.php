<?php

class UserEmailAttachmentPreferences extends DBCollection
{
    /*
    |--------------------------------------------------------------------------
    | Table data
    |--------------------------------------------------------------------------
    */
    public $columns = array(
        'cust_id',
        'username',
        'user_id',
        'name',
        'type',
        'created_at',
        'updated_at'
    ),
        $table = 'user_email_attachment_preferences';

    /*
    |--------------------------------------------------------------------------
    | Static methods
    |--------------------------------------------------------------------------
    */
    /**
     * @author  Will
     *
     * @return UserEmailAttachmentPreferences
     */
    public static function defaultPreferences()
    {
        $preferences = new UserEmailAttachmentPreferences();

        foreach (UserEmailAttachmentPreference::$defaults as $name => $default) {

            $preference        = new UserEmailAttachmentPreference();
            $preference->name  = $name;
            $preference->type  = $default['type'];
            $preference->title = $default['title'];

            $preference->setCheckedValue();

            $preferences->add($preference, $name);

        }

        return $preferences;
    }

    /*
    |--------------------------------------------------------------------------
    | Instance methods
    |--------------------------------------------------------------------------
    */
    /**
     * @author   Will
     */
    public function saveModelsToDB($insert_type = 'INSERT INTO', $appended = false)
    {
        if (!$appended) {

            $appended = '
            ON DUPLICATE KEY UPDATE
			type = VALUES(type),
			updated_at = VALUES(updated_at)
            ';

        }

        parent::saveModelsToDB($insert_type, $appended);
    }
}