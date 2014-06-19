<?php

namespace Presenters;

use Pinleague\Timezone,
    View;

/**
 * Class TimezoneSelector
 *
 * @author Janell
 *
 * @package Presenters
 */
class TimezoneSelector
{
    /**
     * The input name.
     *
     * @var string
     */
    protected $name;

    /**
     * The input id.
     *
     * @var string
     */
    protected $id;

    /**
     * The input class.
     *
     * @var string
     */
    protected $class;

    /**
     * The hidden input timezone value. This is a PHP timezone identifier.
     *
     * @var string
     */
    protected $timezone;

    /**
     * The hidden input city value.
     *
     * @var string
     */
    protected $city;

    /**
     * The hidden input region value.
     *
     * @var string
     */
    protected $region;

    /**
     * The hidden input country value.
     *
     * @var string
     */
    protected $country;

    /**
     * The input value. This is a user-friendly city name.
     *
     * @var string
     */
    protected $display_value;

    /**
     * Whether the current local time should be shown.
     *
     * @var bool
     */
    protected $show_local_time;

    /**
     * A javascript callback to fire on successful timezone retrieval.
     *
     * @var string
     */
    protected $success_callback;

    /**
     * A javascript callback to fire on unsuccessful timezone retrieval.
     *
     * @var string
     */
    protected $error_callback;

    /**
     * Class constructor.
     *
     * @param string $name
     * @param string $id
     * @param string $class
     *
     * @return void
     */
    public function __construct($name, $id, $class)
    {
        $this->name            = $name;
        $this->id              = $id;
        $this->class           = $class;
        $this->show_local_time = false;
    }

    /**
     * Returns a new TimezoneSelector instance.
     *
     * @param string $name
     * @param string $id
     * @param string $class
     *
     * @return TimezoneSelector
     */
    public static function instance($name = 'timezone', $id = 'timezone', $class = 'input-xlarge')
    {
        return new self($name, $id, $class);
    }

    /**
     * Renders and returns the view for this component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $local_time = '';
        if ($this->show_local_time && $this->timezone) {
            $local_time = Timezone::instance($this->timezone)->getCurrentTime();
        }

        return View::make('analytics.components.timezone_selector', array(
            'name'             => $this->name,
            'id'               => $this->id,
            'class'            => $this->class,
            'timezone'         => $this->timezone,
            'city'             => $this->city,
            'region'           => $this->region,
            'country'          => $this->country,
            'display_value'    => $this->display_value,
            'success_callback' => $this->success_callback,
            'error_callback'   => $this->error_callback,
            'local_time'       => $local_time,
        ));
    }

    /**
     * Sets this TimezoneSelector's option to show the current local time.
     *
     * @param bool $show_local_time
     *
     * @return void
     */
    public function setShowLocalTime($show_local_time)
    {
        $this->show_local_time = $show_local_time;
    }

    /**
     * Sets this TimezoneSelector's success callback.
     *
     * @param string $success_callback
     */
    public function setSuccessCallback($success_callback)
    {
        $this->success_callback = $success_callback;
    }

    /**
     * Sets this TimezoneSelector's error callback.
     *
     * @param string $error_callback
     */
    public function setErrorCallback($error_callback)
    {
        $this->error_callback = $error_callback;
    }

    /**
     * Pre-fills input values for the passed User.
     *
     * @param User $user
     *
     * @return void
     */
    public function fillForUser(User $user)
    {
        $this->timezone = $user->getTimezone();
        $this->city     = $user->city;
        $this->region   = $user->region;
        $this->country  = $user->country;

        $formatted_city = '';

        if ($this->timezone) {
            $formatted_city = $this->city;
            if ($this->country) {
                if ($this->region
                    && ($this->country == 'US' || $this->country == 'United States')
                ) {
                    $formatted_city .= ', ' . $this->region;
                }

                $formatted_city .= ' - ' . $this->country;
            }
        }

        $this->display_value = $formatted_city;
    }
}