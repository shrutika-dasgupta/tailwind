<?php

/**
 * Class PinHistory
 */
class PinHistory extends PDODatabaseModel {

    /*
    |--------------------------------------------------------------------------
    | Table Meta Data
    |--------------------------------------------------------------------------
    */
    public $columns = array(
        'pin_id',
        'user_id',
        'date',
        'repin_count',
        'like_count',
        'comment_count',
        'timestamp',
    ), $table = 'data_pins_history';

    public $primary_keys = array(
        'pin_id',
        'timestamp'
    );

    public
        $pin_id,
        $user_id,
        $date,
        $repin_count,
        $like_count,
        $comment_count,
        $timestamp;


    /**
     * Load from DB result
     *
     * @author  Alex
     */
//    public static function createFromDBData($data,$prefix='')
//    {
//        $class = get_called_class();
//
//        if (empty($data)) {
//            $exception_class = $class . 'Exception';
//            throw new $exception_class('The dataset is empty to create a ' . $class);
//        }
//        /** @var $model PDODatabaseModel */
//        $model = new $class();
//
//        $model->loadDBData($data,$prefix);
//
//        return $model;
//    }

//    /**
//     * Load in DB data
//     *
//     * @author  Alex
//     */
//    public function loadDBData($data,$prefix='')
//    {
//
//        foreach ($this->columns as $column) {
//            if($prefix == "pin_history_prefix"){
//                if($column == "repin_count" || $column == "like_count" || $column == "comment_count" || $column == "timestamp"){
//                    $column_name = "history_" . $column;
//                    $data_name = $column_name;
//                    $this->$column = $data->$data_name;
//                } else {
//                    $data_name = $column;
//                    $this->$column = $data->$data_name;
//                };
//            } else {
//                $data_name = $prefix.$column;
//                $this->$column = $data->$data_name;
//            }
//        }
//
//        return $this;
//    }

}