<?php ini_set('display_errors', 'off');
error_reporting(0);

$page = "Competitor Benchmarks";

//$customer = User::find($cust_id);

if(Session::get('has_competitors')){

?>

<div class='clearfix'></div>
<div class=''>

    <div class='accordion' id='accordion3' style='margin-bottom:25px'>
        <div id='collapseTwo' class='accordion-body collapse in'>
            <div class='accordion-inner'>
                <div class="row dashboard" style='margin-bottom:-10px;'>

                    <?php if($days_of_calcs==1){ ?>
                        <script>
                            $(document).ready(function() {
                                $('.feature-wrap-charts').addClass('chart-hide');
                                $('.profile-chart-wrapper').prepend('<div class="chart-not-ready alert alert-info">Unlocking your charts in T minus <?php echo $hours_until_charts; ?>... <a data-toggle="popover" data-container="body" data-content="Each day, we take a snapshot of your analytics (at 12am CST) so you can track your growth over time.  As soon as your next snapshot is created, you\'ll be able to start measuring your progress right here!" data-placement="bottom"><i class="icon-info-2"></i> Learn more.</a></div>');
                            });
                        </script>
                    <?php } ?>

                    <div class="profile-chart-wrapper" style='margin-left:25px'>

                        <div class="row" style='margin-left:0px;'>

                            <div class="feature-wrap-charts competitors" style='text-align:left; margin-bottom: 20px;'>

                                <div class="" style='float:left; width: 15%; height: 210px; margin-bottom: -45px;'>
                                    <div class="feature-wrap-chart-controls competitors" style='text-align:left;'>

                                        <form>
                                            <label class='comp-profile followers-toggle dashboard feature active' style='text-align:left;'>
                                                <input id='followers-toggle' type='radio' value='followers-toggle' style='visibility:hidden;'> Followers </input>
                                            </label>
                                            <label class='comp-profile pins-toggle dashboard feature' style='text-align:left;'>
                                                <input id='pins-toggle' type='radio' value='pins-toggle' style='visibility:hidden;'> Pins </input>
                                            </label>
                                            <label class='comp-profile repins-toggle dashboard feature' style='text-align:left;'>
                                                <input id='repins-toggle' type='radio' value='repins-toggle' style='visibility:hidden;'> Repins </input>
                                            </label>
                                            <label class='comp-profile likes-toggle dashboard feature' style='text-align:left;'>
                                                <input id='likes-toggle' type='radio' value='likes-toggle' style='visibility:hidden;'> Likes </input>
                                            </label>
                                            <label class='comp-profile comments-toggle dashboard feature' style='text-align:left;'>
                                                <input id='comments-toggle' type='radio' value='comments-toggle' style='visibility:hidden;'> Comments </input>
                                            </label>
                                            <label class='comp-profile dashboard help' style='text-align:left;'>
                                                &nbsp;
                                            </label>
                                        </form>
                                    </div>
                                </div>

                                <div id='comp_chart_div' style='float: left; width:85%;'>
                                    <div id='chart2' style='width: 100%; height: 230px;'></div>
                                    <div id='control2' style='width: 100%; height: 44px;'></div>
                                </div>
                            </div>
                            <div class='competitor-table'>
                                <table class='table table-hover table-striped table-bordered'>
                                    <thead>
                                    <th class="comp-labal-column">Profile</th>
                                    <th class="comp-profile-column follower-column active">New Followers</th>
                                    <th class="comp-profile-column pin-column">New Pin Activity</th>
                                    <th class="comp-profile-column repin-column">New Repins</th>
                                    <th class="comp-profile-column like-column">New Likes</th>
                                    <th class="comp-profile-column comment-column">New Comments</th>
                                    <th class="virality-column">Virality Score</th>
                                    <th class="audience-engagement-column">Engagement Score</th>
                                    </thead>
                                    <tbody>
                                    <?php
                                    foreach($comp_grid as $v){
                                        echo $v;
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                </div>
                <div class="row" style='margin-bottom:10px;'>
                    <div class="span5">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



    <script type='text/javascript' src='https://www.google.com/jsapi'></script>
    <script type='text/javascript'>

    var colorArray = ["#3366cc","#dc3912","#ff9900","#109618","#990099","#0099c6","#dd4477","#66aa00","#b82e2e","#316395","#994499","#22aa99","#aaaa11","#6633cc","#e67300","#8b0707","#651067","#329262","#5574a6","#3b3eac","#b77322","#16d620","#b91383","#f4359e","#9c5935","#a9c413","#2a778d","#668d1c","#bea413","#0c5922","#743411"];

    var colorArrayDom = ["#3366cc","#dc3912","#ff9900","#109618","#990099","#0099c6","#dd4477","#66aa00","#b82e2e","#316395","#994499","#22aa99","#aaaa11","#6633cc","#e67300","#8b0707","#651067","#329262","#5574a6","#3b3eac","#b77322","#16d620","#b91383","#f4359e","#9c5935","#a9c413","#2a778d","#668d1c","#bea413","#0c5922","#743411"];


    var colorArrayDefault = ["#3366cc","#dc3912","#ff9900","#109618","#990099","#0099c6","#dd4477","#66aa00","#b82e2e","#316395","#994499","#22aa99","#aaaa11","#6633cc","#e67300","#8b0707","#651067","#329262","#5574a6","#3b3eac","#b77322","#16d620","#b91383","#f4359e","#9c5935","#a9c413","#2a778d","#668d1c","#bea413","#0c5922","#743411"];

    google.load('visualization', '1', {packages: ['corechart', 'controls']});

    google.setOnLoadCallback(drawVisualization);



    function drawVisualization() {
        var dashboard2 = new google.visualization.Dashboard(document.getElementById('comp_chart_div'));

        var control2 = new google.visualization.ControlWrapper({
            'controlType': 'ChartRangeFilter',
            'containerId': 'control2',
            'options': {
                // Filter by the date axis.
                'filterColumnIndex': 0,
                'ui': {
                    'chartType': 'LineChart',
                    'chartOptions': {
                        'chartArea': {'width': '100%'},
                        'hAxis': {'baselineColor': 'none'},
                        'series': {0:{color: '#5792B3'}},
                        'animation':{
                            'duration': 500,
                            'easing': 'inAndOut'
                        },
                        'curveType':'function',
                        'backgroundColor':'transparent',
                        'backgroundColor': {'strokeWidth':1}
                    },


                    // 1 day in milliseconds = 24 * 60 * 60 * 1000 = 86,400,000
                    'minRangeSize': 86400000
                }
            },
            // Initial range:
            'state': {'range': {'start': new Date(<?php echo $new_last_chart_date; ?>), 'end': new Date(<?php echo $new_curr_chart_date; ?>)}}
        });

        var chart2 = new google.visualization.ChartWrapper({
            'chartType': 'LineChart',
            'containerId': 'chart2',
            'options': {
                // Use the same chart area width as the control for axis alignment.
                'chartArea': {'left':'5%','top':'30px','height': '75%', 'width': '92%'},
                'hAxis': {'slantedText': false},
                'legend': {'position': 'top'},
                'colors': colorArray,
                'series': {0:{color: '#5792B3'}},
                'animation':{
                    'duration': 500,
                    'easing': 'inAndOut'
                },
                //'focusTarget':'category',
                'tooltip': {'isHtml':true},
                'backgroundColor':'transparent',
                'backgroundColor': {'stroke':'#ddd','strokeWidth':1},
                'curveType':'function',
                'pointSize':2,
                'series': {0:{'lineWidth':4, 'pointSize':4}},
                'vAxis': {'textPosition':'in'}
            }

        });


        <?php echo $followers_table; ?>
        <?php echo $pins_table; ?>
        <?php echo $repins_table; ?>
        <?php echo $likes_table; ?>
        <?php echo $comments_table; ?>

        <?php echo $followers_print; ?>
        <?php echo $pins_print; ?>
        <?php echo $repins_print; ?>
        <?php echo $likes_print; ?>
        <?php echo $comments_print; ?>





        <?php
            foreach($comp_toggle_vars as $k => $v){
                echo $v;
            }
        ?>

        var followerBox = document.getElementById('followers-toggle');
        var pinBox = document.getElementById('pins-toggle');
        var likeBox = document.getElementById('likes-toggle');
        var repinBox = document.getElementById('repins-toggle');
        var commentBox = document.getElementById('comments-toggle');



        function drawChart(viewTable) {

            var viewNew = viewTable;

            // Disabling the buttons while the chart is drawing.
            followerBox.checked = viewNew.getColumnLabel(viewNew.getNumberOfColumns()-1) == "followers";

            pinBox.checked = viewNew.getColumnLabel(viewNew.getNumberOfColumns()-1) == "pins";

            repinBox.checked = viewNew.getColumnLabel(viewNew.getNumberOfColumns()-1) == "repins";

            likeBox.checked = viewNew.getColumnLabel(viewNew.getNumberOfColumns()-1) == "likes";

            commentBox.checked = viewNew.getColumnLabel(viewNew.getNumberOfColumns()-1) == "comments";


            if(followerBox.checked){
                viewNew = new google.visualization.DataView(followers);
                metric = followers;
            } else if(pinBox.checked){
                viewNew = new google.visualization.DataView(pins);
                metric = pins;
            } else if(repinBox.checked){
                viewNew = new google.visualization.DataView(repins);
                metric = repins;
            } else if(likeBox.checked){
                viewNew = new google.visualization.DataView(likes);
                metric = likes;
            } else if(commentBox.checked){
                viewNew = new google.visualization.DataView(comments);
                metric = comments;
            }


            <?php
                foreach($comp_toggle_check as $k => $v){
                    echo $v;
                }
            ?>

            var currView = viewNew.getViewColumns();
            var removed = metric.getNumberOfColumns()-1;
            viewNew.hideColumns([removed]);

            chart2.setOption('colors',colorArray);
            dashboard2.bind(control2, chart2);
            dashboard2.draw(viewNew);

            //									   	console.log(viewNew.getViewColumns());
            //
            //									   	viewNew.setColumns(currView);
            //									   	lastingView = viewNew;
            //
            //									   	console.log(viewNew.getViewColumns());

        }



        <?php
            foreach($comp_toggle_js as $k => $v){
                echo $v;
            }
        ?>





        followerBox.onclick = function() {
            //adding followers
            if(followerBox.checked){
                var view = new google.visualization.DataView(followers);
                var metric = followers;
                drawChart(view);
            }
        }

        pinBox.onclick = function() {
            //adding pins
            if(pinBox.checked){
                var view = new google.visualization.DataView(pins);
                var metric = pins;
                drawChart(view);
            }
        }

        repinBox.onclick = function() {
            //adding repins
            if(repinBox.checked){
                var view = new google.visualization.DataView(repins);
                var metric = repins;
                drawChart(view);
            }
        }

        likeBox.onclick = function() {
            //adding likes
            if(likeBox.checked){
                var view = new google.visualization.DataView(likes);
                var metric = likes;
                drawChart(view);
            }
        }

        commentBox.onclick = function() {

            //adding comments
            if(commentBox.checked){
                var view = new google.visualization.DataView(comments);
                var metric = comments;
                drawChart(view);
            }
        }


        var metric = followers;
        var view = new google.visualization.DataView(followers);
        var lastingView = view;

        drawChart(lastingView);

    }

    </script>

    <script>
        $(document).ready(function(){

//			$('#comp-tabs a').click(function (e) {
//			  e.preventDefault();
//			  $(this).tab('show');
//			});


            $('td.comp-label-column').click(function(){
                if(!$('input', this).is(':checked')){
                    $('.comp-icon', this).removeClass('active');
                    $(this).parent().css('color','#ccc');
                } else if($('input', this).is(':checked')){
                    $('.comp-icon', this).addClass('active');
                    $(this).parent().css('color','#000');
                }

                //$('.comp-icon', this).toggleClass('active');
            });


            //------ PROFILE CHART / TABLE CONTROLS --------//


            $('#followers-toggle').click(function(){
                $('.comp-profile-column').removeClass('active');
                $('.follower-column').addClass('active');

                $('label.comp-profile').removeClass('active');
                $('.followers-toggle').addClass('active');
            });
            $('#pins-toggle').click(function(){
                $('.comp-profile-column').removeClass('active');
                $('.pin-column').addClass('active');

                $('label.comp-profile').removeClass('active');
                $('.pins-toggle').addClass('active');
            });
            $('#repins-toggle').click(function(){
                $('.comp-profile-column').removeClass('active');
                $('.repin-column').addClass('active');

                $('label.comp-profile').removeClass('active');
                $('.repins-toggle').addClass('active');
            });
            $('#likes-toggle').click(function(){
                $('.comp-profile-column').removeClass('active');
                $('.like-column').addClass('active');

                $('label.comp-profile').removeClass('active');
                $('.likes-toggle').addClass('active');
            });
            $('#comments-toggle').click(function(){
                $('.comp-profile-column').removeClass('active');
                $('.comment-column').addClass('active');

                $('label.comp-profile').removeClass('active');
                $('.comments-toggle').addClass('active');
            });

            $('th.follower-column').click(function(){
                $("#followers-toggle").trigger('click');
                $("#followers-toggle").trigger('click');
            });
            $('th.pin-column').click(function(){
                $("#pins-toggle").trigger('click');
                $("#pins-toggle").trigger('click');
            });
            $('th.repin-column').click(function(){
                $("#repins-toggle").trigger('click');
                $("#repins-toggle").trigger('click');
            });
            $('th.like-column').click(function(){
                $("#likes-toggle").trigger('click');
                $("#likes-toggle").trigger('click');
            });
            $('th.comment-column').click(function(){
                $("#comments-toggle").trigger('click');
                $("#comments-toggle").trigger('click');
            });

        });
    </script>

<?php

} else {

    $is_profile = true;
    $datePicker = true;
    ?>

    <div class='clearfix'></div>
    <div class=''>
        <h3 style='font-weight:normal; text-align:center'>Add competitors to enable this report!</h3>
    </div>
    <div class='clearfix'></div>
    <div style='text-align:center;'>
        <a href="/settings/competitors">
            <button class="btn btn-success">Add a Competitor →</button>
        </a>
    </div>



<?php
}

?>



<?php

function cmp($a, $b) {
    if (!$_GET['sort']) {
        $sort = "followers";
    } else {
        $sort = $_GET['sort'];
    }

    if ($a['current']["$sort"] > $b['current']["$sort"]) {
        return -1;
    } else if ($a['current']["$sort"] < $b['current']["$sort"]) {
        return 1;
    } else {
        return 0;
    }
}

function timesort($a, $b) {
    $t = "chart_date";

    if ($a["$t"] > $b["$t"]) {
        return 1;
    } else if ($a["$t"] == $b["$t"]) {
        return 0;
    } else {
        return -1;
    }
}

function formatNumber($x) {
    if (!$x) {
        return "-";
    } else {
        return number_format($x);
    }
}

function formatRatio($x) {
    if (!$x) {
        return "-";
    } else {
        return number_format($x,1) . "<span style='color:#777'>:1</span>";
    }
}

function formatPercentage($x) {
    $x = $x * 100;
    if ($x >= 0) {
        return "<span style='color: #549E54;font-weight:normal;font-size:12px'>(" . number_format($x,1) . "%)</span>";
    } else if($x < 0) {
        return "<span style='color: #aaa;font-weight:normal;font-size:12px'>(" . number_format($x,1) . "%)</span>";
    } else if($x == "na"){
        return "<span style='color: #aaa;font-weight:normal;font-size:12px'>(--%)</span>";
    }
}


function formatAbsoluteRatio($x) {
    if ($x > 0) {
        return "<span class='pos'><i class='icon-arrow-up'></i>" . number_format($x,1) . "</span><span style='color:#777'>:1</span>";
    } elseif ($x == 0) {
        return "<span class='neg'> &nbsp;--</span><span class='muted'>:1</span>";
    } else {
        return "<span class='neg'><i class='icon-arrow-down'></i>" . number_format($x,1) . "</span><span style='color:#777'>:1</span>";
    }
}

function formatAbsoluteKPI($x) {
    if ($x > 0) {
        return "<span style='color: green;'><i class='icon-arrow-up'></i>" . number_format($x,2) . "</span>";
    } elseif ($x == 0) {
        return "<span style='color: #aaa;'> &nbsp;--</span>";
    } else {
        return "<span style='color: #aaa;'><i class='icon-arrow-down'></i>" . number_format($x,2) . "</span>";
    }
}

function renameCategories($a) {

    if($a=="womens_fashion"){
        $b="womens fashion";
    }
    elseif($a=="diy_crafts"){
        $b="diy & crafts";
    }
    elseif($a=="health_fitness"){
        $b="health & fitness";
    }
    elseif($a=="holidays_events"){
        $b="holidays & events";
    }
    elseif($a=="none"){
        $b="not specified";
    }
    elseif($a=="holiday_events"){
        $b="holidays & events";
    }
    elseif($a=="home_decor"){
        $b="home decor";
    }
    elseif($a=="food_drink"){
        $b="food & drink";
    }
    elseif($a=="film_music_books"){
        $b="film, music & books";
    }
    elseif($a=="hair_beauty"){
        $b="hair & beauty";
    }
    elseif($a=="cars_motorcycles"){
        $b="cars & motorcycles";
    }
    elseif($a=="science_nature"){
        $b="science & nature";
    }
    elseif($a=="mens_fashion"){
        $b="mens fashion";
    }
    elseif($a=="illustrations_posters"){
        $b="illustrations & posters";
    }
    elseif($a=="art_arch"){
        $b="art & architecture";
    }
    elseif($a=="wedding_events"){
        $b="weddings & events";
    }
    else{
        $b=$a;
    }

    return $b;

}
?>