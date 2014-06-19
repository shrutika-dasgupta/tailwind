<?php namespace Analytics;

use DateTime,
    DatePeriod,
    DateInterval,
    Response,
    Redirect,
    Session,
    Exception,
    Pinleague\Pinterest,
    Input,
    User,
    Users,
    Log,
    Profile,
    DB,
    View;

/**
 * Class AjaxController
 *
 * @package Analytics
 */
class AjaxController extends BaseController
{

    protected $layout = 'layouts.ajax_response';

    /**
     * Checks whether a new user's dashboard is ready
     *
     * @author  Alex
     *
     * @var $customer /user
     */
    public function checkNewUserData()
    {
        $vars = $this->baseLegacyVariables();
        extract($vars);

        header("Content-Type: text/html");


        /** @var $customer /User */
        if($customer->isDashboardReady() > 0){
            return "<div class='status'>1</div>";
        } else {
            return "<div class='status'>0</div>";
        }
    }

    /**
     * Checks whether trending pins image processing is complete
     *
     * @author  Alex
     */
    public function checkImageProcessing()
    {
        $vars = $this->baseLegacyVariables();
        extract($vars);

        header("Content-Type: text/html");

        if ($customer->hasFeature('trending_latest_1000')) {
            $trending_limit = 1000;

        } else {
            if ($customer->hasFeature('trending_latest_500')) {
                $trending_limit = 500;

            } else {
                if ($customer->hasFeature('trending_latest_100')) {
                    $trending_limit = 100;

                } else {
                    if ($customer->hasFeature('trending_latest_50')) {
                        $trending_limit = 50;

                    } else {
                        $trending_limit = 50;

                    }
                }
            }
        }



        //check that pins actually exist for this domain
        $pins_count = 0;
        $acc2 = "select * from data_pins_new where domain='$cust_domain' limit $trending_limit";
        $acc2_res = mysql_query($acc2,$conn) or die(mysql_error(). __LINE__);
        while ($b = mysql_fetch_array($acc2_res)) {
            $pins_count++;
        }

        if($pins_count!=0){
            $pins_ready = true;
            $unready_count = 0;
            $acc2 = "select image_id from data_pins_new where domain='$cust_domain' order by created_at desc limit $trending_limit";
            $acc2_res = mysql_query($acc2,$conn) or die(mysql_error(). __LINE__);
            while ($b = mysql_fetch_array($acc2_res)) {
                $image_id = $b['image_id'];

                if($image_id=="" || $image_id==0){
                    $pins_ready = false;
                    $unready_count++;
                }
            }
        }

        $progress = number_format((($pins_count-$unready_count)/$pins_count),3)*100;

        if(($pins_ready && $pins_count!=0) || $progress==100){


            $response = "<div class='status'>1</div><div class='data'></div>";


        } else {

            if(isset($_GET['count'])){
                $counter = $_GET['count'];
            } else {
                $counter = 0;
            }

            $response = "<div class='status'>0</div><div class='data'><div style='z-index:5100002'><h2>Loading up your trending pins... </h2><hr><small class='muted'>may take up to 30 seconds</small><div class='progress progress-success progress-striped active'><div class='bar' style='width: $progress%'></div></div><hr></div></div>";

            if($counter > 7){
                if(!$pins_ready){
                    for ($i = 0; $i < ($trending_limit/50); $i++) {

                        $marker = $i*50;
                        //run the image recognition script
                        $process = "nohup /usr/bin/php ".base_path()."/engines/internal/image_process.php -a " . escapeshellarg($cust_domain) . " -b " . escapeshellarg($marker) . " > /dev/null 2>/dev/null & echo $!";

                        $pid = exec($process);
                    }
                }
            }
        }

        $this->layout->response = $response;
    }



    /**
     * Check username to see if a profile exists on Pinterest and return basic profile stats
     *
     * @author  Alex
     */
    public function checkUsername($username)
    {
        header("Content-Type: text/html");

        $cust_username = $username;

        $username_exists = false;

        if(!$username_exists){

            $pinterest = new Pinterest;

            try{
                $data = $pinterest->getProfileInformation($cust_username);

                if($data['code']==0){
                    $username_exists=true;
                    $profile = new Profile();
                    $profile->loadAPIData($data);
                    $profile->insertUpdateDB();

                    $cust_first_name = $profile->first_name;
                    $cust_last_name = $profile->last_name;
                    $cust_image = $profile->image;
                    $cust_about = $profile->about;
                    $cust_domain = $profile->domain_url;
                    $cust_board_count = number_format($profile->board_count,0);
                    $cust_pin_count = number_format($profile->pin_count,0);
                    $cust_like_count = number_format($profile->like_count,0);
                    $cust_follower_count = number_format($profile->follower_count,0);
                    $cust_following_count = number_format($profile->following_count,0);
                }
            }
            catch (Exception $e) {
                $username_exists = false;
            }
        }

        if($username_exists){

            $response = "<div class='status'>1</div>
                <div class='data' style='text-align:center;margin-bottom:20px;'>
                    <div class='alert alert-success'>Lookin' good!</div>
                        <div class='well' style='display:inline-block'><div>
                        <h2 style='margin-top:0px'>$cust_first_name $cust_last_name</h2>
                    </div>
                    <div class=''>
                        <img src='$cust_image'>
                    </div>
                    <div>
                        <div>
                            <div class='label pull-left' style='margin:10px 10px'>
                                <strong>$cust_board_count</strong> Boards
                            </div>
                            <div class='label pull-left' style='margin:10px 10px'>
                                <strong>$cust_pin_count</strong> Pins
                            </div>
                            <div class='label pull-left' style='margin:10px 10px'>
                                <strong>$cust_like_count</strong> Likes
                            </div>
                            <br>
                            <div class='label pull-left' style='margin:10px 10px'>
                                <strong>$cust_follower_count</strong> Followers
                            </div>
                            <div class='label pull-left' style='margin:10px 10px'>
                                <strong>$cust_following_count</strong> Following
                            </div>
                        </div>
                    </div>
                </div>
                <div class='clearfix'></div>
                <div class='clearfix'></div>
            </div>
            <div class='domain'>$cust_domain</div>
            <div class='first_name'>$cust_first_name</div>
            <div class='last_name'>$cust_last_name</div>";

        } else {

            $response = "<div class='status'>0</div>
                <div class='data' style='text-align:center'>
                    <div class='alert alert-error'>
                        <strong>Whoops!</strong><br>
                        <a href='http://pinterest.com/$cust_username'
                          target='_blank'>http://pinterest.com/$cust_username</a>
                          does not seem to exist on Pinterest!  Please check the URL of the
                          Pinterest profile you'd like to track.
                    </div>
                </div>";

        }

        $this->layout->response = $response;
    }

    /**
     * Get a pin's history for repins, likes and comments to display a sparkline in the
     * pin inspector page
     *
     * Requires a pin_id to be in the POST header
     *
     * @author  Alex
     */
    public function getPinHistory()
    {

        $vars = $this->baseLegacyVariables();
        extract($vars);

        header("Content-Type: text/html");

        try {

            if (!all_in_array($_POST, 'pin_id')
            ) {
                throw new RequiredVariableException('post variable not sent');
            }

            $pin_id = $_POST['pin_id'];

            /*
            * check that history actually exists for this pin
            */
            $history_count = 0;
            $pin_history = array();
            $acc2 = "select * from data_pins_history where pin_id = $pin_id order by date asc";
            $acc2_res = mysql_query($acc2,$conn) or die(mysql_error(). __LINE__);
            while ($b = mysql_fetch_array($acc2_res)) {

                $date = $b['date'];

                /*
                * Get initial date of history we have for this pin
                */
                if($history_count==0){
                    $start_date = $date;
                }

                $pin_history["$date"]                  = array();
                $pin_history["$date"]['date']          = $date;
                $pin_history["$date"]['chart_date']    = date("Y-m-d",$date);
                $pin_history["$date"]['repin_count']   = $b['repin_count'];
                $pin_history["$date"]['like_count']    = $b['like_count'];
                $pin_history["$date"]['comment_count'] = $b['comment_count'];
                $pin_history["$date"]['timestamp']     = $b['timestamp'];

                $history_count++;
            }

            $current_date = getFlatDate(time());



            /*
             * Fill in historical data for dates where there were no changes
             */
            if(count($pin_history) > 1){
                $last_repins   = $pin_history[$start_date]['repin_count'];
                $last_likes    = $pin_history[$start_date]['like_count'];
                $last_comments = $pin_history[$start_date]['comment_count'];

                $start = new DateTime();
                $end   = new DateTime();

                $start->setTimestamp($start_date);
                $end->setTimestamp($current_date);

                $period = new DatePeriod($start, new DateInterval('P1D'), $end);

                $period_counter        = 0;
                foreach ($period as $k => $dt) {

                    $dtt = $dt->getTimestamp();

                    if(!$pin_history["$dtt"]){

                        $pin_history["$dtt"] = array();
                        $pin_history["$dtt"]['date']          = $dtt;
                        $pin_history["$dtt"]['chart_date']    = date("Y-m-d",$dtt);
                        $pin_history["$dtt"]['repin_count']   = $last_repins;
                        $pin_history["$dtt"]['like_count']    = $last_likes;
                        $pin_history["$dtt"]['comment_count'] = $last_comments;
                    }

                    $last_repins   = $pin_history[$dtt]['repin_count'];
                    $last_likes    = $pin_history[$dtt]['like_count'];
                    $last_comments = $pin_history[$dtt]['comment_count'];
                }

                ksort($pin_history);

                $repin_count = "";
                foreach($pin_history as $d => $v){
                    $repin_count .= "[".$v['repin_count'].",".$v['like_count'].",".$v['comment_count']."],";
                }

                $repin_count = rtrim($repin_count,",");
            }

            if(count($pin_history) <= 1){
                $response = "";
            } else {

                $current_date_ts = strtotime("-1 day",$current_date)*1000;
                $start_date_ts   = strtotime("-1 day",$start_date)*1000;
                $response = "
                    <div class='status'>1</div>
                    <div class='repins'>$repin_count</div>
                    <div class='current-date'>$current_date_ts</div>
                    <div class='start-date'>$start_date_ts</div>";
            }

        }
        catch (Exception $e) {
            $response = "";
        }

        $this->layout->response = $response;
    }

    /**
     * Get a user's boards in a certain category from a click on a section of the category
     * footprint bar in the influencers section
     *
     * @author  Alex
     *
     * @param $category
     * @param $username
     *
     * @throws AjaxException
     * @throws RequiredVariableException
     */
    public function getCategoryBoards($category, $username)
    {

        $vars = $this->baseLegacyVariables();
        extract($vars);

        header("Content-Type: text/html");

        try {

            if (empty($category) || (empty($username))
            ) {
                throw new RequiredVariableException('post variable not sent');
            }

            $query = "select user_id from data_profiles_new where username = ?";
            $user_id = DB::select($query,array($username));

            $user_id = $user_id[0]->user_id;

            $query = "select * from data_boards where user_id = ? and category = ?";
            $boards = DB::select($query,array($user_id, $category));

            $response = "<div class='category-footprint-boards'>";
            foreach($boards as $board){
                $response .= "<a class='media' target='_blank' href='http://pinterest.com$board->url'>

                                    <div class='pull-left'>
                                        <img class='media-object cover-photo' src='$board->image_cover_url'>
                                    </div>
                                    <div class='media-body'>
                                        <h4 class='media-heading'>$board->name</h4>
                                        <div class='row margin-fix board-content'>
                                            <span>
                                                <i class='icon-users2'></i> $board->follower_count followers
                                            </span>
                                            <span style='margin-left:10px;'>
                                                <i class='icon-pin-2'></i> $board->pin_count pins
                                            </span>
                                        </div>
                                        <div class='row margin-fix'>
                                            <span class='description'>$board->description</span>
                                        </div>
                                    </div>

                              </a>";
            }
            $response .= "</div>";

        }
        catch (AjaxException $e) {
            $response = "";
        }

        $this->layout->response = $response;
    }


    /**
     * Records event data to a user's history.
     *
     * @route /v1/user/history/record/event
     *
     * @author Will
     * @author Daniel
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function recordEvent()
    {
        $event = filter_var(Input::get('event'), FILTER_SANITIZE_STRING);
        $data  = Input::get('data');

        array_walk($data, function (&$var) {
            $var = filter_var($var, FILTER_SANITIZE_STRING);
        });

        $user = User::getLoggedInUser();

        $recorded = false;

        try {
            $recorded = $user->recordEvent($event, $data);
        }
        catch (Exception $e) {
            Log::error($e);
        }

        return Response::json(array(
           'success' => $recorded,
        ));
    }

    public function switchAccounts() {
        if(!$this->isAdmin()) {
            return Redirect::back();
        }

        Session::set('admin_user',Input::get('cust_id'));

        return Redirect::back();

    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function searchUsers()
    {
        if (!$this->isAdmin()) {
            return Redirect::back();
        }

        $customers = array();

        if (Input::has('term')) {

            $customers = Users::findByAnything(false,Input::get('term'));


        $html = '';
        foreach ($customers as $customer) {

                 $html .= View::make('components::admin_search_result',['customer'=>$customer])->render();
        }
        }

        return $html;

    }


    /**
     * @param $report
     *
     * @return \Illuminate\View\View
     */
    public function showUpgradeModal($report) {
        if(View::exists('modals::upgrade.'.$report)) {
            return View::make('modals::upgrade.'.$report,['buttons'=> View::make('modals::upgrade.buttons')]);
        }
    }


}

function grabPage($url) {
    $c = curl_init ($url);
    curl_setopt ($c, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($c, CURLOPT_USERAGENT,
        "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
    $page = curl_exec ($c);
    curl_close ($c);

    return $page;
}

function pin_count($a, $b) {
    $t = "pin_count";

    if ($a["$t"] < $b["$t"]) {
        return 1;
    } else if ($a["$t"] == $b["$t"]) {
        return 0;
    } else {
        return -1;
    }
}

function word_count($a, $b) {
    $t = "word_count";

    if ($a["$t"] < $b["$t"]) {
        return 1;
    } else if ($a["$t"] == $b["$t"]) {
        return 0;
    } else {
        return -1;
    }
}

function created_at($a, $b) {
    $t = "created_at";

    if ($a["$t"] < $b["$t"]) {
        return 1;
    } else if ($a["$t"] == $b["$t"]) {
        return 0;
    } else {
        return -1;
    }
}

function extract_date($txt) {

    $txt = str_replace('Pinned ', '', $txt);

    $cutoff = strpos($txt, 'ago');

    $txt = substr($txt, 0, $cutoff);

    return $txt;

}

function getGoogleDateFormat($date) {
    $t = getTimestampFromDate($date);

    return date("Y-m-d", $t);
}

function getTimestampFromDate($date) {
    $m = substr($date, 0, 2);
    $d = substr($date, 3, 2);
    $y = substr($date, 6, 4);
    $t = mktime(0,0,0,$m, $d, $y);
    return $t;
}

function GetDateStringFromTime($t) {
    $date_string = date('F d, Y H:i:s', $t);

    return $date_string;
}

function cmp($a, $b) {
    $t = $_GET['t'];
    if (!$t) {
        $t = "repins";
    }

    if ($a["$t"] < $b["$t"]) {
        return 1;
    } else if ($a["$t"] == $b["$t"]) {
        return 0;
    } else {
        return -1;
    }
}

function pinners($a, $b) {
    $t = "count";

    if ($a["$t"] < $b["$t"]) {
        return 1;
    } else if ($a["$t"] == $b["$t"]) {
        return 0;
    } else {
        return -1;
    }
}

function engagement_count($a, $b) {
    $t = "engagement";

    if ($a["$t"] < $b["$t"]) {
        return 1;
    } else if ($a["$t"] == $b["$t"]) {
        return 0;
    } else {
        return -1;
    }
}

function repins($a, $b) {
    $t = "repins";

    if ($a["$t"] < $b["$t"]) {
        return 1;
    } else if ($a["$t"] == $b["$t"]) {
        return 0;
    } else {
        return -1;
    }
}

function getPinterestUrl($p) {
    return "http://pinterest.com/pin/$p/";
}

