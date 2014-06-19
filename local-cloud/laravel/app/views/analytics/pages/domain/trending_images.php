<?php ini_set('display_errors', 'off');
error_reporting(0);
ini_set('memory_limit', '250M');

$page = "Trending Pins";

$customer = User::find($cust_id);

if(Input::get('sort') == "reach"){
    $sort = Input::get('sort');
    $sort_field = "potential_impressions";
} else if(Input::get('sort') == "pins") {
    $sort = Input::get('sort');
    $sort_field = "pin_count";
} else {
    $sort = "reach";
    $sort_field = "potential_impressions";
}

$cust_domain = $this_domain;


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
?>

<?= $navigation ?>

<?php
if($cust_domain!=""){


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


    //check that pins actually exist for this domain

    $pins_count = 0;
    $acc2 = "select sum(pin_count) as pin_count from cache_domain_daily_counts where domain='$cust_domain' and date >= $last_date";
    $acc2_res = mysql_query($acc2,$conn) or die(mysql_error() . "Line: " . __LINE__);
    while ($b = mysql_fetch_array($acc2_res)) {
        $pins_count = $b['pin_count'];
    }

    if($pins_count!=0){

        //get pin data into separate arrays
        $pins = array();
        $pinners = array();
        //$boards = array();
        $images = array();
        $pinner_ids = array();

        /*
         * if $current_date is today, then we assume we want up-to-the-minute real-time results.
         */
        if($current_date == flat_date('day')){
            $end_date_clause = "";
        } else {
            $end_date_clause = "AND created_at < $current_date";
        }


        $acc2 = "SELECT pin_id, board_id, domain, link,
                count(*) as pin_count,
                sum(repin_count) as repin_count,
                sum(like_count) as like_count,
                sum(comment_count) as comment_count,
                image_url, user_id
                FROM data_pins_new use index (domain_created_at_idx)
                WHERE domain='$cust_domain'
                AND created_at > $last_date
                $end_date_clause
                GROUP BY image_url, user_id
                ORDER BY created_at desc
                limit 10000";
        $acc2_res = mysql_query($acc2,$conn) or die(mysql_error() . "Line: " . __LINE__);
        while ($b = mysql_fetch_array($acc2_res)) {

            $pin_id = $b['pin_id'];
            $user_id = $b['user_id'];
            $image_url = $b['image_url'];

            //set pins array
            $pins[$pin_id] = array();
            $pins[$pin_id]['pin_id'] = $b['pin_id'];
            $pins[$pin_id]['user_id'] = $b['user_id'];
            $pins[$pin_id]['board_id'] = $b['board_id'];
            $pins[$pin_id]['domain'] = $b['domain'];
            $pins[$pin_id]['image_url'] = $b['image_url'];
            $pins[$pin_id]['pin_count'] = $b['pin_count'];
            $pins[$pin_id]['repin_count'] = $b['repin_count'];
            $pins[$pin_id]['like_count'] = $b['like_count'];
            $pins[$pin_id]['comment_count'] = $b['comment_count'];
            $pins[$pin_id]['image_url'] = $b['image_url'];

//            if(!isset($wordcloud)){
//                $wordcloud = preg_replace('/[^A-Za-z0-9 ]/',' ', strtolower($b['description']));
//            } else {
//                $wordcloud = $wordcloud . ' ' . preg_replace('/[^A-Za-z0-9 ]/',' ', strtolower($b['description']));
//            }

            //set pinners array
            if(!empty($user_id)){
                if(!$pinners[$user_id]){
                    $pinners[$user_id] = array();
                    $pinners[$user_id]['user_id'] = $b['user_id'];
                    $pinners[$user_id]['pin_count'] = $b['pin_count'];
                    $pinners[$user_id]['repin_count'] = $b['repin_count'];
                    $pinners[$user_id]['like_count'] = $b['like_count'];
                    $pinners[$user_id]['comment_count'] = $b['comment_count'];

                    array_push($pinner_ids, $user_id);

                } else {
                    $pinners[$user_id]['pin_count'] += $b['pin_count'];
                    $pinners[$user_id]['repin_count'] += $b['repin_count'];
                    $pinners[$user_id]['like_count'] += $b['like_count'];
                    $pinners[$user_id]['comment_count'] += $b['comment_count'];
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

        if (count($pins) > 0) {

            $has_pins = true;

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
            $total_impressions = 0;
            foreach($pins as $b){

                $pin_id = $b['pin_id'];
                $user_id = $b['user_id'];
                //$board_url = $b['board_url'];
                $image_id = $b['image_url'];


                //set images array
                if(!isset($images[$image_id])){
                    $images[$image_id]                          = array();
                    $images[$image_id]['image_url']             = $b['image_url'];
                    $images[$image_id]['pin_count']             = $b['pin_count'];
                    $images[$image_id]['repin_count']           = $b['repin_count'];
                    $images[$image_id]['like_count']            = $b['like_count'];
                    $images[$image_id]['comment_count']         = $b['comment_count'];
                    $images[$image_id]['potential_impressions'] = $pinners[$user_id]['follower_count'];

                    $total_impressions += $pinners[$user_id]['follower_count'];

                    $images[$image_id]['pinners'][$user_id]            = array();
                    $images[$image_id]['pinners'][$user_id]['user_id'] = $pinners[$user_id]['user_id'];
                    @$images[$image_id]['pinners'][$user_id]['username'] = $pinners[$user_id]['username'];
                    @$images[$image_id]['pinners'][$user_id]['first_name'] = $pinners[$user_id]['first_name'];
                    @$images[$image_id]['pinners'][$user_id]['last_name'] = $pinners[$user_id]['last_name'];
                    @$images[$image_id]['pinners'][$user_id]['image'] = $pinners[$user_id]['image'];
                    @$images[$image_id]['pinners'][$user_id]['domain_url'] = $pinners[$user_id]['domain_url'];
                    @$images[$image_id]['pinners'][$user_id]['website_url'] = $pinners[$user_id]['website_url'];
                    @$images[$image_id]['pinners'][$user_id]['facebook_url'] = $pinners[$user_id]['facebook_url'];
                    @$images[$image_id]['pinners'][$user_id]['twitter_url'] = $pinners[$user_id]['twitter_url'];
                    @$images[$image_id]['pinners'][$user_id]['location'] = $pinners[$user_id]['location'];
                    @$images[$image_id]['pinners'][$user_id]['pin_count'] = $pinners[$user_id]['pin_count'];
                    @$images[$image_id]['pinners'][$user_id]['board_count'] = $pinners[$user_id]['board_count'];
                    @$images[$image_id]['pinners'][$user_id]['follower_count'] = $pinners[$user_id]['follower_count'];
                    @$images[$image_id]['pinners'][$user_id]['following_count'] = $pinners[$user_id]['following_count'];
                    @$images[$image_id]['pinners'][$user_id]['created_at'] = $pinners[$user_id]['created_at'];
                    @$images[$image_id]['pinners'][$user_id]['pin_count'] = $pinners[$user_id]['pin_count'];
                    @$images[$image_id]['pinners'][$user_id]['reach'] = $pinners[$user_id]['pin_count']*$pinners[$user_id]['follower_count'];

                    @$pinners[$user_id]['reach'] = $pinners[$user_id]['pin_count']*$pinners[$user_id]['follower_count'];

                    $pins[$pin_id]['user_id']     = $b['user_id'];
                    @$pins[$pin_id]['username']   = $pinners[$user_id]['username'];
                    @$pins[$pin_id]['first_name'] = $pinners[$user_id]['first_name'];
                    @$pins[$pin_id]['last_name']  = $pinners[$user_id]['last_name'];


                } else {
                    $images[$image_id]['pin_count']             += $b['pin_count'];
                    $images[$image_id]['repin_count']           += $b['repin_count'];
                    $images[$image_id]['like_count']            += $b['like_count'];
                    $images[$image_id]['comment_count']         += $b['comment_count'];
                    $images[$image_id]['potential_impressions'] += $pinners[$user_id]['follower_count'];

                    $total_impressions += $pinners[$user_id]['follower_count'];

                    $images[$image_id]['pinners'][$user_id] = array();
                    $images[$image_id]['pinners'][$user_id]['user_id'] = $pinners[$user_id]['user_id'];
                    @$images[$image_id]['pinners'][$user_id]['username'] = $pinners[$user_id]['username'];
                    @$images[$image_id]['pinners'][$user_id]['first_name'] = $pinners[$user_id]['first_name'];
                    @$images[$image_id]['pinners'][$user_id]['last_name'] = $pinners[$user_id]['last_name'];
                    @$images[$image_id]['pinners'][$user_id]['image'] = $pinners[$user_id]['image'];
                    @$images[$image_id]['pinners'][$user_id]['domain_url'] = $pinners[$user_id]['domain_url'];
                    @$images[$image_id]['pinners'][$user_id]['website_url'] = $pinners[$user_id]['website_url'];
                    @$images[$image_id]['pinners'][$user_id]['facebook_url'] = $pinners[$user_id]['facebook_url'];
                    @$images[$image_id]['pinners'][$user_id]['twitter_url'] = $pinners[$user_id]['twitter_url'];
                    @$images[$image_id]['pinners'][$user_id]['location'] = $pinners[$user_id]['location'];
                    @$images[$image_id]['pinners'][$user_id]['pin_count'] = $pinners[$user_id]['pin_count'];
                    @$images[$image_id]['pinners'][$user_id]['board_count'] = $pinners[$user_id]['board_count'];
                    @$images[$image_id]['pinners'][$user_id]['follower_count'] = $pinners[$user_id]['follower_count'];
                    @$images[$image_id]['pinners'][$user_id]['following_count'] = $pinners[$user_id]['following_count'];
                    @$images[$image_id]['pinners'][$user_id]['created_at'] = $pinners[$user_id]['created_at'];
                    @$images[$image_id]['pinners'][$user_id]['pin_count'] = $pinners[$user_id]['pin_count'];
                    @$images[$image_id]['pinners'][$user_id]['reach'] = $pinners[$user_id]['pin_count']*$pinners[$user_id]['follower_count'];

                    @$pinners[$user_id]['reach'] = $pinners[$user_id]['pin_count']*$pinners[$user_id]['follower_count'];

                    $pins[$pin_id]['user_id']     = $b['user_id'];
                    @$pins[$pin_id]['username']   = $pinners[$user_id]['username'];
                    @$pins[$pin_id]['first_name'] = $pinners[$user_id]['first_name'];
                    @$pins[$pin_id]['last_name']  = $pinners[$user_id]['last_name'];
                }


            }

            usort($images, "$sort_field");

            $pin_threshold = ($sort == 'pins' ? 1 : 1);

            /*
             * Iterate through pinners to pick out ones to highlight.
             */
            foreach($pinners as $pinner){
                $user_id = $pinner['user_id'];
                if($pinner['reach']/$total_impressions > 0.05) {
                    $pinners[$user_id]['highlight'] = true;
                }
            }
        } else {
            $has_pins = false;
        }

        //usort($pins, "timestamp");

        //get date of the last pin

        ?>



        
        <div class='row-fluid'>
        <div class='span8'>


        <div class='accordion' id='accordion3' style='margin-bottom:25px; margin-left:0px'>
        <div class='accordion-group' style='margin-bottom:0px; border-bottom:none'>
            <div class='section-header'>
                <div class='accordion-toggle section-header' data-parent='#accordion3'
                     href='#collapseTwo' style='cursor:default'>
                    <h2 style='float:left; font-weight:normal;font-size:22px'>
                        Trending Images from <?=$cust_domain;?>:
                    </h2>
                    <div class='help-icon-form pull-right' style='margin:8px 0 0 5px;'>
                        <a class='' data-toggle='popover' data-container='body'
                           data-original-title='What Are My Trending Pins?'
                           data-content="Your Trending Images are the most recent images being
                                       pinned from <?=$cust_domain;?>.
                                       Using image recognition,
                                       this report shows you which images are being pinned the most
                                       (and who has pinned them).
                                       The count on the upper left-hand corner of each Pinner's image is the
                                       number of times they've recently pinned from <?=$cust_domain;?>."
                           data-trigger='hover'
                           data-placement='bottom'>
                            <i id='header-icon' class='icon-help'></i>
                        </a>
                    </div>
                    <span class="btn-group pull-right" style="margin:5px 15px 0 0;">
                        <a href="?sort=reach" type="button" class="btn <?= ($sort == 'reach') ? 'active' : '' ?>">
                            Reach
                        </a>
                        <a href="?sort=pins" type="button" class="btn <?= ($sort == 'pins') ? 'active' : '' ?>">
                            Pins
                        </a>
                    </span>
                    <span class="pull-right" style="margin:11px 0px 0 0;">
                        Sort By: &nbsp;
                    </span>
                </div>
            </div>
        </div>

        <div class='clearfix section-header'></div>


        <div id='collapseTwo' class='accordion-body collapse in'>
            <div class='accordion-inner'>

                <div class="row no-margin">

<?php


						print "<div class='tab-content'>";

						//trending images tab
						print "<div class='tab-pane active' id='trending'>";
							print "<div class=\"\">";
								print "<div class=\"row-fluid\">
											<div class=\"span4\"><center>Image</center></div>
											<div class=\"span3\"><center>Reach</center></div>
											<div class=\"span5\">Pinners</div>
										</div>
										<hr>";


								$image_show_count = 0;
                                $show_extra_pins = false;
								foreach($images as $p){
                                    $image = $p['image_url'];
                                    $pin_count = number_format($p['pin_count'],0);
                                    $repin_count = $p['repin_count'];
                                    $like_count = $p['like_count'];
                                    $comment_count = $p['comment_count'];
                                    $pin_pinners = $p['pinners'];
                                    $pinner_count = count($p['pinners']);
                                    $potential_impressions = number_format($p['potential_impressions'],0);

                                    if($pin_count > $pin_threshold && $image_show_count < 100){
                                        $image_show_count++;
                                        print "<div class=\"row-fluid\">
												<div class=\"span4\">
												    <center>
												        <div class='trending-image-pin-wrapper'>
												            <div class='trending-image-pin-count'>$pin_count pins</div>
												            <img src='$image' class='trending-pin-image'>
												        </div>
                                                    </center>
                                                </div>
									  			<div class=\"span3\">
									  			    <center>

									  			        <strong>
									  			            <span class='potential-impressions' style='display:block;line-height:40px;'>$potential_impressions</span>
                                                        </strong>
                                                        Potential Impressions
									  			    </center>


                                                </div>";

//										  			print "
//										  			<div class=\"span2\">
//											  			<strong>$repin_count</strong> Total Repins<br>
//											  			<strong>$like_count</strong> Total Likes<br>
//											  			<strong>$comment_count</strong> Total Comments<br>
//										  			</div>";

                                        print "
									  			<div class=\"span5\">";

                                        usort($pin_pinners, "reach");

                                        $count = 0;
                                        $show_more_pinners = true;
                                        foreach($pin_pinners as $u){
                                            if($count < 12){
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
                                                $p_follower_count = number_format($u['follower_count'],0);
                                                $p_reach     = number_format($u['reach'],0);

                                                if($count == 0){
                                                    $highlight = "style='width:115px;height:115px;'";
                                                } else {
                                                    $highlight = "";
                                                }
                                                print "<span class='trending-image-pinner' $highlight>
                                                    <a target='_blank' href='http://pinterest.com/$p_username'
                                                       style='vertical-align:top;' data-toggle='popover'
                                                        data-container='body' data-placement='top'
                                                        data-content='$p_first_name $p_last_name <br>";

                                                if($p_pin_count==1){
                                                    print "<strong>$p_pin_count</strong> pin";
                                                } else {
                                                    print "<strong>$p_pin_count</strong> pins";
                                                }

                                                print " <br><strong>$p_follower_count</strong> followers'>
                                                        <div class='trending-image-pinner-engage'>$p_pin_count</div>
                                                        <img src='$p_image' $highlight>
                                                    </a>
                                                </span>";

                                                $count++;
                                            } elseif ($show_more_pinners == true){
                                                $show_more_pinners = false;
                                                $remaining_pinner_count = $pinner_count - 12;
                                                print "
                                                    <div><em>and $remaining_pinner_count more...</em>
                                                    </div>";
                                            }
                                        }
                                        print "	</div>
									  	</div>
									  	<hr>";
                                    } else if ($pin_count == $pin_threshold && $image_show_count < 100) {
                                        $show_extra_pins = true;
                                    }

                                }


        /**
         * If we've gone through all of the images that were pinned more than once and are only
         * left with those pinned just once (or if all images were only pinned once), then we
         * show these additional images below with an alert.
         */

                                if (($image_show_count == 0 || $image_show_count < 100) && $show_extra_pins) {

                                    print "
                                    <div class='row-fluid'>
                                        <div class='span12'>
                                            <div class='alert alert-block alert-info'>";
                                                if ($image_show_count == 0) {
                                                    print "<strong>Note:</strong> All pins in this date range have unique images, pinned only once.";
                                                } else {
                                                    print "<strong>Note:</strong> All pins below this point are unique images, pinned only once.";
                                                }
                                    print "
                                            </div>
                                        </div>
                                    </div>";
                                    $next_image_show_count = 0;
                                    foreach($images as $p){
                                        $image = $p['image_url'];
                                        $pin_count = number_format($p['pin_count'],0);
                                        $repin_count = $p['repin_count'];
                                        $like_count = $p['like_count'];
                                        $comment_count = $p['comment_count'];
                                        $pin_pinners = $p['pinners'];
                                        $pinner_count = count($p['pinners']);
                                        $potential_impressions = number_format($p['potential_impressions'],0);

                                        if($pin_count >= $pin_threshold && $image_show_count < 100 && $next_image_show_count >= $image_show_count){
                                            $image_show_count++;
                                            $next_image_show_count++;
                                            print "<div class=\"row-fluid\">
												<div class=\"span4\">
												    <center>
												        <div class='trending-image-pin-wrapper'>
												            <div class='trending-image-pin-count'>$pin_count pins</div>
												            <img src='$image' class='trending-pin-image'>
												        </div>
                                                    </center>
                                                </div>
									  			<div class=\"span3\">
									  			    <center>

									  			        <strong>
									  			            <span class='potential-impressions' style='display:block;line-height:40px;'>$potential_impressions</span>
                                                        </strong>
                                                        Potential Impressions
									  			    </center>


                                                </div>";

//										  			print "
//										  			<div class=\"span2\">
//											  			<strong>$repin_count</strong> Total Repins<br>
//											  			<strong>$like_count</strong> Total Likes<br>
//											  			<strong>$comment_count</strong> Total Comments<br>
//										  			</div>";

                                            print "
									  			<div class=\"span5\">";

                                            usort($pin_pinners, "reach");

                                            $count = 0;
                                            $show_more_pinners = true;
                                            foreach($pin_pinners as $u){
                                                if($count < 12){
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
                                                    $p_follower_count = number_format($u['follower_count'],0);
                                                    $p_reach     = number_format($u['reach'],0);

                                                    if($count == 0){
                                                        $highlight = "style='width:115px;height:115px;'";
                                                    } else {
                                                        $highlight = "";
                                                    }
                                                    print "<span class='trending-image-pinner' $highlight>
                                                    <a target='_blank' href='http://pinterest.com/$p_username'
                                                       style='vertical-align:top;' data-toggle='popover'
                                                        data-container='body' data-placement='top'
                                                        data-content='$p_first_name $p_last_name <br>";

                                                    if($p_pin_count==1){
                                                        print "<strong>$p_pin_count</strong> pin";
                                                    } else {
                                                        print "<strong>$p_pin_count</strong> pins";
                                                    }

                                                    print " <br><strong>$p_follower_count</strong> followers'>
                                                        <div class='trending-image-pinner-engage'>$p_pin_count</div>
                                                        <img src='$p_image' $highlight>
                                                    </a>
                                                </span>";

                                                    $count++;
                                                } elseif ($show_more_pinners == true){
                                                    $show_more_pinners = false;
                                                    $remaining_pinner_count = $pinner_count - 12;
                                                    print "
                                                    <div><em>and $remaining_pinner_count more...</em>
                                                    </div>";
                                                }
                                            }
                                            print "	</div>
									  	</div>
									  	<hr>";
                                        } else if ($next_image_show_count < $image_show_count) {
                                            $next_image_show_count++;
                                        }

                                    }
                                }
        /**
         * If there are still no images shown at this point, then there must be no pins at all
         * in the selected date range, so we show an alert message to let the user know this.
         */
                                if ($image_show_count == 0) {

                                    print "
                                        <div class='row-fluid'>
                                            <div class='span12'>
                                                <div class='alert alert-block alert-info'>
                                                    <strong>Note:</strong> No pins found from $cust_domain for this date range!
                                                </div>
                                            </div>
                                        </div>";
                                }

							print "</div>";
						print "</div>";

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

        print "
        <script>
	        $('.potential-impressions').fitText(0.5, { minFontSize: '25px', maxFontSize: '40px' });
        </script>";


    } else {
        print "
			<div class=\"\" style='margin-bottom:10px;'>";
        print "<div class='clearfix'></div>";

        print "<div class='alert alert-info'>Looks like we couldn't find any pins from <strong>$cust_domain</strong> on Pinterest over the last $day_range days.
        <br>Try pinning something from your domain, or broadening your date range to find pins that might have been pinned earlier.</div>";

        print "</div>";

        print "<div class='clearfix'></div>";

        print "<div class='clearfix'></div>";

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

function potential_impressions($a, $b) {
    $t = "potential_impressions";

    if ($a["$t"] < $b["$t"]) {
        return 1;
    } else if ($a["$t"] == $b["$t"]) {
        return 0;
    } else {
        return -1;
    }
}

function reach($a, $b) {
    $t = "reach";

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