<?php

class UserIndustries extends DBCollection
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */

    public
        $table = 'user_industries',
        $columns = array('industry_id', 'industry'),
        $primary_keys = array('industry_id');

    /**
     * @author  Will
     *
     * @param int $limit
     *
     * @return \UserIndustries
     */
    public static function all($limit = 50)
    {
        $DBH = DatabaseInstance::DBO();
        $STH = $DBH->query("
            select * from user_industries
            limit $limit
        ");

        $industries = new UserIndustries();
        foreach ($STH->fetchAll() as $row) {
            $model = UserIndustry::createFromDBData($row);
            $industries->add($model,$model->industry_id);
        }

        return $industries;
    }
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
*/
class UserIndustriesException extends CollectionException {}
