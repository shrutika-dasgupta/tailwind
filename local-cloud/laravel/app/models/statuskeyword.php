<?php

/**
 * Status keyword model.
 * 
 * @author Daniel
 */
class StatusKeyword extends PDODatabaseModel
{
    public $table = 'status_keywords';

    public $columns = array(
        'keyword',
        'last_pulled',
        'last_pulled_boards',
        'last_calced',
        'last_calced_wordcloud',
        'pins_per_day',
        'track_type',
        'added_at',
        'timestamp',
    );

    public $primary_keys = array('keyword');

    public $keyword;
    public $last_pulled;
    public $last_pulled_boards;
    public $last_calced;
    public $last_calced_wordcloud;
    public $pins_per_day;
    public $track_type;
    public $added_at;
    public $timestamp;

    /**
     * Initializes the class.
     *
     * @author Will
     *
     * @return \StatusKeyword
     */
    public function __construct()
    {
        $this->last_calced           = 0;
        $this->last_pulled           = 0;
        $this->last_pulled_boards    = 0;
        $this->last_calced_wordcloud = 0;
        $this->added_at              = time();
        $this->timestamp             = time();

        parent::__construct();
    }

    /**
     * Returns the regex match pattern for a keyword.
     *
     * @param string $keyword
     *
     * @return string
     */
    public static function regexMatchPattern($keyword)
    {
        // Match the plural form of keywords by default.
        $plural = '[s]?';

        // Match non-case-sensitive keyword forms by default.
        $modifiers = 'si';

        // Do not match plural forms of short keywords.
        if (strlen($keyword) <= 4) {
            $plural = '';
        }

        // Do not match plural forms of hashtags.
        if (strpos($keyword, '#') !== false) {
            $plural = '';
        }
        else if ($keyword == strtoupper($keyword)) {
            // EXACT match for all-caps keywords.
            $plural    = '';
            $modifiers = 's';
        }

        return "/([\s\"#])({$keyword}{$plural})([\s.,;'\"])/$modifiers";
    }

    /*
    |--------------------------------------------------------------------------
    | Instance Methods
    |--------------------------------------------------------------------------
    */

    /**
     * @author Yesh
     *
     * @param array $dont_update_these_columns
     * @param bool  $dont_log_error
     *
     * @return $this
     */
    public function insertUpdateDB($dont_update_these_columns = array(),$dont_log_error = false)
    {
        array_push($dont_update_these_columns,
                   array('last_calced', 'last_pulled', 'last_calced_wordcloud', 'last_pulled_boards', 'added_at'));

        $append = "ON DUPLICATE KEY UPDATE ";


        foreach ($this->columns as $column) {
                if(!in_array($column,$dont_update_these_columns)) {
                    $append .= "$column = VALUES($column),";
                }
        }

        return $this->saveModelsToDB('INSERT INTO',$append);
    }

    /**
     * @author Yesh
     *
     * @param string $insert_type
     * @param bool $appendedSQL
     * @param bool $dont_log_error
     * @return $this
     */
    public function saveModelsToDB(
        $insert_type = 'INSERT IGNORE INTO',
        $appendedSQL = false,
        $dont_log_error = false
    )
    {
        parent::saveModelsToDB($insert_type,
                               $appendedSQL,
                               $dont_log_error);
    }


}

class StatusKeywordException extends DBModelException {}
