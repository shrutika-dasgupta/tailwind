<?php namespace Models\Tailwind;

use
    Collections\Tailwind\Features,
    Models\Tailwind\FeatureTrait;

/**
 * Class Feature
 *
 * @property    $feature_id       int    10[]
 * @property    $name             varchar    50[]
 * @property    $value            varchar    50[]
 * @property    $added_at         int    11[]
 * @property    $updated_at       int    11[]
 *
 * @package Models
 */
class Feature extends EloquentModel
{
    use FeatureTrait;

    /**
     * The degrees of specificity or level indicating where
     * a feature was set, listed in order of default priority
     */
    const SPECIFICTY_USER        = 'user';
    const SPECIFICTY_ORG         = 'org';
    const SPECIFICTY_PLAN        = 'plan';
    const SPECIFICTY_LEGACY_PLAN = 'legacy_plan';
    const SPECIFICTY_DEFAULT     = 'default';
    /**
     * @var string the table name
     */
    public $table = 'features';
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
        'feature_id' => 'int',
        'name'       => 'varchar',
        'value'      => 'varchar',
        'added_at'   => 'int',
        'updated_at' => 'int',
    );
    /**
     * This is the auto-incrementing key associated with this table
     *
     * @var string
     */
    protected $primary_keys = ['feature_id'];

    public $specificity;

    /**
     * @author  Will
     */
    public function __construct($attributes = array())
    {
        if (
            $attributes instanceof UserFeature OR
            $attributes instanceof OrganizationFeature OR
            $attributes instanceof PlanFeature
        ) {
            $this->value = $attributes->value;
            $this->feature_id = $attributes->feature_id;

            /**
             * Since we are making this a feature from a user feature
             * or organization feature or plan feature we don't want
             * to accidentally save the value to the database - so we
             * guard the value. This means it can't be saved to the DB
             */
            $this->guard(['value']);
        }

        parent::__construct($attributes);
        $this->specificity = self::SPECIFICTY_DEFAULT;
        $this->added_at    = time();
        $this->updated_at  = time();
    }

    /**
     * @param array $models
     *
     * @return \Collections\Tailwind\Features
     */
    public function newCollection(array $models = array())
    {
        return new Features($models);
    }
}
