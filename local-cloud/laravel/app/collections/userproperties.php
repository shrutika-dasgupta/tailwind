<?php
/**
 * Class UserProperties
 */
class UserProperties extends DBCollection
{

    /*
    |--------------------------------------------------------------------------
    | Schema info
    |--------------------------------------------------------------------------
    */
    public
        $columns = array(
        'cust_id',
        'property',
        'count',
        'created_at',
        'updated_at'
    ),
        $table = 'user_properties',
        $primary_keys = array('cust_id', 'property');

    /**
     * @author  Will
     */
    public function toKeyValues()
    {
        $array = array();
        foreach ($this->models as $property) {
            $array[$property->property] = $property->count;
        }

        return $array;
    }
}