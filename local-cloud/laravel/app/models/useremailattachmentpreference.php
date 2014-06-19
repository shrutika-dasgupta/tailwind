<?php

/**
 * User email attachment preferences
 * Represents a set of preferences for email attachements
 *
 * @author  Will
 */
class UserEmailAttachmentPreference extends PDODatabaseModel
{
    /*
    |--------------------------------------------------------------------------
    | Constants
    |--------------------------------------------------------------------------
    */
    const PDF  = 'pdf';
    const CSV  = 'csv';
    const BOTH = 'both';
    const NONE = 'none';

    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    */
    public static $defaults = array(

        'profile-7'  => array(
            'type' => UserEmailAttachmentPreference::NONE
        , 'title'  => 'Profile last 7 days'
        ),

        'profile-14' => array(
            'type' => UserEmailAttachmentPreference::PDF
        , 'title'  => 'Profile last 14 days'
        ),

        'profile-30' => array(
            'type' => UserEmailAttachmentPreference::CSV
        , 'title'  => 'Profile last 30 days'
        ),

        'boards-7'   => array(
            'type' => UserEmailAttachmentPreference::BOTH
        , 'title'  => 'Boards last 7 days'
        ),

    );

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
    | Fields
    |--------------------------------------------------------------------------
    */
    public $csv_checked = '', $pdf_checked = '', $title;
    public
        $cust_id,
        $username,
        $user_id,
        $name,
        $type,
        $created_at,
        $updated_at;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    /**
     * @author  Will
     */
    public function __construct()
    {
        parent::__construct();
        $this->created_at = time();
        $this->updated_at = time();
    }

    /*
    |--------------------------------------------------------------------------
    | Static methods
    |--------------------------------------------------------------------------
    */

    /**
     * Create the set of preferences
     *
     * @author   Will
     *
     * @param User $user
     * @param      $name
     * @param      $type
     *
     * @internal param $attachment
     * @return UserEmailAttachmentPreference
     */
    public static function add(User $user, $name, $type)
    {
        $preference          = new UserEmailAttachmentPreference();
        $preference->cust_id = $user->cust_id;
        $preference->name    = $name;
        $preference->type    = $type;

        $preference->setCheckedValue();

        $preference->saveToDB();

        return $preference;
    }

    /**
     * Loads from a DB object into a model and sets the checked value
     *
     * @author  Will
     *
     * @param $data
     *
     * @return UserEmailAttachmentPreference
     */
    public static function loadFromDBData($data)
    {
        $preference = parent::createFromDBData($data);
        $preference
            ->setCheckedValue()
            ->setTitle();

        return $preference;
    }

    /*
    |--------------------------------------------------------------------------
    | Instance Methods
    |--------------------------------------------------------------------------
    */

    /**
     * @param string $statement_type
     * @param bool   $append
     *
     * @return $this
     */
    public function saveToDB($statement_type = 'INSERT INTO', $append = false)
    {
        $this->updated_at = time();

        if (!$append) {
            $append = '
                ON DUPLICATE KEY UPDATE
				type = VALUES(type),
				updated_at = VALUES(updated_at)
            ';
        }

        return parent::saveToDB($statement_type, $append);
    }

    /**
     * Sets the value of
     * pdf_checked
     * csv_checked
     *
     * @author  Will
     *
     * @return $this
     */
    public function setCheckedValue()
    {
        switch ($this->type) {

            case self::BOTH:
                $this->pdf_checked = 'checked';
                $this->csv_checked = 'checked';
                break;

            case self::PDF:
                $this->pdf_checked = 'checked';
                $this->csv_checked = '';
                break;

            case self::CSV:
                $this->csv_checked = 'checked';
                $this->pdf_checked = '';
                break;

            default:
            case self::NONE:
                $this->csv_checked = '';
                $this->pdf_checked = '';
                break;

        }

        return $this;
    }

    /**
     * Looks through the defaults and finds the title based on the name
     * of the attachment preference
     */
    public function setTitle()
    {
        $defaults = UserEmailAttachmentPreference::$defaults;

        $this->title = $defaults[$this->name]['title'];

        return $this;
    }
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
*/
class UserEmailAttachmentPreferenceException extends DBModelException {}
