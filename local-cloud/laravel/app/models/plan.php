<?php

use
    Models\Tailwind\Feature,
    Models\Tailwind\PlanFeature,
    Collections\Tailwind\Features;


/**
 * Plan model.
 * 
 * @author Will
 * @author Daniel
 */
class Plan extends PDODatabaseModel
{
    const FREE_PLAN_ID        = 1;
    const LITE_PLAN_ID        = 2;
    const PRO_PLAN_ID         = 3;
    const AGENCY_PLAN_ID      = 4;
    const LEGACY_LITE_PLAN_ID = 5;
    const LEGACY_PRO_PLAN_ID  = 6;

    const FREE_NO_CC   = 'Free-no-credit-card';
    const FREE_WITH_CC = 'Free-with-credit-card';
    const LITE         = 'Lite';
    const PRO          = 'Pro';
    const AGENCY       = 'Agency';
    const LEGACY_PRO       = 'Pro-Legacy';
    const LEGACY_LITE       = 'Lite-Legacy';

    const KEYWORD_FREE_LIMIT = 1;
    const KEYWORD_LITE_LIMIT = 5;
    const KEYWORD_PRO_LIMIT  = 10;

    const DOMAIN_FREE_LIMIT = 1;
    const DOMAIN_LITE_LIMIT = 1;
    const DOMAIN_PRO_LIMIT  = 1;

    public $table = 'plans';

    public $columns = array(
        'plan_id',
        'name',
        'chargify_plan_id'
    );

    public $primary_keys = array('plan_id');

    public $plan_id;
    public $name;
    public $chargify_plan_id;

    /**
     * The features of a given plan, cached
     *
     * @var $_features Features
     *
     */
    protected
        $_features,
        $_is_legacy = false;

    /**
     * Build a plan based on the chargify ID
     *
     * @param $id
     *
     * @return \Plan
     */
    public static function findByChargfiyID($id)
    {
        $plan                   = new Plan();
        $plan->chargify_plan_id = $id;
        switch ($id) {
            default:
            case Config::get('chargify.free_account_product_id'):
                $plan->plan_id = self::FREE_PLAN_ID;
                $plan->name    = self::FREE_WITH_CC;
                break;

            case Config::get('chargify.lite_account_product_id'):
                $plan->plan_id = self::LITE_PLAN_ID;
                $plan->name    = self::LITE;
                break;

            case Config::get('chargify.pro_account_product_id'):
                $plan->plan_id = self::PRO_PLAN_ID;
                $plan->name    = self::PRO;
                break;

            case Config::get('chargify.agency_account_product_id'):
                $plan->plan_id = self::AGENCY_PLAN_ID;
                $plan->name    = self::AGENCY;
                break;

        }

        return $plan;
    }

    /**
     * Find the plan
     *
     * @author  Will
     *
     * @param $plan_id
     *
     * @throws PlanNotFoundException
     * @return Plan
     */
    public static function find($plan_id)
    {
        $DBH = DatabaseInstance::DBO();
        $STH = $DBH->prepare('select * from user_plans where id = :id');

        $STH->execute(array(
                           ':id' => $plan_id
                      ));

        $planFromDB = $STH->fetch();

        if ($STH->rowCount() == 0) {
            throw new PlanNotFoundException("Plan $plan_id not found");
        }

        $plan                   = new Plan();
        $plan->plan_id          = $planFromDB->id;
        $plan->chargify_plan_id = $planFromDB->chargify_plan_id;
        $plan->name             = $planFromDB->name;

        return $plan;
    }

    /**
     * Component type
     */
    public function componentSlug()
    {
        switch ($this->chargify_plan_id) {
            case 3319111:
                return Config::get('chargify.lite_account_component_id');
                break;

            case 3319112:
            case 3319114:
                return Config::get('chargify.pro_account_component_id');
                break;

            default:
                Log::error(
                   'Tried to find the component slug of a plan '.
                   'that wasnt in the list'
                );

                return Config::get('chargify.lite_account_component_id');
                break;
        }
    }

    /**
     * @author  Will
     */
    public function __construct() {
        $this->_features = new Features();
        parent::__construct();
    }

    /**
     * @param Models\Tailwind\Feature $feature
     *
     * @param bool    $search_recursively
     *                 If set to true, we'll look for the feature setting in
     *                  the organization and plan
     *
     * @return Models\Tailwind\Feature
     */
    public function getFeature($feature,$search_recursively = false)
    {
        if (!($feature instanceof Feature)) {
            $name = $feature;
            $feature = Feature::where('name' ,'=', $name)->get()->first();

            if (!$feature) {
                Log::warning("Looking for a feature that doesn't exist: $name");
                return false;
            }
        }

        if ($this->_features->has($feature->feature_id)) {
            Log::debug('Using feature cache for '.$feature->feature_id);

            return $this->_features->get($feature->feature_id);
        }

        $plan_id = $this->plan_id;

        if($this->_is_legacy) {
            $plan_id = $this->legacyPlanId();
        }

        $plan_feature =
            PlanFeature::where('plan_id','=',$plan_id)
                       ->where('feature_id','=',$feature->feature_id)
                       ->get()
                       ->first();

        if ($plan_feature) {

            $feature->value = $plan_feature->value;
            if ($this->_is_legacy) {
                $feature->specificity = Feature::SPECIFICTY_LEGACY_PLAN;
            }
            $feature->specificity = Feature::SPECIFICTY_PLAN;
            $this->_features->add($feature);

            return $feature;
        }

        if($search_recursively) {
            return $feature;
        }

        return false;
    }

    /**
     * Determines whether this plan has a given feature.
     *
     * @author Daniel
     * @author Will
     *
     * @param string | Feature $feature
     *
     * @throws Exception
     * @return bool
     */
    public function hasFeature($feature)
    {
        return $this->getFeature($feature,true)->isEnabled();
    }

    /**
     * Determines the limit for a feature of this plan.
     * 
     * @author Daniel
     * @author Will
     *
     * @param Feature | string $feature
     *
     * @return int
     */
    public function maxAllowed($feature)
    {
        return $this->getFeature($feature,true)->maxAllowed();
    }

    /**
     * @param $feature
     *
     * @return bool|int
     */
    public function featureValue($feature) {
        return $this->getFeature($feature,true)->value;
    }

    /**
     * @author  Will
     *
     * @param $data
     *
     * @return $this|void
     */
    public function loadDBData($data)
    {
        $this->plan_id          = $data->id;
        $this->name             = $data->name;
        $this->chargify_plan_id = $data->chargify_plan_id;
    }

    /**
     * Based on the plan, get the keyword limit
     *
     * @author  Will
     */
    public function keywordLimit()
    {
        switch ($this->plan_id) {
            default:
                return self::KEYWORD_FREE_LIMIT;
                break;

            case self::LITE_PLAN_ID:
                return self::KEYWORD_LITE_LIMIT;
                break;

            case self::PRO_PLAN_ID:
            case self::AGENCY_PLAN_ID:
                return self::KEYWORD_PRO_LIMIT;
                break;
        }
    }

    /**
     * Based on the plan, get the domain limit
     *
     * @author  Will
     */
    public function domainLimit()
    {
        switch ($this->plan_id) {
            default:
                return self::DOMAIN_FREE_LIMIT;
                break;

            case self::LITE_PLAN_ID:
                return self::DOMAIN_LITE_LIMIT;
                break;

            case self::PRO_PLAN_ID:
            case self::AGENCY_PLAN_ID:
                return self::DOMAIN_PRO_LIMIT;
                break;
        }
    }

    /**
     * Tells the plan that it should go by it's legacy settings, not
     * the up to date ones
     *
     * @author  Will
     *
     */
    public function useLegacy() {
        $this->_is_legacy = true;
        return $this;
    }

    /**
     * @return bool|int
     */
    public function legacyPlanId(){
        if($this->plan_id == self::LITE_PLAN_ID) {
            return self::LEGACY_LITE_PLAN_ID;
        }

        if($this->plan_id == self::PRO_PLAN_ID) {
            return self::LEGACY_PRO_PLAN_ID;
        }

        if($this->plan_id == self::AGENCY_PLAN_ID) {
            return self::AGENCY_PLAN_ID;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getName() {
        switch ($this->plan_id) {
            case self::FREE_PLAN_ID:
                return 'Free';
                break;
            case self::LITE_PLAN_ID:
                return 'Lite';
            case self::PRO_PLAN_ID:
                return ' Pro';
                break;
            case self::AGENCY_PLAN_ID:
                return 'Enterprise';
                break;
        }
        return $this->name;
    }

}

class PlanException extends DBModelException {}

class PlanNotFoundException extends PlanException {}

class PlanFeaturesNotFoundException extends PlanException {}
