<?php
/**
 * @author Alex
 * Date: 8/28/13 7:08 PM
 * 
 */


/*
 * Navigation Highlighting/Activation
 *
 *
 */


$nav_dash="";$nav_comp="";$nav_time=""; $nav_pins="";$nav_sets="";
if ($page == "Dashboard") {
    $nav_dash="active";
}


else if ($page == "Profile") {
    $nav_profile ="active";
    $nav_growth="in";
} else if ($page == "Website") {
    $nav_website ="active";
    $nav_growth="in";
} else if ($page == "Boards") {
    $nav_boards ="active";
    $nav_growth="in";
}


else if ($page == "Time") {
    $nav_day_time="active";
    $nav_content="in";
}  else if ($page == "Influencers") {
    $nav_fans ="active";
} else if ($page == "Profile Pins") {
    $nav_pins ="active";
    $nav_content="in";
}  else if ($page == "Categories") {
    $nav_category ="active";
    $nav_content="in";
}


else if ($page == "Followers") {
    $nav_fol ="active";
    $nav_audience="in";
} else if ($page == "Trending Pins") {
    $nav_trend ="active";
    $nav_audience="in";
}


else if ($page == "Traffic") {
    $nav_traffic ="active";
    $nav_roi="in";
} else if ($page == "Pins Value") {
    $nav_pinsval ="active";
    $nav_roi="in";
} else if ($page == "Revenue Generators") {
    $nav_revgen ="active";
    $nav_roi="in";
}


else if ($page == "Competitor Benchmarks") {
    $nav_comp_bench="active";
    $nav_comp="in";
} else if ($page == "Competitor Intelligence") {
    $nav_comp_intel="active";
    $nav_comp="in";
}


else if ($page == "Settings") {
    $nav_sets="active";
}








/*
 * Google Analytics Experiment scripts for Upgrade Button
 *
 * (must be before google analytics tracking code)
 *
 */




if($customer){
    if ($customer->doesNotHaveCreditCardOnFile()) { ?>

        <!-- Load the Content Experiment JavaScript API client for the experiment -->
        <script src="//www.google-analytics.com/cx/api.js?experiment=AW0b_gpwTBGT36msU1ks_g"></script>

        <script>
            // Ask Google Analytics which variation to show the visitor.
            var chosenVariation = cxApi.chooseVariation();
        </script>

        <script>
            // Define JavaScript for each page variation of this experiment.
            var pageVariations = [
                function() {},  // Original: Do nothing. This will render the default HTML.
                function() {    // Variation 1: Banner Image
                    document.getElementById('nav-trial-button').innerHTML = 'Get Pro for Free';
                },
                function() {    // Variation 2: Sub-heading Text
                    document.getElementById('nav-trial-button').innerHTML = 'Go Pro for Free';
                },
                function() {    // Variation 3: Button Text
                    document.getElementById('nav-trial-button').innerHTML = 'Try Pro for Free';
                    $('#nav-trial-button').removeClass('btn-success');
                    $('#nav-trial-button').addClass('btn-warning');
                },
                function() {    // Variation 4: Button Color
                    document.getElementById('nav-trial-button').innerHTML = 'Get Pro for Free';
                    $('#nav-trial-button').removeClass('btn-success');
                    $('#nav-trial-button').addClass('btn-warning');
                },
                function() {    // Variation 5: Button Color
                    document.getElementById('nav-trial-button').innerHTML = 'Go Pro for Free';
                    $('#nav-trial-button').removeClass('btn-success');
                    $('#nav-trial-button').addClass('btn-warning');
                }

            ];

            // Wait for the DOM to load, then execute the view for the chosen variation.
            $(document).ready(
                // Execute the chosen view
                pageVariations[chosenVariation]
            );
        </script>
    <?php
    } else {
        ?>
        <script>
            $(document).ready(function(){
                // Execute the chosen view
                $('#menu-bottom').css('height','30px');
                $('#menu-content-scroll').css('bottom','30px');
            });
        </script>

    <?php
    }
}



/*
 * Wordcloud scripts and logic (only if trending pins page)
 * should probably stay as its own module
 *
 */


	    //Add wordcloud
	    if($page == "Trending Pins"){ ?>
            <script type="text/javascript" src="/v2/html/includes/js/JQcloud-1.0.3.min.js"></script>
            <?php include('v2/html/modules/wordcloud/wordcloud.php'); ?>
        <?php }


/*
 * Keyword cloud scripts - only used for custom keyword cloud pages, not needed in regular app
 * logic.
 */

	    //Add wordcloud
	    if($page == "Keyword Cloud"){ ?>
            <script type="text/javascript" src="/v2/html/includes/js/JQcloud-1.0.3.min.js"></script>
            <?php include('v2/html/modules/wordcloud/key_cloud.php'); ?>
        <?php }


/*
 * Datepicker UI scripts from jQuery UI - include on pages where custom date range is available.
 * profile, boards, website, competitor benchmarks, traffic
 */


	    if ($datePicker) {	?>
            <link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/themes/base/jquery-ui.css" />
            <!--<script src="jquery/uijs/jquery.ui.widget.js"></script>
            <script src="jquery/uijs/jquery.ui.datepicker.js"></script>-->
            <script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.js"></script>
            <script>
                $(function() {
                    $( "#datepickerFrom" ).datepicker();
                });
                $(function() {
                    $( "#datepickerTo" ).datepicker();
                });
            </script>
        <?php
        }


/*
 *  Custom Datatable modules for the boards page and the pins page.  Ideally, we'd be able
 * to just abstract these and create them on the fly, but it's much too complicated to do.
 */


	    if($page=="Profile Pins"){
            include('v2/html/modules/datatables/datatable_pins.php');
        } ?>

<?php
        if($page=="Boards"){
            include('v2/html/modules/datatables/datatable_boards.php');
        }



/*
 * include JS for gauges on the profile page and website page
 */



     	//add gauge JS if on profile page
     	if($page == "Profile" || $page == "Website"){ ?>
            <script type="text/javascript" src="/v2/html/includes/js/gauges/gauge.min.js"></script>
        <?php }

/*
 *  artificially created an id attribute for the body element based on the page name, we'll need to
 * replicate this somehow.
 */

    $body_id=strtolower(str_replace(" ","",$page));








/*
 * Needs to be added to the User model as a method?????  Need access to this in controllers
 */

function dashboardReady($user_id, $cust_timestamp, $conn) {
    $ready = false;
    $pins_ready = false;
    $calcs_ready = false;

    $timestamp = time();

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