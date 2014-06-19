<?php

/**
 * Class UserSocialProperties
 *
 * @author Janell
 */
class UserSocialProperties extends Collection
{
    public $table = 'user_social_properties';

    public $columns = array(
        'cust_id',
        'type',
        'name',
        'value',
        'created_at',
        'updated_at',
    );

    public $primary_keys = array('cust_id', 'type', 'name');

    /**
     * Returns an array of user social property values.
     *
     * @author  Janell
     *
     * @return array
     */
    public function toKeyValues()
    {
        $array = array();
        foreach ($this->models as $social_property) {
            if (empty($array[$social_property->type])) {
                $array[$social_property->type] = array();
            }

            $array[$social_property->type][$social_property->name] = $social_property->value;
        }

        return $array;
    }
}