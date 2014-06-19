<?php ini_set('display_errors', 'off');
error_reporting(0);


?>


<div class="clearfix"></div>
<div class="">


    <div class="accordion" id="accordion3" style="margin-bottom:25px">
        <div class="accordion-group" style="margin-bottom:0px">

        <?php if ($type == "followers/influential" || $type == "followers/newest"): ?>

            <div class='section-header'>
                <div class='accordion-toggle section-header' style='cursor:default'>
                    <span class="pull-left" style="margin:11px 0px 0 5px;">
                        Sort By: &nbsp;
                    </span>
                    <span class="btn-group pull-left" style="margin:5px 5px 5px 5px;">
                        <a href="<?= URL::route('newest-followers') ?>" type="button" class="btn <?= ($type == 'followers/newest') ? 'active' : '' ?>">
                            Newest
                        </a>
                        <a <?= ($customer->hasFeature('nav_fans') ? 'href="'. URL::route('influential-followers') . '"' : '') ?> type="button" id="influential-followers-toggle-btn" class="btn <?= ($type == 'followers/influential') ? 'active' : '' ?> <?= ($customer->hasFeature('nav_fans') ? '' : 'disabled go-pro') ?>">
                            Most Influential
                        </a>
                    </span>
                </div>
            </div>
        </div>
        <div class='clearfix section-header'></div>
        <?php endif ?>


        <div id='collapseTwo' class='accordion-body collapse in'>
            <div class='accordion-inner' style="padding-top:15px;">

                <div class="row margin-fix influencers">


<?= $popover_custom_date; ?>
<?= $popover_report_toggle; ?>

<?php

print "<div class=\"\" style='text-align:left;'>";
print "<table class=\"table table-striped table-bordered\">
					<thead>

							<th><center><strong>Rank</strong></center></th>
							<th style='min-width:91px;'><center><strong>Photo</strong></center></th>
							<th><center><strong>Profile Info</strong></center></th>";
if ($type == "followers/influential") {

    print "
                            <th  class='sorting_desc'><center><strong>Followers</strong></center></th>";

} else if ($type == "followers/newest") {

    print "
                            <th  class=''><center><strong>Followers</strong></center></th>";

} else if ($type == "domain-pinners") {

    print "
							<th class='sorting_desc' style='width:90px;'><center><strong>Domain Mentions</strong></center></th>
							<th><center><strong>Followers</strong></center></th>
							<th class='table-header-icon' style='min-width:100px;'>
							    <center style='width:105px;'>
							        <span style='margin-left: -10px;'>
                                        <strong>
                                            Potential Impressions
                                        </strong>
                                    </span>
							        <span class='pull-right'>
                                        <i class='icon-help tip has-tip tip-top'
                                            style='text-shadow: 0px -5px 12px #fff, 0 0 0 #000, 1px 1px 1px rgba(255,255,255,0.4);
                                            color: rgba(0,174,230,0.8);'
                                            data-toggle='popover'
                                            data-container='body'
                                            data-placement='left'
                                            data-content=\"<u><strong>Potential Impressions</strong></u>: how many times your content
                                            may have surfaced in front of fans after being pinned.
                                            <br><br><strong>The <u>2 Main Components</u> of Potential Impressions</strong>:
                                            <br> (1) The Pinner's Influence
                                            <br> (2) The number of times they've Pinned from your domain\">
                                        </i>
                                    </span>
                                </center>
                            </th>";
} else if ($type == "top-repinners") {

    print "
							<th class='sorting_desc' style='min-width:60px;padding: 10px 8px 4px;'><center style='margin-left:-10px;'><strong>Repins</strong></center></th>
							<th><center><strong>Followers</strong></center></th>
							<th class='table-header-icon' style='min-width:164px;'>
							    <center>
							        <span style='margin-left: -10px;'>
                                        <strong>
                                            Potential Impressions
                                        </strong>
                                    </span>
                                    <span class='pull-right'>
                                        <i class='icon-help tip has-tip tip-top'
                                            style='text-shadow: 0px -5px 12px #fff, 0 0 0 #000, 1px 1px 1px rgba(255,255,255,0.4);
                                            color: rgba(0,174,230,0.8);'
                                            data-toggle='popover'
                                            data-container='body'
                                            data-placement='left'
                                            data-content=\"<u><strong>Potential Impressions</strong></u>: how many times your content
                                            may have surfaced in front of fans after being pinned.
                                            <br><br><strong>The <u>2 Main Components</u> of Potential Impressions</strong>:
                                            <br> (1) Your Repinner's Influence
                                            <br> (2) The number of times they've Repinned your pins\">
                                        </i>
                                    </span>
                                </center>
                            </th>";
} else if ($type == "most-valuable-pinners") {
    print "<th class='$visit_header'>
                                        <a href='/most-valuable-pinners?rev_sort=visits'>
                                            <center><strong>Visits</strong></center>
                                        </a>
                                    </th>";
    print "<th class='$conversion_header'>
                                        <a href='/most-valuable-pinners?rev_sort=transactions'>
                                            <center><strong>Conversions</strong></center>
                                        </a>
                                    </th>";
    print "<th class='$revenue_header'>
                                        <a href='/most-valuable-pinners?rev_sort=revenue'>
                                            <center><strong>Revenue</strong></center>
                                        </a>
                                    </th>";
    print "<th><center><strong>Followers</strong></center></th>";
}
if ($has_analytics && ($type != "most-valuable-pinners" && $type != "followers/newest")) {
    if ($type == "domain-pinners") {
        print "<th style='width:70px;'><center><strong>Visits Generated</strong></center></th>";
    } else {
        print "<th><center><strong>Visits Generated</strong></center></th>";
    }
}
print "
					</thead>";

print "<tbody>";


//					usort($data_calced, "cmp");
$rank = 1;
$added = 1;
$total_revenue = 0;
foreach ($data_calced as $d) {

    if ($rank > $rank_limit) {
        break;
    }

    $follower_image    = $d['image'];
    $follower_username = $d['username'];
    $follower_user_id  = $d['user_id'];
    $follower_footprint = $d['footprint'];




    $follower_display_name = str_replace("'", "", $d['display_name']);

    if (strlen($follower_display_name) == 0 || $follower_display_name == "Unauthorized") {
        $follower_display_name = $d['username'];
    }

    if (isset($d['repins'])) {
        $follower_repins = formatNumber($d['repins']);
    }

    if ($has_analytics) {
        @$follower_visits = formatNumber($d['visits']);
        @$follower_conversions = formatNumber($d['conversions']);
    }

    //$follower_boards = formatNumber($d['board_count']);
    $follower_is_following = 0;
    //$follower_is_following = $d['profile_followers'];
    $follower_followers      = formatNumber($d['followers']);
    $follower_pins           = formatNumber($d['pins']);
    $follower_influence      = formatNumber($d['influence']);
    $follower_location_city  = $d['location_city'];
    $follower_location_state = $d['location_state'];
    $follower_website        = $d['website'];
    $follower_website_print  = $d['website_print'];
    $follower_meta           = $d['user_meta'];
    $follower_link           = "http://www.pinterest.com/" . $d['username'] . "/";


    $revenue = number_format($d['revenue'], 2);


        if ($type == "followers/newest") {

            print "
                                <tr>";


            print "<td style='text-align:left;'>$rank</td>
                                <td style='text-align:center; height:75px;'><a href=\"$follower_link\"><img style='height:75px;' src=\"$follower_image\" /></a></td>
                                <td style='text-align:left;padding:0px;'>
                                    $follower_meta
                                </td>
                                <td style='text-align:right;'>$follower_followers</td>";

            print "</tr>";

            $added++;
        }



        //print followers
        if ($type == "followers/influential") {

            print "
							<tr>";


            print "<td style='text-align:left;'>$rank</td>
							<td style='text-align:center; height:75px;'><a href=\"$follower_link\"><img style='height:75px;' src=\"$follower_image\" /></a></td>
							<td style='text-align:left;padding:0px;'>
							    $follower_meta
							</td>
							<td style='text-align:right;'>$follower_followers</td>";

            if ($has_analytics) {
                print "<td style='text-align:right;'><strong></strong>$follower_visits</td>";
            }

            print "</tr>";

            $added++;
        }


        //print pinners
        if ($type == "domain-pinners" && $follower_pins != "-") {

            print "
							<tr>";


            print "<td style='text-align:left;'>$rank</td>
							<td style='text-align:center; height:75px;'><a href=\"$follower_link\"><img style='height:75px;' src=\"$follower_image\" /></a></td>
							<td style='text-align:left;padding:0px;'>
							    $follower_meta
							</td>
							<td style='text-align:right;'>$follower_pins</td>

							<td style='text-align:right;'>$follower_followers</td>

							<td style='text-align:right;'>$follower_influence</td>";

            if ($has_analytics) {
                print "<td style='text-align:right;'><strong></strong>$follower_visits</td>";
            }

            print "</tr>";

            $added++;

        }


        //print Repinners
        if ($type == "top-repinners") {

            print "
							<tr>";


            print "<td style='text-align:left;'>$rank</td>
							<td style='text-align:center; height:75px;'><a href=\"$follower_link\"><img style='height:75px;' src=\"$follower_image\" /></a></td>
							<td style='text-align:left;padding:0px;'>
							    $follower_meta
							</td>

							<td style='text-align:right;'>$follower_repins</td>

							<td style='text-align:right;'>$follower_followers</td>

							<td style='text-align:right;'>$follower_influence</td>";

            if ($has_analytics) {
                print "<td style='text-align:right;'><strong></strong>$follower_visits</td>";
            }

            print "</tr>";

            $added++;

        }


        //print revenue generators
        if ($type == "most-valuable-pinners" && ($revenue != 0 || $follower_visits != 0 || $follower_conversions != 0)) {

            print "
							<tr>";


            print "<td style='text-align:left;'>$rank</td>
							<td style='text-align:center; height:75px;'><a href=\"$follower_link\"><img style='height:75px;' src=\"$follower_image\" /></a></td>
							<td style='text-align:left;padding:0px;'>
							    $follower_meta
							</td>
							<td style='text-align:right;'>$follower_visits</td>
							<td style='text-align:right;'>$follower_conversions</td>
							<td style='text-align:right;'><strong>\$</strong>$revenue</td>
							<td style='text-align:right;'>$follower_followers</td>";

            print "</tr>";

            $added++;
            $total_revenue = $total_revenue + $revenue;
        }

        if ($type == "top-advocates") {

            print "
							<tr>";


            print "<td style='text-align:left;'>$rank</td>
							<td style='text-align:center; height:75px;'><a href=\"$follower_link\"><img style='height:75px;' src=\"$follower_image\" /></a></td>
							<td style='text-align:left;padding:0px;'>
                            <div class='influencer-meta'>
								<div class='username'><a target=_blank href=\"http://www.pinterest.com/$follower_username\"><strong>$follower_display_name</strong></a> ";

            if ($type == "domain-pinners") {
                if ($follower_username == $user_username) {
                    print " <span class='badge badge-warning'>You</span>";
                }
            }

            print "</div>
								<div class='badges'>";

            if ($type == "domain-pinners" && $follower_username != $user_username) {
                if ($follower_is_following != 0) {
                    print "<span class='badge badge-info'>Follower</span>";
                } else {
                    print "<span class='badge badge-success'>New Fan</span>";
                }
            }


            print "</div>
								<br />
								<div class='clearfix'></div>
								<div class='location'>";

            if ($follower_location_city != "" && $follower_location_state != "") {
                print "<span style='font-size:16px; color:#555'><i class='icon-location'></i></span> " . $follower_location_city . ", " . $follower_location_state . " ";
            } elseif ($follower_location_city != "" && $follower_location_state == "") {
                print "<span style='font-size:16px; color:#555'><i class='icon-location'></i></span> " . $follower_location_city . " ";
            } else {
                print "&nbsp;";
            }

                        print "</div>
                                <div>
								<br />
								" . (($d["twitter"] != "") ? "<a target=_blank href=\"$follower_twitter\" class='social-icons twitter'> <i class='icon-twitter-2'></i> </a>" : " ") . "
								" . (($d["facebook"] != "") ? "<a target=_blank href=\"$follower_facebook\" class='social-icons facebook'> <i class='icon-facebook-2'></i> </a> " : " ") . "
								" . (($d["website"] != "") ? "<a target='_blank' href='http://" . $follower_website . "' class='social-icons website'> <i class='icon-earth'></i>&nbsp; $follower_website </a>" : " ") . "
                                </div>
                                <div class='clearfix'></div>
                            </div>
                            $follower_footprint_bar
							</td>
							<td style='text-align:right;'>$follower_followers</td>
							<td style='text-align:right;'>$follower_pins</td>
							<td style='text-align:right;'>$follower_influence</td>";

            if ($has_analytics) {
                print "<td style='text-align:right;'><strong></strong>$follower_visits</td>";
            }

            print "</tr>";

            $added++;

        }
        $rank++;



}

print "</tbody>";
print "</table>";

if ($rank == 1) {
    print "<div class=\"alert\">Your Fans & Influencers Report is currently being refreshed. Please check back shortly :) </div>";
} else {

    if (($added == 1 && $type == "followers") && $user_username != "tempspaz") {
        print "<div class=\"alert alert-block\">Your Top Followers report is still being created. Please check back shortly :) </div>";
    }

    if (($added == 1 && $type == "followers") && $user_username == "tempspaz") {
        print "<div class=\"alert alert-block\">Hey Mike - what's your secret?  You're gaining new followers so fast that we can't even keep up! </div>";
    }

    if (($added == 1 && $type == "domain-pinners") && $total_brand_mention_pins != 0) {
        if ($domain != "" || $has_analytics) {
            print "<div class=\"alert alert-block\">Your Top Pinners report may still be in the works. Please check back shortly :)  </di>";
        } else {
            print "<div class=\"alert alert-block\">You must first track a website in order to see your Brand Mentions report.  <br><br>Click on the <i class='icon-mail'></i> icon in the upper right corner to message us with the site you'd like to track, and we'll be happy to set it up for you :)  </div>";
        }
    }

    if (($added == 1 && $type == "domain-pinners") && $total_brand_mention_pins == 0) {
        print "<div class=\"alert info alert-block\">Looks like you don't have anyone pinning about you yet!  If you are not tracking a website, then it is normal for this message to appear :) </div>";
    }

    if ($added == 1 && $type == "top-advocates") {
        print "<div class=\"alert alert-block\">You Top Brand Advocates report is still being created. Please check back shortly :) </div>";
    }

    if (($added == 1 && $type == "most-valuable-pinners") && $total_revenue == 0) {
        print "<div class=\"alert alert-block\">You must have Revenue Goals established in Google Analytics for this feature to work.  If you've created Revenue Goals, you may not yet be generating revenue from Pinterest - go pin some products!</div>";
    }

}
print "</div>";
print "</div>";
print "</div>";
print "</div>";
print "</div>";
print "</div>";


echo $export_popover;


function cmp($a, $b)
{
    $type = $_GET['type'];
    if (!$type) {
        $key = "influence";
    }

    if ($type == "top-advocates") {
        $key = "influence";
    } else if ($type == "domain-pinners") {
        $key = "pins";
    } else if ($type == "followers") {
        $key = "followers";
    } else if ($type == "most-valuable-pinners") {
        $key = "revenue";
    }

    if ($a["$key"] > $b["$key"]) {
        return -1;
    } else if ($a["$key"] < $b["$key"]) {
        return 1;
    } else {
        return 0;
    }
}

function formatNumber($x)
{
    if (!$x) {
        return "-";
    } else {
        return number_format($x);
    }
}



?>
