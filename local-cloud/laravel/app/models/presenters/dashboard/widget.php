<?php namespace Presenters\Dashboard;

use UserAccount,
    Plan;

/**
 * Class Widget
 *
 * @package Presenters\Dashboard
 */
abstract class  Widget
{

    /**
     * Sentiment helps us determine language used in the templates. We don't want to be
     * congratulating people for losing followers etc
     */
    const SENTIMENT_POSITIVE = 'positive';
    const SENTIMENT_NEGATIVE = 'negative';
    const SENTIMENT_NEUTRAL  = 'neutral';
    /**
     * The metric we should use to determine the sentiment
     */
    protected $sentiment_metric;
    /**
     * The name of the widget, used to get the address of the view template
     *
     * @var string
     */
    protected $view_name;
    /**
     * The size of the widget. Right now, just default
     *
     * @var string
     */
    protected $size = 'large';
    /**
     * The user account pertaining to this widget
     *
     * @var \UserAccount
     */
    protected $user_account;
    /**
     * The cached value of the user_account's plan for easy access
     *
     * @var string
     */
    protected $plan_id;
    /**
     * The point in time from which to create the widget
     *
     * @var int | string
     */
    protected $reference_time;
    /**
     * The address of the widget's view
     *
     * @var string
     */
    protected $base_view_dir = 'analytics/dashboard/widgets';
    /**
     * The priority of how the widgets will lenient load
     * Lower integers will load first. Templates will only load
     * lower than what they are, never the other way around.
     *
     * So there has to be a free positive for every module, even if it's
     * just a blank template
     *
     * This can also be overwritten per module to not include all the views
     *
     * @var array
     */
    protected $hierarchy = array(
        0 => 'pro/negative',
        1 => 'pro/neutral',
        2 => 'pro/positive',
        3 => 'lite/negative',
        4 => 'lite/neutral',
        5 => 'lite/positive',
        6 => 'free/negative',
        7 => 'free/neutral',
        8 => 'free/positive'
    );

    /**
     * @author   Will
     *
     * @param UserAccount $user_account
     * @param             $reference_time
     *
     * @internal param $name
     */
    public function __construct(UserAccount $user_account, $reference_time)
    {
        $this->user_account = $user_account;
        $this->plan_id = $this->user_account->organization()->plan;

        if (!is_numeric($reference_time)) {
            $reference_time = strtotime($reference_time);
        }

        $this->reference_time = $reference_time;
    }

    /**
     * Set the name of the view file
     *
     * @author  Will
     *
     * @param $name
     *
     * @return $this
     */
    public function setViewName($name)
    {
        $this->view_name = $name;

        return $this;
    }

    /**
     * Based on the plan and the "sentiment" of the
     * widget, gets the path of the view that should be used
     *
     * @author  Will
     */
    protected function viewPath()
    {


        if (is_null($this->view_name)) {
            throw new WidgetException('The view name has not been set');
        }

        switch ($this->plan_id) {

            default:

                $plan = 'free';
                break;

            case Plan::LITE_PLAN_ID:

                $plan = 'lite';
                break;

            case Plan::AGENCY_PLAN_ID:
            case Plan::PRO_PLAN_ID:

                $plan = 'pro';
                break;

        }

        return $this->findAvailableWidgetPath($this->getSentiment(), $plan);
    }

    /**
     * Finds the best available path based on the sentiment and plan
     *
     * @author   Will
     *
     * @param      $sentiment
     * @param      $plan
     *
     * @param bool $previous_slug
     *
     * @throws WidgetException
     * @return string
     */
    protected function findAvailableWidgetPath($sentiment, $plan, $previous_slug = false)
    {
        /*
         * The path finder is supposed to be somewhat leinant on what widget views are there
         * If the expected view isn't there it will work it's way up from Free to Pro
         * and down from Positive to Negative
         *
         * For example, if we want a negative pro widget but it does not exist we will look for a
         * negative lite widget; If that doesn't exist, we'll look for a negative free widget; if
         * THAT doesn't exist, we'll look for a pro neutral widget; if that doesn't exist, a lite
         * neutral; etc etc
         */
        $slug = "$plan/$sentiment";

        if (in_array($previous_slug,$this->hierarchy) == false AND $previous_slug) {
            throw new WidgetException("$this->view_name is not in the hierarchy");
        }

        if ($previous_slug == end($this->hierarchy)) {
            throw new WidgetException('There is no view for ' . $this->view_name." (Previous: $previous_slug)");
        }

        if ($previous_slug) {
            $slug_key = array_search($previous_slug, $this->hierarchy);
            $slug_key++;

            $slug = $this->hierarchy[$slug_key];
        }

        $path = "$this->base_view_dir/$this->size/$slug/$this->view_name";

        if (file_exists(app_path() . '/views/' . $path . '.php')) {
            return $path;
        }

        return $this->findAvailableWidgetPath($sentiment, $plan, $slug);
    }

    /**
     * In general, metrics over 0 are positive. We can overwrite this if need be
     * but this is the most sane default.
     *
     * @author  Will
     *
     * @return string
     */
    protected function getSentiment()
    {

        $metric = $this->getSentimentMetric();

        if ($metric == 0) {
            return self::SENTIMENT_NEUTRAL;
        }

        if ($metric < 0) {
            return self::SENTIMENT_NEGATIVE;
        }

        return self::SENTIMENT_POSITIVE;
    }

    /**
     * The default sentiment of a widget is positive
     * If there isn't a metric we want to return a positive number
     * and force some positivity
     *
     * @author  Will
     */
    protected function getSentimentMetric()
    {

        if (!is_numeric($this->sentiment_metric)) {
            return 1;
        }

        return $this->sentiment_metric;
    }

    /**
     * @author  Will
     *
     * @param $property_name
     */
    protected function setSentimentMetricProperty($property_name)
    {
        $this->sentiment_metric = $this->$property_name;
    }

    /**
     * If we use the small template, we need to set the size to small
     *
     * @author  Will
     */
    public function setSmall()
    {
        return $this->setSize('small');
    }

    /**
     * @author  Will
     *
     * @param $size
     *
     * @return $this
     */
    protected function setSize($size)
    {
        $this->size = $size;
        return $this;
    }

}

/**
 * Class WidgetException
 *
 * @package Presenters\Dashboard
 */
class WidgetException extends \Exception {}
