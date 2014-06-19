<?php namespace Analytics;

use Presenters\Dashboard\CommentsWidget,
    Presenters\Dashboard\FollowersWidget,
    Presenters\Dashboard\RepinsWidget,
    Presenters\Dashboard\TopBoardsWidget,
    Presenters\Dashboard\ViralPinsWidget,
    Presenters\Dashboard\DomainPinsWidget,
    Presenters\Dashboard\ListeningWidget;

use Presenters\Dashboard\TaskWidget;
use View,
    Redirect,
    Carbon\Carbon,
    Log;

/**
 * Class DashboardController
 *
 * @package Analytics
 */
class DashboardController extends BaseController
{

    protected $layout = 'layouts.analytics';

    /**
     * @author  Will
     */
    public function __construct()
    {
        parent::__construct();
        Log::setLog(__FILE__, 'Reporting', 'Dashboard');
    }

    /**
     * Shows the default profile page
     *
     * @author  Will
     */
    public function showDashboardDefault()
    {
        $this->showSingleAccountDashboard();
    }

    /**
     * /dashboard
     *
     * The dashboard MVP will just be an overview of all the reports, which we
     * we can use to highlight areas individual users could have benefit from.
     * The hope is that this increases engagement
     *
     * @author  Will
     */
    public function showSingleAccountDashboard()
    {


        if (!$this->logged_in_customer->hasFeature('dashboard_enabled')) {
            return Redirect::route('profile');
        }

        ini_set('memory_limit', '500M');

        /**
         * We can turn features on and off
         */
        if ($this->logged_in_customer->plan()-plan_id == 1) {
            $config = array(
                'left'  => array(
                    'new_repins'      => 1,
                    //'pulse'           => 1,
                    //'discover_stats' => 1,
                    'new_followers'        => 3,
                    'new_domain_pins' => 2,
                    //'most_repinned_boards' => 1,
                ),
                'right' => array(
                    'tasks'           => 0,
                    'most_repinned_boards' => 1,
                    //'pinterest_progress'   => 0,
                    'recent_comments'      => 2,
                    //'most_repinned_boards' => 1,
                    //'most_followed_boards' => 2,
                    //'new_owned_pins'       => 2,
                )
            );
        } else {
            $config = array(
                'left'  => array(
                    'new_repins'      => 1,
                    //'pulse'           => 1,
                    //'discover_stats' => 1,
                    'viral_repins'    => 3,
                    'new_domain_pins' => 2,
                    //'most_repinned_boards' => 1,
                ),
                'right' => array(
                    'tasks'           => 0,
                    'new_followers'        => 0,
                    'most_repinned_boards' => 1,
                    //'pinterest_progress'   => 0,
                    'recent_comments'      => 2,
                    //'most_repinned_boards' => 1,
                    //'most_followed_boards' => 2,
                    //'new_owned_pins'       => 2,
                )
            );
        }



        /**
         * This would be the time frame, but ignore it for now
         * we can play with this later, but right now it's 7 days ago
         *
         * This is still hard coded in some places, make sure we double check this if we
         * ever change it
         */
        $reference_time = strtotime('-7 days', flat_date('day'));
        //$previous_time = $this->logged_in_customer->getLastLogin('exclude current session');

        /**
         * The account we are using for a single account summary dashboard
         * one day we'll probably have a multi account dashboard
         */
        $account = $this->active_user_account;

        /**
         * This is where we will store each individual modules HTML before we store it in each columns
         * html string. I'm sorry this is as complicated as it is
         */
        $views = array();

        /**
         * This is where we'll store the html for the left and right columns (mentioned above)
         */
        $lcol_html = '';
        $rcol_html = '';

        /**
         * The active widgets
         */
        $active_widgets = array_keys(array_merge($config['left'], $config['right']));

        /*
        |--------------------------------------------------------------------------
        | Tasks
        |--------------------------------------------------------------------------
        */
        if (in_array('tasks', $active_widgets)) {

            if ($this->logged_in_customer->hasFeature('tasks_list_enabled')) {
                $task           = new TaskWidget($this->logged_in_customer, $reference_time);
                $views['tasks'] = $task->render();
            }
        }

        /*
         |--------------------------------------------------------------------------
         | Top Boards
         |--------------------------------------------------------------------------
         |
         */
        if (in_array('most_repinned_boards', $active_widgets)) {

            $top_boards                    = new TopBoardsWidget($account, $reference_time);
            $views['most_repinned_boards'] = $top_boards->byMostRepins()->setSmall()->render();
        }

        if (in_array('most_followed_boards', $active_widgets)) {

            $top_boards                    = new TopBoardsWidget($account, $reference_time);
            $views['most_followed_boards'] = $top_boards->byMostFollows()->render();

        }

        /*
         |--------------------------------------------------------------------------
         | Repins / Followers / Likes
         |--------------------------------------------------------------------------
         |
         */
        if (in_array('new_followers', $active_widgets)) {

            $follower_widget = new FollowersWidget(
                $account,
                $reference_time
            );

            $views['new_followers'] = $follower_widget->render();
        }

        if (in_array('new_repins', $active_widgets)) {

            $repins_widget       = new RepinsWidget($account, $reference_time);
            $views['new_repins'] = $repins_widget->render();

            Log::debug('New repins module created');
        }
        /*
         |--------------------------------------------------------------------------
         | Viral Pins
         |--------------------------------------------------------------------------
         */
        if (in_array('viral_repins', $active_widgets)) {

            $viral_pins            = new ViralPinsWidget($account, $reference_time);
            $views['viral_repins'] = $viral_pins->byMostRepins()->render();

            Log::debug('Viral repins module created');

        }

        if (in_array('viral_likes', $active_widgets)) {

            $viral_pins           = new ViralPinsWidget($account, $reference_time);
            $views['viral_likes'] = $viral_pins->byMostLikes()->render();

            Log::debug('Viral likes module created');

        }

        /*
        |--------------------------------------------------------------------------
        | Comments
        |--------------------------------------------------------------------------
        */
        if (in_array('recent_comments', $active_widgets)) {

            $comments                 = new CommentsWidget($account, $reference_time);
            $views['recent_comments'] = $comments->render();

            Log::debug('Comments module created');

        }

        /*
         |--------------------------------------------------------------------------
         | Organic pins
         |--------------------------------------------------------------------------
         |
         */
        if (in_array('new_domain_pins', $active_widgets)) {

            if ($account->domains()->count() == 0) {
                $views['new_domain_pins'] = View::make('analytics.dashboard.ads.add_domain');
            } else {

                $domain_widget            = new DomainPinsWidget($account, $reference_time);
                $views['new_domain_pins'] = $domain_widget->render();
            }
        }

        /*
         |--------------------------------------------------------------------------
         | Listening
         |--------------------------------------------------------------------------
         |
         */
        if (in_array('pulse', $active_widgets)) {

            $pulse_widget   = new ListeningWidget($account, $reference_time);
            $views['pulse'] = $pulse_widget->render();
        }

        if (in_array('discover_stats', $active_widgets)) {

            $pulse_widget   = new ListeningWidget($account, $reference_time);
            $views['discover_stats'] = $pulse_widget->render();
        }

        /*
         |--------------------------------------------------------------------------
         | GoogleAnalytics
         |--------------------------------------------------------------------------
         | Only show if they have it synced, obviously
         |
         */
        if ($this->logged_in_customer->organization()->hasGoogleAnalytics()) {

            /*
             * Check if the revenue since last calc is over 0, show widget with how much earned
             * from pinterest
             * link to traffic and revenue page
             */

            /*
             * Check if visits since last calc is over 0, show widget with how many visits
             * link to top pins or top pinners
             */

        }


        /*
        |--------------------------------------------------------------------------
        | Compile the Dashboard
        |--------------------------------------------------------------------------
        */
        asort($config['left']);
        foreach ($config['left'] as $key => $order) {
            $lcol_html .= $views[$key];
        }

        asort($config['right']);
        foreach ($config['right'] as $key => $order) {
            $rcol_html .= $views[$key];
        }

        $this->layout->body_id                  = 'dashboard';
        $this->layout_defaults['page']          = 'dashboard';
        $this->layout_defaults['top_nav_title'] = "Dashboard Overview - Last 7 Days";
        $this->layout->top_navigation           = $this->buildTopNavigation();

        $this->layout->side_navigation = $this->buildSideNavigation('dashboard');

        $vars = array(
            'left_column'  => $lcol_html,
            'right_column' => $rcol_html
        );

        $this->layout->last_calculated =
            'Last calculated ' .
            Carbon::createFromTimeStamp(
                  $account->profile()->getLastHistoryCalc()->timestamp
            )     ->diffForHumans();

        $this->layout->main_content = View::make('analytics.dashboard.dashboard', $vars);
        Log::info('Main content view created');
        Log::runtime();
    }
}
