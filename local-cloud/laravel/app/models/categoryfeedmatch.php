<?php

use Pinleague\Pinterest;

/**
 * Category Feed Match Model
 *
 * @author Yesh
 */
class CategoryFeedMatch extends PDODatabaseModel
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
     */

    public
        $table = 'category_notifications',
        $columns =
        array(
            'pin_id',
            'user_id',
            'domain',
            'via_pinner',
            'origin_pinner',
            'description',
            'match_type',
            'category_name',
            'completed_flag',
            'timestamp'
        ),
        $primary_keys = array('pin_id','category_name','match_type','timestamp');
    public
        $pin_id,
        $user_id,
        $domain,
        $via_pinner,
        $origin_pinner,
        $description,
        $match_type,
        $category_name,
        $completed_flag,
        $timestamp;


    /**
     * @author Yesh
     */
    public function __construct()
    {
        parent::__construct();
        $this->completed_flag = 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Public Instance Methods
    |--------------------------------------------------------------------------
     */
    /**
     * Load up pinterest data into trackcategory object
     *
     * @author  Yesh
     *
     * @param $data
     *
     * @throws Pinleague\PinterestException
     * @return $this
     */
    public function loadAPIData(CategoryFeedQueue $data)
    {
        $this->pin_id        = $data->pin_id;
        $this->user_id       = $data->user_id;
        $this->domain        = $data->domain;
        $this->via_pinner    = $data->via_pinner;
        $this->origin_pinner = $data->origin_pinner;
        $this->description   = $data->description;
        $this->match_type    = $data->match_type;
        $this->category_name = $data->category_name;
        $this->timestamp     = $data->timestamp;

        return $this;
    }
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
 */
class CategoryFeedMatchException extends DBModelException {}
