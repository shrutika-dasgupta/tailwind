<?php ini_set('display_errors', 'off');
error_reporting(0);

$page = "Trending Pins";

$customer = User::find($cust_id);

if(isset($range)){
    $range = $range;
} else {
    $range = 50;
}

$fifty_pill = "";
$hundo_pill = "";
$five_hundo_pill = "";
$thousand_pill = "";

$fifty_link = "href='/pins/domain/trending/50'";
$hundo_link = "href='/pins/domain/trending/100'";
$five_hundo_link = "href='/pins/domain/trending/500'";
$thousand_link = "href='/pins/domain/trending/1000'";


if ($customer->hasFeature('trending_latest_1000')) {
    $trending_limit = 1000;

    if($range > 1000){
        $range = 1000;
    }

} else {
    if ($customer->hasFeature('trending_latest_500')) {
        $trending_limit = 500;
        $thousand_pill = "inactive";
        $thousand_link = "";


        if($range > 500){
            $range = 500;
        }

    } else {
        if ($customer->hasFeature('trending_latest_100')) {
            $trending_limit = 100;
            $thousand_pill = "inactive";
            $thousand_link = "";
            $five_hundo_pill = "inactive";
            $five_hundo_link = "";

            if($range > 100){
                $range = 100;
            }

        } else {
            if ($customer->hasFeature('trending_latest_50')) {
                $trending_limit = 50;
                $thousand_pill = "inactive";
                $thousand_link = "";
                $five_hundo_pill = "inactive";
                $five_hundo_link = "";
                $hundo_pill = "inactive";
                $hundo_link = "";

                if($range > 50){
                    $range = 50;
                }

            } else {
                $trending_limit = 50;
                $thousand_pill = "inactive";
                $thousand_link = "";
                $five_hundo_pill = "inactive";
                $five_hundo_link = "";
                $hundo_pill = "inactive";
                $hundo_link = "";
                $fifty_pill = "inactive";
                $fifty_link = "";

            }
        }
    }
}

if ($customer->hasFeature('trending_export')) {
    //TODO: need to build this feature
} else {

}







//get the last time that this domain was pulled for pins
$acc = "select * from status_domains where domain='$cust_domain'";
$acc_res = mysql_query($acc,$conn) or die(mysql_error() . "Line: " . __LINE__);
while ($a = mysql_fetch_array($acc_res)) {
    $last_pulled = $a['last_pulled'];
}




if ($range == 50) {
    $num_pins = 50;
    $fifty_pill = "active";
} else if ($range == 100) {
    $num_pins = 100;
    $hundo_pill = "active";
} else if ($range == 500) {
    $num_pins = 500;
    $five_hundo_pill = "active";
} else if ($range == 1000) {
    $num_pins = 1000;
    $thousand_pill = "active";
}



if($cust_domain!=""){

    print "
		<div class=\"row-fluid\" style='margin-bottom:10px;'>";
    print "<div class=\"span\" style='text-align:left;'>";
    print "<h1>Trending Pins from <u>$cust_domain</u></h1><hr>";
    print "</div>";
    print "</div>";

    print "<div class='clearfix'></div>";
    //
    print "<div class='clearfix'></div>";

    print "
		<div class=\"brand-mentions\">";
    print "<div class=\"\">";



    print "
			<div class='pins'>";

    //print "<div class='loading1' style='text-align:center'>Loading... <img src='images/loading.gif'></div>";

    print "
			</div>";

//		$time = microtime(true)*1000;
//		//set check time to 2 hours ago to see if any pins were updated in the last day.
//		$check_time = round($time - 80000000);
//		$pull_time = round($time - 10800000);
//
//
//		$is_data_recent = false;
//		if($last_pulled > $check_time){
//			$is_data_recent = true;
//		}




//		if(!$is_data_recent){
    //run background process to call script on calculations server in order to scrape site_pins and store them in the DB (will only run if pins have not been updated recently)
//			$process_site_pins = "nohup /usr/bin/php /var/www/html/dashboard-grab-page.php -a " . escapeshellarg($site) . " > /dev/null 2>/dev/null & echo $!";
//
//			$pid = shell_exec($process_site_pins);
//
//			$pins_ready=false;
//		} else {
//			$pins_ready = true;
//		}

    //check that pins actually exist for this domain
    $pins_count = 0;
    $acc2 = "select * from data_pins_new where domain='$cust_domain' limit $num_pins";
    $acc2_res = mysql_query($acc2,$conn) or die(mysql_error() . "Line: " . __LINE__);
    while ($b = mysql_fetch_array($acc2_res)) {
        $pins_count++;
    }

    if($pins_count!=0){
        $pins_ready=true;
        $unready_count = 0;
        $acc2 = "select image_id from data_pins_new where domain='$cust_domain' order by created_at desc limit $trending_limit";
        $acc2_res = mysql_query($acc2,$conn) or die(mysql_error() . "Line: " . __LINE__);
        while ($b = mysql_fetch_array($acc2_res)) {
            $image_id = $b['image_id'];

            if($image_id == "" || $image_id=="0"){
                $pins_ready = false;
                $unready_count++;
                //print $image_id;
            }
        }
        if(!$pins_ready){
            for ($i = 0; $i < ($unready_count/50); $i++) {

                $marker = $i*50;
                //run the image recognition script
                $process = "nohup /usr/bin/php ".base_path()."/engines/internal/image_process.php -a " . escapeshellarg($cust_domain) . " -b " . escapeshellarg($marker) . " > /dev/null 2>/dev/null & echo $!";

                $pid = exec($process);
            }
        }
    }


    if($pins_ready && $pins_count!=0){

        //get pin data into separate arrays
        $pins = array();
        $pinners = array();
        //$boards = array();
        $images = array();
        $pinner_ids = array();
        $acc2 = "select * from data_pins_new where domain='$cust_domain' order by created_at desc limit $num_pins";
        $acc2_res = mysql_query($acc2,$conn) or die(mysql_error() . "Line: " . __LINE__);
        while ($b = mysql_fetch_array($acc2_res)) {

            $pin_id = $b['pin_id'];
            $user_id = $b['user_id'];
            $image_id = $b['image_id'];

            /*
             * image_id is set to 3 where the image does not load from pinterest and cannot
             * be processed.  Therefor, we ignore these here before we load the page.
             */
            if($image_id != 3){
                //set pins array
                $pins["$pin_id"] = array();
                $pins["$pin_id"]['pin_id'] = $b['pin_id'];
                $pins["$pin_id"]['user_id'] = $b['user_id'];
                $pins["$pin_id"]['board_id'] = $b['board_id'];
                $pins["$pin_id"]['domain'] = $b['domain'];
                $pins["$pin_id"]['method'] = $b['method'];
                $pins["$pin_id"]['is_repin'] = $b['is_repin'];
                $pins["$pin_id"]['image_url'] = $b['image_url'];
                $pins["$pin_id"]['link'] = $b['link'];
                $pins["$pin_id"]['description'] = $b['description'];
                $pins["$pin_id"]['dominant_color'] = $b['dominant_color'];
                $pins["$pin_id"]['repin_count'] = $b['repin_count'];
                $pins["$pin_id"]['like_count'] = $b['like_count'];
                $pins["$pin_id"]['comment_count'] = $b['comment_count'];
                $pins["$pin_id"]['created_at'] = $b['created_at'];
                $pins["$pin_id"]['image_id'] = $b['image_id'];

    //            if(!isset($wordcloud)){
    //                $wordcloud = preg_replace('/[^A-Za-z0-9 ]/',' ', strtolower($b['description']));
    //            } else {
    //                $wordcloud = $wordcloud . ' ' . preg_replace('/[^A-Za-z0-9 ]/',' ', strtolower($b['description']));
    //            }

                //set pinners array
                if(!empty($user_id)){
                    if(!$pinners["$user_id"]){
                        $pinners["$user_id"] = array();
                        $pinners["$user_id"]['user_id'] = $b['user_id'];
                        $pinners["$user_id"]['pin_count'] = 1;
                        $pinners["$user_id"]['repin_count'] = $b['repin_count'];
                        $pinners["$user_id"]['like_count'] = $b['like_count'];
                        $pinners["$user_id"]['comment_count'] = $b['comment_count'];

                        array_push($pinner_ids, $user_id);

                    } else {
                        $pinners["$user_id"]['pin_count']++;
                        $pinners["$user_id"]['repin_count'] += $b['repin_count'];
                        $pinners["$user_id"]['like_count'] += $b['like_count'];
                        $pinners["$user_id"]['comment_count'] += $b['comment_count'];
                    }
                }


            //set boards array
//				if(!$boards["$board_url"]){
//					$boards["$board_url"] = array();
//					$boards["$board_url"]['board_url'] = $b['board_url'];
//					$boards["$board_url"]['board_name'] = $b['board_name'];
//					$boards["$board_url"]['pinner_username'] = $b['pinner_username'];
//					$boards["$board_url"]['pinner_name'] = $b['pinner_name'];
//					$boards["$board_url"]['pinner_image'] = $b['pinner_image'];
//					$boards["$board_url"]['pin_count'] = 1;
//					$boards["$board_url"]['repin_count'] = $b['repins'];
//					$boards["$board_url"]['like_count'] = $b['likes'];
//					$boards["$board_url"]['comment_count'] = $b['comments'];
//				} else {
//					$boards["$board_url"]['pin_count']++;
//					$boards["$board_url"]['repin_count'] += $b['repins'];
//					$boards["$board_url"]['like_count'] += $b['likes'];
//					$boards["$board_url"]['comment_count'] += $b['comments'];
//				}
            }
        }


        //pull profile data of pinners from the profiles tables
        $acc = "select * from data_profiles_new where user_id IN(" . implode(',', $pinner_ids) . ");";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error() . "Line: " . __LINE__);
        while ($a = mysql_fetch_array($acc_res)) {
            $user_id = $a['user_id'];

            $pinners[$user_id]['user_id'] = $a['user_id'];
            $pinners[$user_id]['username'] = $a['username'];
            $pinners[$user_id]['first_name'] = $a['first_name'];
            $pinners[$user_id]['last_name'] = $a['last_name'];
            $pinners[$user_id]['image'] = $a['image'];
            $pinners[$user_id]['domain_url'] = $a['domain_url'];
            $pinners[$user_id]['website_url'] = $a['website_url'];
            $pinners[$user_id]['facebook_url'] = $a['facebook_url'];
            $pinners[$user_id]['twitter_url'] = $a['twitter_url'];
            $pinners[$user_id]['location'] = $a['location'];
            $pinners[$user_id]['pins'] = $a['pin_count'];
            $pinners[$user_id]['board_count'] = $a['board_count'];
            $pinners[$user_id]['follower_count'] = $a['follower_count'];
            $pinners[$user_id]['following_count'] = $a['following_count'];
            $pinners[$user_id]['created_at'] = $a['created_at'];
        }

        //map pin and profile data to the image of the pin itself
        $acc2 = "select * from data_pins_new where domain='$cust_domain' order by created_at desc limit $num_pins";
        $acc2_res = mysql_query($acc2,$conn) or die(mysql_error() . "Line: " . __LINE__);
        while ($b = mysql_fetch_array($acc2_res)) {

            $pin_id = $b['pin_id'];
            $user_id = $b['user_id'];
            //$board_url = $b['board_url'];
            $image_id = $b['image_id'];


            //set images array
            if(!isset($images["$image_id"])){
                $images["$image_id"] = array();
                $images["$image_id"]['image_url'] = $b['image_url'];
                $images["$image_id"]['pin_count'] = 1;
                $images["$image_id"]['repin_count'] = $b['repin_count'];
                $images["$image_id"]['like_count'] = $b['like_count'];
                $images["$image_id"]['comment_count'] = $b['comment_count'];

                $images["$image_id"]['pinners']["$user_id"] = array();
                $images["$image_id"]['pinners']["$user_id"]['user_id'] = $pinners[$user_id]['user_id'];
                @$images["$image_id"]['pinners']["$user_id"]['username'] = $pinners[$user_id]['username'];
                @$images["$image_id"]['pinners']["$user_id"]['first_name'] = $pinners[$user_id]['first_name'];
                @$images["$image_id"]['pinners']["$user_id"]['last_name'] = $pinners[$user_id]['last_name'];
                @$images["$image_id"]['pinners']["$user_id"]['image'] = $pinners[$user_id]['image'];
                @$images["$image_id"]['pinners']["$user_id"]['domain_url'] = $pinners[$user_id]['domain_url'];
                @$images["$image_id"]['pinners']["$user_id"]['website_url'] = $pinners[$user_id]['website_url'];
                @$images["$image_id"]['pinners']["$user_id"]['facebook_url'] = $pinners[$user_id]['facebook_url'];
                @$images["$image_id"]['pinners']["$user_id"]['twitter_url'] = $pinners[$user_id]['twitter_url'];
                @$images["$image_id"]['pinners']["$user_id"]['location'] = $pinners[$user_id]['location'];
                @$images["$image_id"]['pinners']["$user_id"]['pin_count'] = $pinners[$user_id]['pin_count'];
                @$images["$image_id"]['pinners']["$user_id"]['board_count'] = $pinners[$user_id]['board_count'];
                @$images["$image_id"]['pinners']["$user_id"]['follower_count'] = $pinners[$user_id]['follower_count'];
                @$images["$image_id"]['pinners']["$user_id"]['following_count'] = $pinners[$user_id]['following_count'];
                @$images["$image_id"]['pinners']["$user_id"]['created_at'] = $pinners[$user_id]['created_at'];
                @$images["$image_id"]['pinners']["$user_id"]['pin_count'] = $pinners[$user_id]['pin_count'];

                $pins["$pin_id"]['user_id'] = $b['user_id'];
                @$pins["$pin_id"]['username'] = $pinners[$user_id]['username'];
                @$pins["$pin_id"]['first_name'] = $pinners[$user_id]['first_name'];
                @$pins["$pin_id"]['last_name'] = $pinners[$user_id]['last_name'];

            } else {
                $images["$image_id"]['pin_count']++;
                $images["$image_id"]['repin_count'] += $b['repin_count'];
                $images["$image_id"]['like_count'] += $b['like_count'];
                $images["$image_id"]['comment_count'] += $b['comment_count'];

                $images["$image_id"]['pinners']["$user_id"] = array();
                $images["$image_id"]['pinners']["$user_id"]['user_id'] = $pinners[$user_id]['user_id'];
                @$images["$image_id"]['pinners']["$user_id"]['username'] = $pinners[$user_id]['username'];
                @$images["$image_id"]['pinners']["$user_id"]['first_name'] = $pinners[$user_id]['first_name'];
                @$images["$image_id"]['pinners']["$user_id"]['last_name'] = $pinners[$user_id]['last_name'];
                @$images["$image_id"]['pinners']["$user_id"]['image'] = $pinners[$user_id]['image'];
                @$images["$image_id"]['pinners']["$user_id"]['domain_url'] = $pinners[$user_id]['domain_url'];
                @$images["$image_id"]['pinners']["$user_id"]['website_url'] = $pinners[$user_id]['website_url'];
                @$images["$image_id"]['pinners']["$user_id"]['facebook_url'] = $pinners[$user_id]['facebook_url'];
                @$images["$image_id"]['pinners']["$user_id"]['twitter_url'] = $pinners[$user_id]['twitter_url'];
                @$images["$image_id"]['pinners']["$user_id"]['location'] = $pinners[$user_id]['location'];
                @$images["$image_id"]['pinners']["$user_id"]['pin_count'] = $pinners[$user_id]['pin_count'];
                @$images["$image_id"]['pinners']["$user_id"]['board_count'] = $pinners[$user_id]['board_count'];
                @$images["$image_id"]['pinners']["$user_id"]['follower_count'] = $pinners[$user_id]['follower_count'];
                @$images["$image_id"]['pinners']["$user_id"]['following_count'] = $pinners[$user_id]['following_count'];
                @$images["$image_id"]['pinners']["$user_id"]['created_at'] = $pinners[$user_id]['created_at'];
                @$images["$image_id"]['pinners']["$user_id"]['pin_count'] = $pinners[$user_id]['pin_count'];

                $pins["$pin_id"]['user_id'] = $b['user_id'];
                @$pins["$pin_id"]['username'] = $pinners[$user_id]['username'];
                @$pins["$pin_id"]['first_name'] = $pinners[$user_id]['first_name'];
                @$pins["$pin_id"]['last_name'] = $pinners[$user_id]['last_name'];
            }


        }

        //usort($pins, "timestamp");

        //get date of the last pin
        $j = count($pins) - 1;
        $keys = array_keys($pins);
        $last_date = $pins[$keys[$j]]['created_at'];
        $time_ago = time() - $last_date;
        $one_week = 60*60*24*7;
        $one_day = 60*60*24;

        if($time_ago < ($one_week*2)){
            if($time_ago < $one_day){
                $time_ago_print = number_format(($time_ago/(60*60)),0) . " hours";
            } else {
                $time_ago_print = number_format(($time_ago/(60*60*24)),0) . " days";
                if($time_ago_print=="1 days"){
                    $time_ago_print="day";
                }
            }
        } else {
            $time_ago_print = number_format(($time_ago/$one_week),0) . " weeks";
        }
        ?>



        
        <div class='row-fluid'>
        <div class='span8'>


        <div class='accordion' id='accordion3' style='margin-bottom:25px; margin-left:0px'>
        <div class='accordion-group' style='margin-bottom:0px; border-bottom:none'>
            <div class='section-header'>
                <div class='accordion-toggle section-header' data-parent='#accordion3'
                     href='#collapseTwo' style='cursor:default'>
                    <h2 style='float:left; font-weight:normal;font-size:22px'>Latest</h2>
                    <ul class="nav nav-pills pull-left" style='margin:2px 15px 0 0'>
                        <li style="padding:8px;"></li>
                        <li class="<?=$fifty_pill;?>"><a <?=$fifty_link;?> >50</a></li>
                        <li class="<?=$hundo_pill;?>"><a <?=$hundo_link;?> >100</a></li>
                        <li class="<?=$five_hundo_pill;?>"><a <?=$five_hundo_link;?> >500</a></li>
                        <li class="<?=$thousand_pill;?>"><a <?=$thousand_link;?> >1000</a></li>
                    </ul>


                    <h2 style='float:left; font-weight:normal;font-size:22px'>
                        Pins from the past
                        <span class='muted'><?=$time_ago_print;?></span>
                    </h2>
                    <div class='help-icon-form pull-right' style='margin:8px 0 0 5px;'>
                        <a class='' data-toggle='popover' data-container='body'
                           data-original-title='What Are My Trending Pins?'
                           data-content="Your Trending Pins are the most recent images being
                                       pinned from <?=$cust_domain;?>.
                                       <br><br><strong>Trending Images:</strong> Using image recognition,
                                       this report shows you which images are being pinned the most
                                       (and who has pinned them).
                                       The count on the upper left-hand corner of each Pinner's image is the
                                       number of times they've recently pinned from <?=$cust_domain;?>.
                                       <br><br><strong>All Your Latest Pins:</strong> a running list of the most
                                       recent pins from <?=$cust_domain;?>, regardless of image.
                                       <br><br><strong>Top Pinners:</strong> meet the people who have recently
                                       pinned the most from <?=$cust_domain;?>." data-trigger='hover'
                           data-placement='bottom'>
                            <i id='header-icon' class='icon-help'></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class='clearfix section-header'></div>


        <div id='collapseTwo' class='accordion-body collapse in'>
            <div class='accordion-inner'>

                <div class="row no-margin">

<?php
						//create tabs for each list
						print "<ul class='nav nav-tabs' id='trendingPinsTabs'>
								  <li class='active'><a href='#trending' data-toggle='tab'>Trending Images</a></li>
								  <li><a href='#recent' data-toggle='tab'>All Your Latest Pins</a></li>
								  <li><a href='#pinners' data-toggle='tab'>Top Pinners</a></li>
								</ul>";

						print "<div class='tab-content'>";




						//trending images tab
						print "<div class='tab-pane active' id='trending'>";
							print "<div class=\"\">";
								print "<div class=\"row-fluid\">
											<div class=\"span4\"><center>Pin</center></div>
											<div class=\"span3\"><center>Popularity</center></div>
											<div class=\"span5\">Pinners</div>
										</div>
										<hr>";

								usort($images, "pin_count");
								$image_show_count = 0;
								foreach($images as $p){
                                    $image = $p['image_url'];
                                    $pin_count = $p['pin_count'];
                                    $repin_count = $p['repin_count'];
                                    $like_count = $p['like_count'];
                                    $comment_count = $p['comment_count'];
                                    $pin_pinners = $p['pinners'];

                                    if($pin_count > 1){
                                        $image_show_count++;
                                        print "<div class=\"row-fluid\">
												<div class=\"span4\"><center><img src='$image' class='trending-pin-image'></center></div>
									  			<div class=\"span3\"><center>Pinned <br><strong><h1>$pin_count</h1></strong> times</center></div>";

//										  			print "
//										  			<div class=\"span2\">
//											  			<strong>$repin_count</strong> Total Repins<br>
//											  			<strong>$like_count</strong> Total Likes<br>
//											  			<strong>$comment_count</strong> Total Comments<br>
//										  			</div>";

                                        print "
									  			<div class=\"span5\">";

                                        usort($pin_pinners, "pin_count");

                                        foreach($pin_pinners as $u){
                                            if($u['image']==""){
                                                $p_image = "http://passets-lt.pinterest.com/images/user/default_75.png";
                                            } else {
                                                $p_image = $u['image'];
                                            }
                                            $p_user_id = $u['user_id'];
                                            $p_username = $u['username'];
                                            $p_first_name = str_replace('\'', '', $u['first_name']);
                                            $p_last_name = str_replace('\'', '', $u['last_name']);
                                            $p_pin_count = $u['pin_count'];

                                            print "<span class='trending-image-pinner'><a target='_blank' href='http://pinterest.com/$p_username' data-toggle='popover' data-container='body' data-placement='top' data-content='<strong>$p_first_name $p_last_name</strong> <br> ";

                                            if($p_pin_count==1){
                                                print "$p_pin_count recent pin";
                                            } else {
                                                print "$p_pin_count recent pins";
                                            }

                                            print " <br>from <br>$cust_domain'><div class='trending-image-pinner-engage'>$p_pin_count</div><img src='$p_image'></a></span>";
                                        }
                                        print "	</div>
									  	</div>
									  	<hr>";
                                    }

                                }

								if($image_show_count < 10){
                                    foreach($images as $p){
                                        $image = $p['image_url'];
                                        $pin_count = $p['pin_count'];
                                        $repin_count = $p['repin_count'];
                                        $like_count = $p['like_count'];
                                        $comment_count = $p['comment_count'];
                                        $pin_pinners = $p['pinners'];

                                        if($pin_count == 1 && $image_show_count < 20){
                                            print "<div class=\"row-fluid\">
													<div class=\"span4\"><center><img src='$image' class='trending-pin-image'></center></div>
										  			<div class=\"span3\"><center>Pinned <br><strong><h1>$pin_count</h1></strong> times</center></div>";

                                            //										  			print "
                                            //										  			<div class=\"span2\">
                                            //											  			<strong>$repin_count</strong> Total Repins<br>
                                            //											  			<strong>$like_count</strong> Total Likes<br>
                                            //											  			<strong>$comment_count</strong> Total Comments<br>
                                            //										  			</div>";

                                            print "
										  			<div class=\"span5\">";

                                            usort($pin_pinners, "pin_count");

                                            foreach($pin_pinners as $u){
                                                if($u['image']==""){
                                                    $p_image = "http://passets-lt.pinterest.com/images/user/default_75.png";
                                                } else {
                                                    $p_image = $u['image'];
                                                }
                                                $p_username = $u['username'];
                                                $p_first_name = str_replace('\'', '', $u['first_name']);
                                                $p_last_name = str_replace('\'', '', $u['last_name']);
                                                $p_pin_count = $u['pin_count'];

                                                print "<span class='trending-image-pinner'><a  target='_blank' href='http://pinterest.com/$p_username' data-toggle='popover' data-container='body' data-placement='top' data-content='<strong>$p_first_name $p_last_name</strong> <br> ";

                                                if($p_pin_count==1){
                                                    print "$p_pin_count recent pin";
                                                } else {
                                                    print "$p_pin_count recent pins";
                                                }

                                                print " <br>from <br>$cust_domain'><div class='trending-image-pinner-engage'>$p_pin_count</div><img src='$p_image'></a></span>";
                                            }
                                            print "	</div>
										  	</div>
										  	<hr>";
                                            $image_show_count++;
                                        }
                                    }
                                }

							print "</div>";
						print "</div>";

						//most recent pins tab
						print "<div class='tab-pane' id='recent'>";
							print "<div class=\"\">";
								print "<div class=\"row-fluid\">
											<div class=\"span5\"><center>Pin</center></div>
											<div class=\"span3\">Engagement</div>
											<div class=\"span4\"><center>Pinner</center></div>
										</div>
										<hr>";

								usort($pins, "created_at");

								foreach($pins as $p){
                                    $pin_id = $p['pin_id'];
                                    $pinner_user_id = $p['user_id'];
                                    @$pinner_first_name = $pinners[$pinner_user_id]['first_name'];
                                    @$pinner_last_name = $pinners[$pinner_user_id]['last_name'];
                                    @$pinner_username = $pinners[$pinner_user_id]['username'];

                                    if(!isset($pinners[$pinner_user_id]['image'])
                                        || $pinners[$pinner_user_id]['image']==""){
                                        $pinner_image = "http://passets-lt.pinterest.com/images/user/default_75.png";
                                    } else {
                                        $pinner_image = $pinners[$pinner_user_id]['image'];
                                    }
                                    $description = $p['description'];
                                    $image = $p['image_url'];
                                    $repins = $p['repin_count'];
                                    $likes = $p['like_count'];
                                    $comments = $p['comment_count'];

                                    print "<div class=\"row-fluid\">
												<div class=\"span5\"><center><a target='_blank' href='http://pinterest.com/pin/$pin_id/' data-toggle='popover' data-content='$description' data-placement='right'><img src='$image' class='trending-pin-image'></a></center></div>
									  			<div class=\"span3\">
									  				<strong>$repins</strong> repins<br>
									  				<strong>$likes</strong> likes<br>
									  				<strong>$comments</strong> comments</div>
									  			<div class=\"span4\"><center>Pinned by <br><br>
									  				<a target='_blank' href='http://pinterest.com/$pinner_username/'><h4>$pinner_first_name $pinner_last_name</h4><br>
									  					<img src='$pinner_image' height='75'> </a></center></div>";

//									  			print "
//									  			<div class=\"span3\"><center>
//									  				Pinned onto <br><br>
//									  				</center></div>";

                                    print "
									  		</div>
									  		<hr>";

                                }

							print "</div>";
						print "</div>";

						//top pinners tab
						print "<div class='tab-pane' id='pinners'>";
							print "<div class=\"\">";
								print "<div class=\"row-fluid\">
											<div class=\"span4\"><center>Pinner</center></div>
											<div class=\"span3\"><center>Pins</center></div>
											<div class=\"span5\"><center>Engagement Received on Your Pins</center></div>
										</div>
										<hr>";

								usort($pinners, "pin_count");

								$pinner_counter = 0;
								foreach($pinners as $p){
                                    if($pinner_counter < 10){
                                        @$pinner_username = $p['username'];
                                        @$pinner_first_name = $p['first_name'];
                                        @$pinner_last_name = $p['last_name'];

                                        if(!isset($p['image']) || $p['image']==""){
                                            $pinner_image = 'http://passets-lt.pinterest.com/images/user/default_75.png';
                                        } else {
                                            $pinner_image = $p['image'];
                                        }
                                        $pin_count = $p['pin_count'];
                                        $repin_count = $p['repin_count'];
                                        $like_count = $p['like_count'];
                                        $comment_count = $p['comment_count'];

                                        print "<div class=\"row-fluid\">
													<div class=\"trending-pinner-pinner span4\" style='float:left;text-align:center;'>
										  				<a target='_blank' href='http://pinterest.com/$pinner_username/'><img src='$pinner_image' height='75'> <br>$pinner_first_name $pinner_last_name </a></div>
										  			<div class=\"trending-pinner-pins span3\" style='float:left;text-align:center;'>
										  				<center><strong><h1>$pin_count</h1></strong> Pins</center></div>
										  			<div class=\"trending-pinner-stats span5\" style='float:left; text-align:center;'>
										  				<strong>$repin_count</strong> Total Repins<br>
										  				<strong>$like_count</strong> Total Likes<br>
										  				<strong>$comment_count</strong> Total Comments<br>
										  			</div>";

//										  			print "
//										  			<div class=\"trending-pinner-widget\" style=\"width:500px\">";
//
//										  				if($pinner_username!=""){
//										  					print "<a data-pin-do='embedUser' href='http://pinterest.com/$pinner_username/' data-pin-scale-width='60' data-pin-scale-height='120' data-pin-board-width='600'></a>";
//										  				}
//										  			print
//										  			"</div>";


                                        print
                                            "</div>
                                            <hr>";

                                        $pinner_counter++;
                                    }

                                }

							print "</div>";
						print "</div>";

						//top boards tab
//						print "<div class='tab-pane' id='boards'>";
//							print "<div class=\"\">";
//								print "<div class=\"row-fluid\">
//											<div class=\"span3\"><center>Board</center></div>
//											<div class=\"span3\"><center>Pins</center></div>
//											<div class=\"span5\"><center>Board Details</center></div>
//										</div>
//										<hr>";
//
//								usort($boards, "pin_count");
//
//								$board_counter = 0;
//								foreach($boards as $p){
//									if($board_counter < 10){
//										$pinner_username = $p['pinner_username'];
//										$pinner_name = $p['pinner_name'];
//										$pinner_image = $p['pinner_image'];
//										$board_url = $p['board_url'];
//										$board_name = $p['board_name'];
//										$pin_count = $p['pin_count'];
//										$repin_count = $p['repin_count'];
//										$like_count = $p['like_count'];
//										$comment_count = $p['comment_count'];
//
//										print "<div class=\"row-fluid\">
//										  			<div class=\"trending-board-board\">
//										  				<center><a href='http://pinterest.com$board_url'><h3 style='margin:0px'>$board_name</h3></a>
//										  				by<br>
//										  				<a href='http://pinterest.com/$pinner_username/'><img src='$pinner_image' height='50'><br>$pinner_name</a></center></div>
//										  			<div class=\"trending-pinner-pins\">
//										  				<center><strong><h1 rel='tooltip' data-original-title='<strong>$repin_count</strong> Total Repins<br>
//										  				<strong>$like_count</strong> Total Likes<br>
//										  				<strong>$comment_count</strong> Total Comments'>$pin_count</h1></strong> Pins</center></div>
//										  			<div class=\"trending-board-widget\">
//										  					<center><a data-pin-do='embedBoard' href='http://pinterest.com$board_url' data-pin-scale-width='60' data-pin-scale-height='100' data-pin-board-width='700' width='100%'></a></center>
//										  			</div>
//										  		</div>
//										  		<hr>";
//
//										$board_counter++;
//									}
//								}
//
//							print "</div>";
//						print "</div>";



						print "</div>";
	  				print "</div>";
	  				//end tabs

  				print "</div>";
			print "</div>";
		print "</div>";
	print "</div>";


	print "<div class='span4'>";

		print "
		<div class='accordion' id='accordion4' style='margin-bottom:25px; margin-left:0px'>
			<div class='accordion-group' style='margin-bottom:0px; border-bottom:none'>
			  <div class='section-header'>
			    <div class='accordion-toggle' data-parent='#accordion4' href='#collapseThree' style='cursor:default'>
			    	<h2 style='float:left;font-weight:normal;font-size:16px'>What Fans Are Saying When They Pin</h2>
			    	<div class='help-icon-form pull-right' style='margin:8px 0 0 5px;'>
			    		<a class='' data-toggle='popover' data-container='body' data-original-title='Your finger on the Pinterest pulse' data-content=\"This 'Word-Cloud' is an easy way for you to break down how Pinners are describing the most recent pins from $cust_domain.\" data-trigger='hover' data-placement='left'>
			    			<i id='header-icon' class='icon-help'></i>
			    		</a>
			    	</div>
			    </div>
			  </div>";

			  print "<div class='clearfix section-header'></div>";

			  print "
			  <div id='collapseThree' class='accordion-body collapse in'>
			       <div class='accordion-inner'>";

						print "
						<div id='wordcloud' style='width: 100%; height: 500px; position: relative;'></div>";

					print "
					</div>";
				print "
				</div>";
			print "
			</div>
		</div>";

	print "</div>";

print "</div>";

		print "
			<script type='text/javascript'>

				$('.pins').ready( function () {
					$('.loading1').css('display','none');
				});
				$('.pinners').ready( function () {
					$('.loading2').css('display','none');
				});
				$('.top_pins').ready( function () {
					$('.loading3').css('display','none');
				});


			</script>";


    } else if ($pins_count==0) {
        print "
			<div class=\"\" style='margin-bottom:10px;'>";
        print "<div class='clearfix'></div>";

        print "<div class='alert alert-error'><strong>Whoops!</strong> Looks like we haven't found any pins from <strong>$cust_domain</strong> on Pinterest yet!
        <br>Try pinning something and you should see this report fill up within the next 24 hours!</div>";

        print "</div>";

        print "<div class='clearfix'></div>";

        print "<div class='clearfix'></div>";

    } else {

        print "
			<script type='text/javascript'>";


        print "

			var counter = 0;

			function getData() {
			    $.ajax({
	                type: 'GET',
	                data: {'count':counter},
	                url: '/ajax/check-image-processing',
	                dataType: 'html',
	                success: function(data){

	                	//console.log(data);
	                	//console.log($(data).filter('.status').text());
	                	if ($(data).filter('.status').text()==1){
	    	                $('.pins').html($(data).filter('.data'));
	    	                $('[data-toggle=popover]').popover({delay: { show: 0, hide: 0 }, animation: false, trigger: 'hover'});
	    	                $('.wait_video').remove();
	    	                location.reload();
	        	        } else {
	        	        	$('.pins').html($(data).filter('.data'));
	        	            setTimeout('getData()', 1000);
	        	            counter++;
	        	        }
					}
			    });
			}";

        print "
			$(document).ready(function () {
			    getData();
			});";

        print "
			</script>";

        print "</div>";
        print "</div>";

        //print "$site";

        print "<h3>We know, we hate waiting, too.  But it could be worse...</h3>";
        print "<div class='wait_video'>";

        $rand = rand(0, 100);

        if($rand < 50){
            print "<iframe width='560' height='315' src='http://www.youtube.com/embed/JK69jswc41Q?autoplay=0' frameborder='0' allowfullscreen></iframe>";
        } else {
            print "<iframe width='560' height='315' src='http://www.youtube.com/embed/D1UY7eDRXrs?autoplay=0' frameborder='0' allowfullscreen></iframe>";
        }
        print "</div>";

    }
} else {

    print "
			<div class=\"\" style='margin-bottom:10px;'>";
    print "<div class='clearfix'></div>";

    if(isset($_GET['e'])){
        if($_GET['e']==2){
            print "<div class='alert alert-error'><strong>Whoops!</strong> Something went wrong and we were not able to add your domain.  Please make sure it was typed in correctly and try again.  If this problem persists, please contact us by clicking the <i class='icon-help'></i> icon in the upper-right corner and we'll straighten it out for you right away!</div>";
        } else if($_GET['e']==3){
            print "<div class='alert alert-error'><strong>Whoops!</strong> Please enter a domain first!</div>";
        }
    }


    print "<h3 style='font-weight:normal; text-align:center'>Trending pins are based on pinning activity coming from your website.</h3>  <h4 style='text-align:center'>Please enter a domain to track in order to see your Trending pins report.</h4>";
    print "</div>";

    print "<div class='clearfix'></div>";

    print "<div class='clearfix'></div>";

    print "
			<div class=\"brand-mentions\">";
    print "<div class=\"\">";

    print "<div class='row no-site' style='margin-top:50px'>
							<center>
								<form action='/website/add' method='POST' class=\"\">
									<fieldset>

										<div class=\"control-group\">
											<div class=\"controls\">
												<div class=\"input-prepend input-append\">
												    <span class=\"add-on\"><i class=\"icon-earth\"></i> http:// </span>
													<input class=\"input-xlarge\" style='margin-left: -4px;' data-minlength='0' value=\"$cust_domain\" id=\"appendedInputButton\" type=\"text\" name='domain' placeholder='e.g. \"mysite.com\"' pattern='^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}$'>
													<input type='hidden' name='page' value='trending'>
													<button type=\"submit\" class=\"btn btn-success\"'>
														Add Your Domain
													</button>
												</div>
											</div>
											<div class=\"form-actions\">
												<center>
													<span class='muted' style='width:50%'>
														<small class='muted'>\"http://\" and \"www\" not required. Only domains and subdomains can be tracked.
														<br>Sub-directories cannot currently be tracked on Pinterest.
														<br><span class='text-success'><strong>Trackable:</strong></span> etsy.com, macys.com, yoursite.tumblr.com
														<br><span class='text-error'><strong>Not Trackable:</strong></span> etsy.com/shop/mystore, macys.com/mens-clothing</small>
													</span>
												</center>
											</div>

									</fieldset>
								</form>
							</center>
							</div>";

    print "
						<script type='text/javascript'>

							$('.no-site').ready( function () {
								$('.loading1').css('display','none');
							});
							$('.pinners').ready( function () {
								$('.loading2').css('display','none');
							});
							$('.top_pins').ready( function () {
								$('.loading3').css('display','none');
							});

						</script>";

    print "</div>";
    print "</div>";
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



?>