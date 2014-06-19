<?php namespace Presenters\Dashboard;

use View,
    UserAccount,
    Plan,
    Log;

/**
 * Class Widget
 *
 * @package Presenters\Dashboard
 */
class RepinsWidget extends Widget implements WidgetInterface
{
    /**
     * The number of new followers
     *
     * @var int
     */
    protected $new_repins;

    /**
     * @author  Will
     *
     * @param UserAccount  $user_account
     * @param \UserAccount $reference_time
     */
    public function __construct(UserAccount $user_account, $reference_time)
    {
        parent::__construct($user_account, $reference_time);

        $this->new_repins =
            $this->user_account->profile()
            ->newRepinsSince($this->reference_time);

        $this->setSentimentMetricProperty('new_repins');

        $this->setViewName('new_repins');
    }

    /**
     * The widget should return a string of HTML
     *
     * @return string
     */
    public function render()
    {
        $repins_analysis
            = $this->user_account->profile()
            ->repinGrowthAnalysis($this->new_repins, 'week');

        $repins_analysis['time_context'] = 'last week';
        $repins_analysis['username']
            = $this->user_account->profile()->username;

        $blurb= View::make('shared.analysis.repin_growth', $repins_analysis);

        $vars = array(
            'new_repins_count' => number_format($this->new_repins),
            'average_growth'   => $repins_analysis['average_growth'],
            'change_in_growth' => $repins_analysis['change_in_growth'],
            'total'            => $repins_analysis['total'],
            'repins_blurb'     => $blurb
        );

        if ($this->plan_id > Plan::FREE_PLAN_ID) {
            $vars['repinners'] = $this->user_account->profile()->topRepinners(2);
        }

        return View::make($this->viewPath(), $vars);
    }
}