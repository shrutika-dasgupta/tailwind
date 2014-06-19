<?php namespace Models\Tailwind;

use
    \Collections\Tailwind\OrganizationFeatures;

/**
 * Class User
 *
 * @property    $org_id           int    11[]
 * @property    $feature_id       int    11[]
 * @property    $value            varchar    50[]
 * @property    $added_at         int    11[]
 * @property    $updated_at       int    11[]
 *
 * @package Models
 */
class OrganizationFeature extends EloquentModel
{
    use FeatureTrait;

    /**
     * @var string the table name
     */
    public $table = 'user_organization_features';
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
        'org_id'     => 'int',
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
    protected $primary_keys = ['org_id', 'feature_id',];

    /**
     * @author  Will
     */
    public function __construct()
    {
        $this->updated_at = time();
        $this->added_at   = time();
        parent::__construct();
    }

    /**
     * @param array $models
     *
     * @return \Collections\Tailwind\OrganizationFeatures
     */
    public function newCollection(array $models = array())
    {
        return new OrganizationFeatures($models);
    }
}
