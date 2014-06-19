<?php
/**
 * @author Alex
 * Date: 10/8/13 8:44 PM
 * 
 */

namespace Admin\adp;

use View;

use BaseController;

use DatabaseInstance;

use Engines,
    Engine,
    User,
    Users,
    UserLead,
    UserLeads;

class AdpController extends BaseController
{

    /**
     * Show list of customers
     *
     * @author  Will
     *
     */
    public function runAdp()
    {
        $conn = DatabaseInstance::mysql_connect();


        if(isset($_GET['action'])){
            $action = $_GET['action'];
            if(isset($_GET['uid'])){
                $uid = $_GET['uid'];
            } else {
                $uid = "";
            }
        } else {
            $action = "";
            $uid = "";
        }


        //create hash with date before and date after in case server time is slightly off on VPS
        $day = date('Y-n-j', time());
        $day1 = date('Y-n-j', strtotime("-1 day", time()));
        $day2 = date('Y-n-j', strtotime("+1 day", time()));
        $rid_check = md5($uid.$day.$uid);
        $rid_check1 = md5($uid.$day1.$uid);
        $rid_check2 = md5($uid.$day2.$uid);

//	print $day."<br>";
//	print $day1."<br>";
//	print $day2."<br>";
//
//	print $rid_check."<br>";
//	print $rid_check1."<br>";
//	print $rid_check2;

        //grab db data
        if($action == "init") {

            $uid = $_GET['uid'];
            $rid = $_GET['rid'];

            if($rid==$rid_check || $rid==$rid_check1 || $rid==$rid_check2) {
                $acc = "select * from adp where id='$uid'";
                $acc_res = mysql_query($acc,$conn) or die(mysql_error());
                while ($a = mysql_fetch_array($acc_res)) {
                    $username = $a['username'];
                    $fixed_wait = $a['fixed_wait'];
                    $variable_wait = $a['variable_wait'];
                    $network = $a['network'];
                    $vps = $a['vps'];
                    $board_follow_count = $a['board_follow_count'];
                    $unfollow_catchup = $a['unfollow_catchup'];
                    $email = $a['email'];
                    $password = $a['password'];
                    $threshold = $a['threshold'];
                    $status = $a['status'];
                    $category1 = $a['category1'];
                    $category2 = $a['category2'];
                    $category3 = $a['category3'];
                    $category4 = $a['category4'];
                    $category5 = $a['category5'];
                    $category6 = $a['category6'];
                    $category7 = $a['category7'];
                    $category8 = $a['category8'];
                    $category9 = $a['category9'];
                    $category10 = $a['category10'];
                    $keywords = $a['keywords'];
                    $keyword_percent = $a['keyword_percent'];
                }

                $acc = "select * from adp_proxies where id='$uid'";
                $acc_res = mysql_query($acc,$conn) or die(mysql_error());
                while ($a = mysql_fetch_array($acc_res)) {
                    $proxy_port = $a['proxy_port'];
                    $proxy_user = $a['proxy_user'];
                    $proxy_pw = $a['proxy_pw'];
                }

                print "
				<table>
					<tr class='username'><td>$username</td></tr>
					<tr class='fixed'><td>$fixed_wait</td></tr>
					<tr class='variable'><td>$variable_wait</td></tr>
					<tr class='network'><td>$network</td></tr>
					<tr class='vps'><td>$vps</td></tr>
					<tr class='board'><td>$board_follow_count</td></tr>
					<tr class='unfollow'><td>$unfollow_catchup</td></tr>
					<tr class='email'><td>$email</td></tr>
					<tr class='pw'><td>$password</td></tr>
					<tr class='threshold'><td>$threshold</td></tr>
					<tr class='status'><td>$status</td></tr>
					<tr class='cat1'><td>$category1</td></tr>
					<tr class='cat2'><td>$category2</td></tr>
					<tr class='cat3'><td>$category3</td></tr>
					<tr class='cat4'><td>$category4</td></tr>
					<tr class='cat5'><td>$category5</td></tr>
					<tr class='cat6'><td>$category6</td></tr>
					<tr class='cat7'><td>$category7</td></tr>
					<tr class='cat8'><td>$category8</td></tr>
					<tr class='cat9'><td>$category9</td></tr>
					<tr class='cat10'><td>$category10</td></tr>
					<tr class='keywords'><td>$keywords</td></tr>
					<tr class='keyword_perc'><td>$keyword_percent</td></tr>
					<tr class='proxy_port'><td>$proxy_port</td></tr>
					<tr class='proxy_user'><td>$proxy_user</td></tr>
					<tr class='proxy_pw'><td>$proxy_pw</td></tr>
				</table>";
            }
        }


        //action to get accounts in the network to follow
        if($action == "network") {
            $uid = $_GET['uid'];
            $rid = $_GET['rid'];
            $network = $_GET['network'];

            if($rid==$rid_check || $rid==$rid_check1 || $rid==$rid_check2) {

                if($network==1){
                    $net_name = "general";
                } elseif($network==2) {
                    $net_name = "womens";
                } elseif($network==3) {
                    $net_name = "weddings";
                } elseif($network==4) {
                    $net_name = "food";
                } else {
                    $net_name = "none";
                }


                if($net_name!="none"){

                    //count number of users in that network
                    $acc = "select count(*) from adp_network";
                    $acc_res = mysql_query($acc,$conn) or die(mysql_error());
                    while ($a = mysql_fetch_array($acc_res)) {
                        $count = $a['count(*)'];
                    }

                    $pick_rand = rand(0,$count);
                    $pick_rand2 = rand(0,$count);

                    $acc = "select username from adp_network where $net_name=1 limit $pick_rand, 1";
                    $acc_res = mysql_query($acc,$conn) or die(mysql_error());
                    while ($a = mysql_fetch_array($acc_res)) {
                        $username1 = $a['username'];
                    }

                    $acc = "select username from adp_network where $net_name=1 limit $pick_rand2, 1";
                    $acc_res = mysql_query($acc,$conn) or die(mysql_error());
                    while ($a = mysql_fetch_array($acc_res)) {
                        $username2 = $a['username'];
                    }

                    print "<table>";

                    if(!is_null($username1)){
                        print "<tr class='username1'><td>$username1</td></tr>";
                    } else {
                        print "<tr class='username1'><td>none</td></tr>";
                    }
                    if(!is_null($username2)){
                        print "<tr class='username1'><td>$username2</td></tr>";
                    } else {
                        print "<tr class='username1'><td>none</td></tr>";
                    }

                    print "</table>";

                } else {
                    print "
					<table>
						<tr class='none'><td>none</td></tr>
					</table>";
                }
            }
        }


        //record following a user or board
        if ($action == "followBoard") {
            $uid = $_GET['uid'];
            $rid = $_GET['rid'];

            if($rid==$rid_check || $rid==$rid_check1 || $rid==$rid_check2) {
                $username=strtolower($_GET['username']);
                $username_followed = strtolower($_GET['username_followed']);
                $board_followed = strtolower($_GET['board_followed']);
                $target = strtolower($_GET['target']);
                $target_type = strtolower($_GET['target_type']);
                $vps = $_GET['vps'];
                $time = time();

                if (($username)
                    && ($username_followed)) {
                    $sql = "insert into adp_actions
				(user_id, username, action_taken, username_followed, board_followed, total_boards, target, target_type, vps, tries, unfollowed, timestamp)
				values (\"$uid\", \"$username\", \"follow\", \"$username_followed\", \"$board_followed\", \"1\", \"$target\", \"$target_type\", \"$vps\", \"1\", \"0\", \"$time\")

				ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), board_followed = VALUES(board_followed), target = VALUES(target), target_type = VALUES(target_type), vps = VALUES(vps), tries = tries + 1, unfollowed = 0, timestamp = VALUES(timestamp)";
                    $resu = mysql_query($sql, $conn);
                }
            }
        }

        //record liking a user pin
        if ($action == "like") {
            $uid = $_GET['uid'];
            $rid = $_GET['rid'];

            if($rid==$rid_check || $rid==$rid_check1 || $rid==$rid_check2) {
                $username=strtolower($_GET['username']);
                $username_followed = strtolower($_GET['username_followed']);
                $board_followed = strtolower($_GET['board_followed']);
                $target = strtolower($_GET['target']);
                $target_type = strtolower($_GET['target_type']);
                $vps = $_GET['vps'];
                $time = time();

                if (($username)
                    && ($username_followed)) {
                    $sql = "insert into adp_actions
				(user_id, username, action_taken, username_followed, board_followed, total_boards, target, target_type, vps, tries, unfollowed, timestamp)
				values (\"$uid\", \"$username\", \"like\", \"$username_followed\", \"$board_followed\", \"1\", \"$target\", \"$target_type\", \"$vps\", \"1\", \"0\", \"$time\")";
                    $resu = mysql_query($sql, $conn);
                }
            }
        }


        //get new user to follow more boards for
        if ($action == "get_user") {
            $uid = $_GET['uid'];
            $username=strtolower($_GET['username']);

            $acc = "SELECT SQL_NO_CACHE username_followed
		 FROM adp_actions
		 WHERE username = '$username' and action_taken = 'follow'
		 ORDER BY timestamp DESC
		 LIMIT 110, 1";
            $acc_res = mysql_query($acc,$conn) or die(mysql_error());
            while ($a = mysql_fetch_array($acc_res)) {
                $username_followed = $a['username_followed'];
            }

            if($username_followed){


                $parameters = array();

                $parameters['fields'] = "board.url,board.category";

                $pinterest = new Pinterest;

                $data = $pinterest->getProfileBoards($username_followed, $parameters);

                $boards = array();
                $num_boards = count($data['data']);
                $count = 0;

                foreach($data['data'] as $d){
                    $boards[$count]['category'] = $d['category'];
                    $boards[$count]['url'] = $d['url'];
                    $count++;
                }

                print "<div class='username'>$username_followed</div>";
                if($num_boards > 1){
                    print "<div class='num_boards'>$num_boards</div>";


                    print "<div class='url'>";
                    $count=0;
                    foreach($boards as $board){
                        if($count==0){
                            print $board['url'];
                        } else {
                            print "," . $board['url'];
                        }
                        $count++;
                    }
                    print "</div>";

                    print "<div class='category'>";
                    $count=0;
                    foreach($boards as $board){
                        if($count==0){
                            print $board['category'];
                        } else {
                            print "," . $board['category'];
                        }
                        $count++;
                    }
                    print "</div>";
                }

            } else {
                print "<div class='get'>none</div>";
            }
        }


        //update how many boards were followed for a certain profile on the second go-around
        if ($action == "updateBoards") {
            $uid = $_GET['uid'];
            $username=strtolower($_GET['username']);
            $username_followed=strtolower($_GET['username_followed']);
            $total_boards = $_GET['total_boards'];

            if($total_boards==0){
                $total_boards = 1;
            }

            $insert = "UPDATE adp_actions set total_boards=$total_boards, unfollowed=0 where username='$username' and username_followed='$username_followed'";
            $resu = mysql_query($insert, $conn);
        }


        //get user to unfollow
        if ($action == "get_unfollow") {
            $username=strtolower($_GET['username']);
            if($_GET['unfollow_num']){
                $unfollow_num=$_GET['unfollow_num'];
            } else {
                $unfollow_num = 300;
            }

            $acc = "SELECT SQL_NO_CACHE username_followed
		 FROM adp_actions
		 WHERE username = '$username' and action_taken = 'follow'
		 ORDER BY timestamp DESC
		 LIMIT $unfollow_num, 1";
            $acc_res = mysql_query($acc,$conn) or die(mysql_error());
            while ($a = mysql_fetch_array($acc_res)) {
                $username_followed = $a['username_followed'];
            }
            if($username_followed){
                print "<div class='get'>$username_followed</div>";
            } else {
                print "<div class='get'>none</div>";
            }
        }


        //check any missed users to unfollow
        if ($action == "get_unfollow_check") {
            $username=strtolower($_GET['username']);
            if($_GET['unfollow_num']){
                $unfollow_num=$_GET['unfollow_num'];
            } else {
                $unfollow_num = 300;
            }

            $user_exists=false;
            $acc = "SELECT SQL_NO_CACHE username_followed, unfollowed
		 FROM adp_actions
		 WHERE username = '$username' and action_taken = 'follow'
		 ORDER BY timestamp DESC
		 LIMIT $unfollow_num, 10";
            $acc_res = mysql_query($acc,$conn) or die(mysql_error());
            while ($a = mysql_fetch_array($acc_res)) {
                $username_followed = $a['username_followed'];
                $unfollowed = $a['unfollowed'];

                if($unfollowed==0){
                    $user_exists=true;
                    break;
                }
            }
            if($user_exists){
                print "<div class='get'>$username_followed</div>";
            } else {
                print "<div class='get'>none</div>";
            }
        }


        //check any missed users to unfollow
        if ($action == "get_unfollow_more") {
            $username=strtolower($_GET['username']);
            if($_GET['unfollow_num']){
                $unfollow_num=$_GET['unfollow_num'];
            } else {
                $unfollow_num = 1000;
            }

            $user_exists=false;
            $acc = "SELECT SQL_NO_CACHE username_followed, unfollowed
		 FROM adp_actions
		 WHERE username = '$username' and action_taken = 'follow'
		 ORDER BY timestamp DESC
		 LIMIT $unfollow_num";
            $acc_res = mysql_query($acc,$conn) or die(mysql_error());
            while ($a = mysql_fetch_array($acc_res)) {
                $username_followed = $a['username_followed'];
                $unfollowed = $a['unfollowed'];

                $user_exists=true;
            }

            if($user_exists){
                print "<div class='get'>$username_followed</div>";
            } else {
                print "<div class='get'>none</div>";
            }
        }


        //get number of actions this user has taken
        if ($action == "get_unfollow_total") {
            $username=strtolower($_GET['username']);

            $user_exists=false;
            $acc = "SELECT SQL_NO_CACHE count(*) as action_count
		 FROM adp_actions
		 WHERE username = '$username' and action_taken = 'follow'";
            $acc_res = mysql_query($acc,$conn) or die(mysql_error());
            while ($a = mysql_fetch_array($acc_res)) {
                $action_count = $a['action_count'];
                $user_exists=true;
            }

            if($user_exists){
                print "<div class='get'>$action_count</div>";
            } else {
                print "<div class='get'>none</div>";
            }
        }


        //get user to unfollow for trueup process
        if ($action == "get_unfollow_trueup") {
            $username=strtolower($_GET['username']);
            if($_GET['unfollow_num']){
                $unfollow_num=$_GET['unfollow_num'];
            } else {
                $unfollow_num = 300;
            }

            $acc = "SELECT SQL_NO_CACHE username_followed
		 FROM adp_actions
		 WHERE username = '$username' and action_taken = 'follow'
		 ORDER BY timestamp DESC
		 LIMIT $unfollow_num, 1";
            $acc_res = mysql_query($acc,$conn) or die(mysql_error());
            while ($a = mysql_fetch_array($acc_res)) {
                $username_followed = $a['username_followed'];
            }
            if($username_followed){
                print "<div class='get'>$username_followed</div>";
            } else {
                print "<div class='get'>none</div>";
            }
        }




        //log profile as unfollowed or not unfollowed due to success
        if ($action == "updateUnfollow") {

            $uid = $_GET['uid'];
            $username=strtolower($_GET['username']);
            $username_followed=strtolower($_GET['username_followed']);
            $result=$_GET['result'];

            $insert = "UPDATE adp_actions set unfollowed=$result where username='$username' and username_followed='$username_followed'";
            $resu = mysql_query($insert, $conn);
        }




        //get list of latest followers
        if ($action == "get_followers") {
            $username=strtolower($_GET['username']);

            print "<table>";

            $acc = "select data_profiles_new.username as follower_username from data_followers left join (data_profiles_new)
		on (data_followers.follower_user_id = data_profiles_new.user_id)
		where data_followers.user_id = (select data_profiles_new.user_id from data_profiles_new where data_profiles_new.username='$username') order by data_followers.timestamp desc limit 2000;";
            $acc_res = mysql_query($acc,$conn) or die(mysql_error());
            while ($a = mysql_fetch_array($acc_res)) {
                $follower_username = strtolower($a['follower_username']);
                print "<tr><td class='get_follower'>$follower_username</td></tr>";
            }
        }



        //get list of latest categories organically
        if ($action == "user_categories" && $_GET['user']){

            $pin_counter = 0;
            $pinspage = "http://pinterest.com/". $_GET['user'] ."/pins/";

            //get the board link for each pin on the page
            foreach (htmlqp($pinspage, '.pin') as $pin) {
                $pins[] = array(
                    'board' => $pin->branch('div.attribution')->branch('p.NoImage')->branch('a')->eq(0)->attr('href')
                );
                $pin_counter++;
            }

            //print "$pin_counter pins found";

            $boards_counter = 0;
            $boards = array();
            foreach ($pins as $p){

                $board = $p['board'];

                if(!$boards["$board"]){
                    $boards["$board"] = array();
                    $boards["$board"]['url'] = $board;
                    $boards["$board"]['count'] = 1;
                    $boards_counter++;
                } else {
                    $boards["$board"]['count']++;
                }

            }

            //print "<br>$boards_counter boards found";

            $category_counter = 0;
            $categories_found = 0;
            $categories = array();
            foreach($boards as $b){
                $board_url = $b['url'];
                $board_count = $b['count'];

                $board_url = "http://pinterest.com" . $board_url;

                foreach(htmlqp($board_url, 'head') as $c){
                    $category = $c->top('meta')->eq(12)->attr('content');

                    if($category!=""){
                        if(!$categories["$category"]){
                            $categories["$category"] = array();
                            $categories["$category"]['category'] = $category;
                            $categories["$category"]['count'] = $board_count;
                            $category_counter++;
                            $categories_found++;
                        } else {
                            $categories["$category"]['count'] += $board_count;
                            $categories_found++;
                        }
                    }
                }
            }

            //print "<br>$category_counter categories found";


            foreach($categories as $c){

                $cat = $c['category'];
                $catcount = $c['count'];

                print "<div class='category'>$cat,". number_format((($catcount/$pin_counter)*100),0) . "</div>";
            }

        }
    }

}