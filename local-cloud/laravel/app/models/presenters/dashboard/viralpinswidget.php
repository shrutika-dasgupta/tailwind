<?php namespace Presenters\Dashboard;

use View,
    Log;

/**
 * Class Widget
 *
 * @package Presenters\Dashboard
 */
class ViralPinsWidget extends Widget implements WidgetInterface
{
    /**
     * The most viral pins
     *
     * @var \Pins
     */
    protected $viral_pins;

    /**
     * @author Will
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        $vars = array(
            'pins' => $this->viral_pins
        );

        return View::make($this->viewPath(), $vars);
    }

    /**
     * @author Will
     */
    public function byMostRepins()
    {

        $this->viral_pins =
            $this->user_account
                ->profile()
                ->mostRepinnedPins(3, $this->reference_time);

        $this->setViewName('viral_repins');

        return $this;
    }

    /**
     * @author Will
     */
    public function byMostLikes()
    {

        $this->viral_pins =
            $this->user_account
                ->profile()
                ->mostLikedPins(3, $this->reference_time);

        $this->setViewName('viral_likes');

        return $this;
    }
}