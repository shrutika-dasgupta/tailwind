<?php namespace Models\Tailwind;

use
    \Collections\Tailwind\UserFeatures;

/**
 * Class User
 *
 * @property    $cust_id          int    11[]
 * @property    $feature_id       int    11[]
 * @property    $value            varchar    50[]
 * @property    $added_at         int    11[]
 * @property    $updated_at       int    11[]
 *
 * @package Models
 */
class UserFeature extends EloquentModel
{
    use FeatureTrait;

    /**
     * @var string the table name
     */
    public $table = 'user_features';
    /**
     * The user table is auto incrementing
     *
     * @var bool
     */
    public $incrementing = false;
    /**
     * The columns in the database for the user object
     *
     * @var array
     */
    protected $columns = array(
        'cust_id'    => 'int',
        'feature_id' => 'int',
        'value'      => 'varchar',
        'added_at'   => 'int',
        'updated_at' => 'int',
    );
    /**
     * This is the auto-incrementing key associated with this table
     *
     * @var string
     */
    protected $primary_keys = ['cust_id', 'feature_id',];

    /**
     * @author  Will
     *
     * @param array $attributes
     */
    public function __construct($attributes = array())
    {
        $this->updated_at = time();
        $this->added_at   = time();

        return parent::__construct($attributes);
    }

    /**
     * @param array $models
     *
     * @return \Collections\Tailwind\UserFeatures
     */
    public function newCollection(array $models = array())
    {
        return new UserFeatures($models);
    }
}
