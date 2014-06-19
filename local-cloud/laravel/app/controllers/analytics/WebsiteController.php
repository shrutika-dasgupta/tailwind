<?php namespace Analytics;

use
    Log,
    Redirect,
    User,
    UserHistory,
    View;

/**
 * Class WebsiteController
 *
 * @package Analytics
 */
class WebsiteController extends BaseController
{
    /*
    |--------------------------------------------------------------------------
    | Layout info
    |--------------------------------------------------------------------------
    */
    protected $layout = 'layouts.analytics';

    /**
     * Construct
     *
     * @author  Will
     */
    public function __construct()
    {

        parent::__construct();

        Log::setLog(__FILE__, 'Reporting', 'Website_Report');
    }

    /**
     * /website/add
     */
    public function addDomain()
    {

        $vars = $this->baseLegacyVariables();

        /*
         * Redirect to upgrade page if feature not available
         */
        extract($vars);
        /** @var  $user \User */
        $user = $customer;
//        if(!$customer->hasFeature('nav_website')){
//            return Redirect::to("/upgrade?ref=website&plan=" . $customer->plan()->plan_id . "");
//        }

        //clean the domain
        $domain_input = strtolower($_POST['domain']);

        $url = parse_url($domain_input);
        if (isset($url['host'])) {
            $this_domain = $url['host'];
        } else {
            $pieces      = explode('/', $url['path']);
            $this_domain = $pieces[0];
        }

        if (substr($this_domain, 0, 3) == 'www') {
            $this_domain = substr($this_domain, 3);
        }

        $this_domain = ltrim($this_domain, '.');

        if ($_POST['page']) {
            if ($_POST['page'] == "listening") {
                $forward = '/discover';
            } else {
                $forward = "/pins/domain/trending";
            }
        }

        if ($this_domain) {

            $has_domain = false;
            $acc        = "select * from user_accounts_domains where account_id = '$cust_account_id'";
            $acc_res = mysql_query($acc, $conn) or die(mysql_error());
            while ($a = mysql_fetch_array($acc_res)) {
                $has_domain = true;
            }

            if ($has_domain) {
                //add domain to the user_accounts_domains table
                $insert = "update user_accounts_domains set domain = '$this_domain' where account_id = '$cust_account_id'";
                $resu   = mysql_query($insert, $conn);
            } else {
                //add domain to the user_accounts_domains table
                $insert = "insert into user_accounts_domains (account_id, domain) values ('$cust_account_id','$this_domain')";
                $resu   = mysql_query($insert, $conn);
            }


            //check to see if domain was changed
            $acc = "select * from user_accounts_domains where account_id = '$cust_account_id'";
            $acc_res = mysql_query($acc, $conn) or die(mysql_error());
            while ($a = mysql_fetch_array($acc_res)) {
                $new_domain = $a['domain'];
            }

            //if domain changed, return with success
            if ($new_domain == $this_domain) {

                $user->recordEvent(
                    UserHistory::ADD_ACCOUNT_DOMAIN,
                        $parameters = array(
                            'ip'=>ip(),
                            'domain' => $new_domain
                        )
                );

                return Redirect::to($forward)
                ->with('flash_message','Added!');

            } else {

                return Redirect::to($forward)
                    ->with('flash_message','There was an error');
            }

        }

        return Redirect::to($forward)
        ->with('flash_message','There was an error');
    }

    public function downloadWebsite($startDate, $endDate, $type)
    {

        echo $startDate . $endDate . $type;
    }

    /**
     * /website/{startDate}/{endDate}
     *
     * @param $startDate
     * @param $endDate
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function showWebsite($startDate, $endDate)
    {

        $vars = $this->baseLegacyVariables();
        extract($vars);

        /*
         * Redirect to upgrade page if feature not available
         */
        if(!$customer->hasFeature('nav_website')){
            $query_string = http_build_query(
                array(
                     'ref'  => 'website',
                     'plan' => $customer->plan()->plan_id
                )
            );
            header('location:/upgrade?'.$query_string);
            exit;
            //return Redirect::to('/upgrade?'.$query_string);
        }

        $this->layout_defaults['pre_body_close_includes']['gauge'] = 4;

        $this->layout_defaults['page']          = 'website';
        $this->layout_defaults['top_nav_title'] = 'Pins From ' . $cust_domain;
        $this->layout->top_navigation           = $this->buildTopNavigation();

        $this->layout->side_navigation = $this->buildSideNavigation('website');

        $this->layout->head .= View::make('analytics.components.head.gauge');

        $this->layout->main_content    = View::make('analytics.pages.website', $vars);

        $this->logged_in_customer->recordEvent(
                                 UserHistory::VIEW_REPORT,
                                 $parameters = array(
                                     'report' => 'website'
                                 )
        );

    }

    public function showWebsiteDefault()
    {

        $this->showWebsite('', '');
    }
}
