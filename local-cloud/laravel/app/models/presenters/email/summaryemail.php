<?php namespace Presenters;

use Profile,
    Boards,
    Pins,
    UserEmail,
    Caches\DomainDailyCounts,
    View;

use UserEmailException;

/**
 * Class SummaryEmail
 *
 * @package Presenters
 */
class SummaryEmail
{
    /**
     * @var UserEmail
     */
    protected $email;

    /**
     * You need to pass through the email object
     *
     * @author  Will
     *
     * @param $email
     */
    public function __construct(UserEmail $email)
    {
        $this->email = $email;
    }

    /**
     *
     * @author  Will
     *
     * @param $type
     *
     * @throws \UserEmailException
     * @return View
     */
    public function render($type)
    {
        /**
         * @var $profile \Profile
         */
        $profile = $this->email->profiles()->first();

        $latest = $profile->findCalculationOn($this->email->send_at);

        if (!$latest) {

            /*
             * Uses the Pinterest API to grab boards
             * stores them in the database
             */
//            $boards = $profile->getAPIBoards();
//            /** @var $boards Boards */
//            $boards->setPropertyOfAllModels('track_type', $profile->track_type);
//            $boards->insertUpdateDB();

            /*
             * Uses the boards from the API (above), finds which one the username owns,
             * finds the pins of those boards via the API
             * and then saves those to the DB
             */
//            $pins = $profile->getAPIPinsFromOwnedBoards();
//            /** @var $pins Pins */
//            $pins->setPropertyOfAllModels('track_type', $profile->track_type);
//            $pins->insertUpdateDB();

            $latest = $profile->calculateProfileHistory();

            /*
             * Save the calc to the DB
             */
            $latest->insertUpdateDB();
        }

        switch ($this->email->email_name) {

            default:

                throw new UserEmailException(
                    'No email type ' . $this->email->email_name
                );

            case UserEmail::DAILY_SUMMARY:

                $frequency          = 'Daily';
                $duration_statement = 'yesterday';
                $time_slug          = '1 day ago';
                $frequency_slug     = 'day';

                break;

            case UserEmail::WEEKLY_SUMMARY:

                $frequency          = 'Weekly';
                $duration_statement = 'last week';
                $time_slug          = '1 week ago';
                $frequency_slug     = 'week';

                break;

            case UserEmail::MONTHLY_SUMMARY:

                $frequency          = 'Monthly';
                $duration_statement = 'last month';
                $time_slug          = '1 month ago';
                $frequency_slug     = 'month';

                break;
        }

        $offset_timestamp = strtotime($time_slug, $this->email->send_at) + 1;

        $google_analytics_tracking =
            '?utm_source=' . $frequency .
            '_summary&utm_medium=email&utm_campaign=SummaryEmail';

        $offset = $profile->findCalculationBefore($offset_timestamp);

        if (!$offset OR !$latest) {
            throw new UserEmailException(
                'The calculations could not be found and were not created.'
            );
        }

        if ($offset->date === $latest->date) {

            throw new UserEmailException(
                'This summary email is trying to calculate the difference' .
                ' between the same dates. It will always say 0'
            );
        }

        $timeframe
            = date('l, F jS, Y', $offset->date)
            . ' to ' .
            date('l, F jS, Y', $latest->date);

        /*
        |--------------------------------------------------------------------------
        | Organic Pins
        |--------------------------------------------------------------------------
        */
        $new_domain_pins = 0;
        $domains         = '';


        /*
         * BARGH - You have to cast this as an integer to make sure its not treated like
         * a username. I know this is awful code and honestly I'm sorry. Again.
         */
        $user_account = $this->email->customer()->getUserAccount((int)$profile->user_id);

        if ($user_account) {
            foreach ($user_account->domains() as $domain) {

                /*
                 * Since there is a partial date for today's calc, we use the flat
                 * date (1AM EST, 12AM CST) to make sure we are using only full calcs
                 * The (-1) is to ensure it's the latest point that isn't today
                 */
                $new_domain_pins =
                    DomainDailyCounts::sumDuring(
                                     $domain->domain, 'pin_count',
                                     flat_date('day', $offset_timestamp) - 1,
                                     flat_date('day', $this->email->send_at) - 1);

                $domains .= $domain->domain . ', ';

            }
        }
        $domains = rtrim($domains, ', ');

        if ($domains == '') {
            $domain_button = '<a href="http://analytics.tailwindapp.com/pins/domain/trending' .
                $google_analytics_tracking
                . '">Add a domain to track organic pins</a>';
        } else {
            $domains = 'from ' . $domains;
        }


        /*
        |--------------------------------------------------------------------------
        | Followers
        |--------------------------------------------------------------------------
        */
        $new_followers   = $latest->follower_count - $offset->follower_count;
        $total_followers = $latest->follower_count;

        if ($new_followers == 0) {
            $followers_statement = 'No new followers';
        } elseif ($new_followers < 0) {
            $lost                = abs($new_followers);
            $followers_statement = "No new followers";
        } else {
            $followers_statement = "$new_followers new followers";
        }

        /*
        |--------------------------------------------------------------------------
        | Repins
        |--------------------------------------------------------------------------
        */
        $new_repins   = $latest->repin_count - $offset->repin_count;
        $total_repins = $latest->repin_count;

        if ($new_repins == 0) {
            $repin_statement = "no new repins";
        } elseif ($new_repins < 0) {
            $lost            = abs($new_repins);
            $repin_statement = "no new repins";
        } else {
            $repin_statement = "$new_repins new repins";
        }

        /*
        |--------------------------------------------------------------------------
        | Analysis
        |--------------------------------------------------------------------------
        */
        /**
         *  The pieces of the email we're throwing in
         *
         * @var $sections array
         *
         */
        $sections = array(
            'followers'    => $new_followers,
            'repins'       => $new_repins,
            'organic_pins' => $new_domain_pins
        );

        arsort($sections);

        /*
        |--------------------------------------------------------------------------
        | Headlines
        |--------------------------------------------------------------------------
        */
        /*
         * If they are all 0, then show the "nothing" stats
         */
        if (
            $new_repins <= 0
            AND $new_followers <= 0
            AND $new_domain_pins <= 0
        ) {
            $headline =
                ucfirst($duration_statement)
                . ' was a slow one for '
                . $profile->username;

            $sub_headline         = 'Log in for tips on how to increase your metrics';
            $headline_button_text = 'Log in';


        } else {
            switch (key($sections)) {
                case 'followers':
                    $headline =
                        $this->email->profiles()->first()->username .
                        ' gained <span style="color:#AEDCEE">' .
                        number_format($new_followers) .
                        '</span> new followers ' .
                        $duration_statement;

                    $sub_headline = 'that brings you to <span style="color:#AEDCEE">' .
                        number_format($total_followers) .
                        '</span> total followers';

                    $headline_button_text = 'See more';

                    break;

                case 'repins':

                    $headline =
                        $this->email->profiles()->first()->username . "'s pins were " .
                        'repinned <span style="color:#AEDCEE"> ' .
                        number_format($new_repins) .
                        '</span> times ' .
                        $duration_statement;

                    $sub_headline = 'that brings you to <span style="color:#AEDCEE">' .
                        number_format($total_repins) .
                        '</span> total repins';

                    $headline_button_text = 'See more';

                    break;

                case 'organic_pins':

                    $headline =
                        'There were ' .
                        '<span style="color:#AEDCEE">' .
                        $new_domain_pins .
                        '</span> new pins pinned ' .
                        $duration_statement;

                    $sub_headline = $domains;

                    $headline_button_text = 'See more';

                    break;
            }

        }
        /*
        |--------------------------------------------------------------------------
        | Analysis
        |--------------------------------------------------------------------------
        */
        $followers_analysis                 = $profile->followerGrowthAnalysis($new_followers, $frequency_slug);
        $followers_analysis['time_context'] = $duration_statement;

        $followers_blurb = View::make('shared.analysis.follower_growth', $followers_analysis) .
            ' Make sure to log in to your account and connect with some of your new fans!';

        $repins_analysis
            = $profile->repinGrowthAnalysis($new_repins, $frequency_slug);

        $repins_analysis['time_context'] = $duration_statement;
        $repins_analysis['username']     = $profile->username;

        $repins_blurb =
            View::make('shared.analysis.repin_growth', $repins_analysis) .
            'Make sure to check out your Tailwind pin inspector to get even more repins!';

        if ($domains == '') {
            $organic_pins_blurb = 'You really should ' .
                '<a href="http://analytics.tailwindapp.com/pins/domain/trending'
                . $google_analytics_tracking .
                '">add a domain to track organic pins</a>.' .
                'Otherwise, this will always say 0 and we will all be sad :(';
        } else {
            $organic_pins_blurb =
                'There were ' . number_format($new_domain_pins) .
                ' pins pinned ' . $domains . ' ' .
                $duration_statement . ' Check out the website tab in the app' .
                ' to see more information about who is pinning from your domain!';
        }


        $section_vars = array(
            'followers_new'             => number_format($new_followers),
            'followers_total'           => number_format($total_followers),
            'followers_blurb'           => $followers_blurb,
            'repins_new'                => number_format($new_repins),
            'repins_total'              => number_format($total_repins),
            'repins_blurb'              => $repins_blurb,
            'organic_pins_new'          => number_format($new_domain_pins),
            'organic_pins_blurb'        => $organic_pins_blurb,
            'google_analytics_tracking' => $google_analytics_tracking,
            'timeframe'                 => $timeframe
        );

        $main_body_vars = array();


        foreach ($sections as $id => $amount) {
            $main_body_vars['sections'][] = View::make('shared.emails.html.summary.' . $id, $section_vars);
        }

        $main_body_vars['timeframe'] = $timeframe;

        /*
        |--------------------------------------------------------------------------
        | HTML response
        |--------------------------------------------------------------------------
        */

        $main_body = View::make('shared.emails.html.summary.summary', $main_body_vars);

        $template_vars = array(
            'title'                    => $frequency . ' Summary for ' . $profile->username,
            'first_line'               => 'Your ' . strtolower($frequency) . ' summary: ' . $followers_statement . ', ' . $repin_statement . ', and ' . $new_domain_pins . ' new pins from your domain!',
            'unsubscribe_link'         => 'http://analytics.tailwindapp.com/notifications-off/' . $google_analytics_tracking,
            'update_subscription_link' => 'http://analytics.tailwindapp.com/settings/notifications' . $google_analytics_tracking,
            'headline'                 => $headline,
            'sub_headline'             => $sub_headline,
            'headline_button_text'     => $headline_button_text,
            'headline_button_link'     => 'http://analytics.tailwindapp.com/profile' . $google_analytics_tracking,
            'main_body'                => $main_body,

        );

        $template_vars = array_merge($template_vars, $main_body_vars);

        if ('plaintext' == $type) {
            return View::make('shared.emails.plaintext.free_summary', $template_vars);
        } else {
            return View::make('shared/emails/templates/zero', $template_vars);
        }
    }
}