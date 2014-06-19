<?php namespace Presenters\Dashboard;

use View,
    UserAccount,
    Log;

/**
 * Class Widget
 *
 * @package Presenters\Dashboard
 */
class FollowersWidget extends Widget implements WidgetInterface
{
    /**
     * The number of new followers
     *
     * @var int
     */
    protected $new_follower_count;

    /**
     * @author  Will
     *
     * @param UserAccount  $user_account
     * @param \UserAccount $reference_time
     */
    public function __construct(UserAccount $user_account, $reference_time)
    {
        parent::__construct($user_account, $reference_time);

        $this->new_follower_count
            = $this->user_account->profile()->newFollowersSince($this->reference_time);

        $this->setSentimentMetricProperty('new_follower_count');

        $this->setViewName('new_followers');
    }

    /**
     * The widget should return a string of HTML
     *
     * @return string
     */
    public function render()
    {
        /**
         * This will be the number of pictures we will show in the view
         * if they have a bazillion followers we want to limit how many we show
         */
        $followers = $this->user_account->profile()
                                        ->getRecentDBFollowers(within_limit($this->new_follower_count, 5, 10));

        $followers_analysis = $this->user_account->profile()
                                                 ->followerGrowthAnalysis($this->new_follower_count, 'week');

        $followers_analysis['time_context'] = 'last week';
        $blurb                              = View::make('shared.analysis.follower_growth', $followers_analysis);

        $vars = array(
            'new_follower_count' => number_format($this->new_follower_count),
            'average_growth'     => $followers_analysis['average_growth'],
            'change_in_growth'   => $followers_analysis['change_in_growth'],
            'total'              => $followers_analysis['total'],
            'followers'          => $followers,
            'blurb'              => $blurb
        );

        return View::make($this->viewPath(), $vars);
    }


}