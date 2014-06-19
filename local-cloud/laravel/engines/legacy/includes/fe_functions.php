<?php

date_default_timezone_set('America/Chicago');


    function createPopover($id, $trigger="hover", $placement="",$title="",$referral="",$plan="",$content="Upgrade to Unlock this Feature"){




        $button = "<a class=\"btn-link\" href=\"/upgrade?ref=".$referral."&plan=".$plan."\"><button class=\"btn btn-success btn-block\"><i class=\"icon-arrow-right\"></i> See Plans & Pricing</button></a>";
        $button .= "<a class=\"btn-link \" style=\"margin-top:5px; display: block\" href=\"/demo/pro?follow=back\"><button class=\"btn btn-small btn-block\">or Try the Pro Demo</button></a>";

        $popover = "
                <script>
                    $(document).ready(function() {
                        $('$id').attr({
                            'data-toggle':'popover-click',
                            'data-container':'body',
                            'data-placement':'$placement',
                            'data-title':'$title',
                            'data-content': function () {
                                        return '<div class=\"popover-box\">$content<br> $button</div>';
                                    }
                        })
                    });
                </script>";

        return $popover;

    }


    function createPopoverOld($id, $trigger="hover", $placement="",$title="",$referral="",$plan="",$content="Upgrade to Unlock this Feature"){




        $button = "<a class='btn-link' href='/upgrade?ref=".$referral."&plan=".$plan."'><button class='btn btn-success btn-block'><i class='icon-arrow-right'></i> Learn More</button></a>";

        $popover = "
            <script>
                $(document).ready(function() {
                    $('$id').popover({
                        'trigger':'$trigger',
                        'container':'$id',
                        'placement':'$placement',
                        'title':'$title',
                        'content': function () {
                                    return \"<div class='popover-box'>$content<br> $button</div>\";
                                },
                        'html':true
                    }).on({
                        show: function (e) {
                            var thispop = $(this);

                            // Currently hovering popover
                            thispop.data('hoveringPopover', true);

                            // If it's still waiting to determine if it can be hovered, don't allow other handlers
                            if (thispop.data('waitingForPopoverTO')) {
                                e.stopImmediatePropagation();
                            }
                        },
                        hide: function (e) {
                            var thispop = $(this);

                            // If timeout was reached, allow hide to occur
                            if (thispop.data('forceHidePopover')) {
                                thispop.data('forceHidePopover', false);
                                return true;
                            }

                            // Prevent other `hide` handlers from executing
                            e.stopImmediatePropagation();

                            // Reset timeout checker
                            clearTimeout(thispop.data('popoverTO'));

                            // No longer hovering popover
                            thispop.data('hoveringPopover', false);

                            // Flag for `show` event
                            thispop.data('waitingForPopoverTO', true);

                              // In 1500ms, check to see if the popover is still not being hovered
                            thispop.data('popoverTO', setTimeout(function () {
                                // If not being hovered, force the hide
                                if (!thispop.data('hoveringPopover')) {
                                    thispop.data('forceHidePopover', true);
                                    thispop.data('waitingForPopoverTO', false);
                                    thispop.popover('hide');
                                }
                            }, 500));

                            // Stop default behavior
                            return false;
                        }
                    });
                });
            </script>";

        return $popover;

    }


    function createNavPopover($id, $trigger="hover", $placement="",$title="",$referral="",$plan="",$content=""){

        $button = "<a class=\"btn-link\" href=\"/upgrade?ref=".$referral."&plan=".$plan."\"><button class=\"btn btn-success btn-block\"><i class=\"icon-arrow-right\"></i> Learn More</button></a>";


        $popover = "


                    $('$id').popover({
                        'trigger':'$trigger',
                        'container':'$id',
                        'placement':'$placement',
                        'title':'$title',
                        'content': function () {
                                    return '<div class=\"popover-box\">$content<br> $button</div>';
                                },
                        'html':true
                    }).on({
                        show: function (e) {
                            var thispop = $(this);

                            // Currently hovering popover
                            thispop.data('hoveringPopover', true);

                            // If it's still waiting to determine if it can be hovered, don't allow other handlers
                            if (thispop.data('waitingForPopoverTO')) {
                                e.stopImmediatePropagation();
                            }
                        },
                        hide: function (e) {
                            var thispop = $(this);

                            // If timeout was reached, allow hide to occur
                            if (thispop.data('forceHidePopover')) {
                                thispop.data('forceHidePopover', false);
                                return true;
                            }

                            // Prevent other `hide` handlers from executing
                            e.stopImmediatePropagation();

                            // Reset timeout checker
                            clearTimeout(thispop.data('popoverTO'));

                            // No longer hovering popover
                            thispop.data('hoveringPopover', false);

                            // Flag for `show` event
                            thispop.data('waitingForPopoverTO', true);

                            // In 1500ms, check to see if the popover is still not being hovered
                            thispop.data('popoverTO', setTimeout(function () {
                                // If not being hovered, force the hide
                                if (!thispop.data('hoveringPopover')) {
                                    thispop.data('forceHidePopover', true);
                                    thispop.data('waitingForPopoverTO', false);
                                    thispop.popover('hide');
                                }
                            }, 500));

                            // Stop default behavior
                            return false;
                        }
                    });
            ";

        return $popover;

    }

	/* --------------- */

	function dashboardReady($user_id, $cust_timestamp, $conn) {
		$ready = false;
		$pins_ready = false;
		$calcs_ready = false;

		$timestamp = time();

//		if((($timestamp - $cust_timestamp)/60/60) < 23){
//			//check to make sure profile pins have started pulling
//			$acc = "select last_pulled from status_profile_pins where user_id='$user_id'";
//			$acc_res = mysql_query($acc,$conn) or die(mysql_error());
//			while ($a = mysql_fetch_array($acc_res)) {
//
//
//				if((($timestamp - $a['last_pulled'])/60) > 5){
//					$pins_ready = true;
//				}
//			}
//		} else {
//			$pins_ready = true;
//		}

        if((($timestamp - $cust_timestamp)/60/60) < 23){
            //check to make sure that profile calculations have been completed
            $acc = "select last_calced, last_pulled_boards from status_profiles where user_id='$user_id'";
            $acc_res = mysql_query($acc,$conn) or die(mysql_error());
            while ($a = mysql_fetch_array($acc_res)) {
                if(($a['last_calced']!=0) && ($a['last_pulled_boards']!=0)){
                    $last_calced = $a['last_calced'];
                    $calcs_ready = true;
                }
            }
            if($calcs_ready){
                $acc = "select * from calcs_profile_history where user_id='$user_id' order by date desc limit 1";
                $acc_res = mysql_query($acc,$conn) or die(mysql_error());
                while ($a = mysql_fetch_array($acc_res)) {
                    if($a['date'] == getFlatDate($last_calced)){
                        $pins_ready = true;
                    }
                }
            }
        } else {
            $pins_ready = true;
        }

		if($pins_ready){
			$ready = true;
		}

		return $ready;

	}

	function hasAnalytics($account_id, $conn) {

		$found = false;
		$acc = "select account_id from status_traffic where account_id = '$account_id' order by timestamp desc limit 1";
		$acc_res = mysql_query($acc,$conn) or die(mysql_error());
		while ($a = mysql_fetch_array($acc_res)) {
			$found = true;
		}
		return $found;
	}

	function analyticsReady($account_id, $conn) {

		$found = false;
		$acc = "select last_pulled from status_traffic where account_id = '$account_id' order by timestamp desc limit 1";
		$acc_res = mysql_query($acc,$conn) or die(mysql_error());
		while ($a = mysql_fetch_array($acc_res)) {
			if($a['last_pulled']!=0){
				$found = true;
			}
		}
		return $found;
	}

	function getAnalyticsToken($account_id, $conn) {
		$acc = "select token from status_traffic where account_id = '$account_id' order by timestamp desc limit 1";
		$acc_res = mysql_query($acc,$conn) or die(mysql_error());
		while ($a = mysql_fetch_array($acc_res)) {
			$token = $a['token'];
		}
		return $token;
	}

	function getAnalyticsProfile($account_id, $conn) {
		$acc = "select profile from status_traffic where account_id = '$account_id' order by timestamp desc limit 1";
		$acc_res = mysql_query($acc,$conn) or die(mysql_error());
        $profile = false;
		while ($a = mysql_fetch_array($acc_res)) {
			$profile = $a['profile'];
		}
		return $profile;
	}

	function getSetting($site_id, $a, $conn) {
		$acc = "select value from pinterest_settings where attribute=\"$a\" AND site_id = '$site_id' order by id desc limit 1";
		$acc_res = mysql_query($acc,$conn) or die(mysql_error());
		while ($a = mysql_fetch_array($acc_res)) {
			$v = $a['value'];
		}

		if (!$v) {
			return null;
		} else {
			return $v;
		}
	}

	function saveAnalyticsProfile($account_id, $ga_profile, $conn) {
		$timestamp = time();
		//update existing profile if it already
		$sql = "update user_analytics set timestamp = \"$timestamp\", profile = \"$ga_profile\" where account_id = '$account_id'";
		$resu = mysql_query($sql, $conn);

		$sql = "update status_traffic set timestamp = \"$timestamp\", profile = \"$ga_profile\" where account_id = '$account_id'";
		$resu = mysql_query($sql, $conn);
	}


	function hasAnalyticsProfile($account_id, $conn) {
		$has = false;
		$acc = "select profile from user_analytics where account_id=\"$account_id\" order by id desc limit 1";
		$acc_res = mysql_query($acc,$conn) or die(mysql_error());
		while ($a = mysql_fetch_array($acc_res)) {
			$profile = $a['profile'];
			if($profile!=null && $profile!=''){
				$has = true;
			}
		}
		return $has;
	}

	function getLatestCacheDate($site_id, $conn) {
		$timestamp = 0;
		$acc = "select * from pinterest_calculations where site_id = '$site_id' AND status='completed' order by timestamp desc limit 1";
		$acc_res = mysql_query($acc,$conn) or die(mysql_error());
		while ($a = mysql_fetch_array($acc_res)) {
			$timestamp = $a['timestamp'];
		}

		if ($timestamp == 0) {
			return 0;
		} else {
			return getFlatDate($timestamp);
		}
	}

	function hasCache($site_id, $conn) {
		$timestamp = 0;
		$acc = "select * from pinterest_calculations where site_id = '$site_id' AND status='completed' order by timestamp desc limit 1";
		$acc_res = mysql_query($acc,$conn) or die(mysql_error());
		while ($a = mysql_fetch_array($acc_res)) {
			$timestamp = $a['timestamp'];
		}

		if ($timestamp == 0) {
			return false;
		} else {
			return true;
		}
	}

	function validateEmail($input) {
		if ((strpos($input, "@") === false)) {
			return 1;
		}
		if ((strpos($input, ".") === false)) {
			return 1;
		}
		return 0;
	}

	function validatePassword($input) {
		if (strlen($input) < 7) {
			return 3;
		}

		return 0;
	}
?>
