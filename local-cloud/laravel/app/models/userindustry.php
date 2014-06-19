<?php
/**
 * Class UserIndustry
 * @author  Will
 */
class UserIndustry extends PDODatabaseModel
{
    /*
    |--------------------------------------------------------------------------
    | Magic
    |--------------------------------------------------------------------------
    */
    /**
     * Makes sure the industry is printed instead of the array
     * @return string
     */
    public function __toString() {
        return $this->industry;
    }

    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */

    public
        $industry_id,
        $industry;
    public
        $table = 'user_industries',
        $columns = array('industry_id', 'industry'),
        $primary_keys = array('industry_id');

}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
*/
class UserIndustryException extends DBModelException {}

class UserIndustryNotFoundException extends UserIndustryException {}
