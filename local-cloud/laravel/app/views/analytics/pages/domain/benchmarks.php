<?php ini_set('display_errors', 'off');
error_reporting(0);

$page = "Competitor Benchmarks";

//$customer = User::find($cust_id);

if($has_competitors){

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
                                                    <label class='comp-domain new-pins-toggle dashboard feature active' style='text-align:left;'>
                                                        <input id='new-pins-toggle' type='radio' value='new-pins-toggle' style='visibility:hidden;'> New Pins </input>
                                                    </label>
                                                    <label class='comp-domain new-pinners-toggle dashboard feature' style='text-align:left;'>
                                                        <input id='new-pinners-toggle' type='radio' value='new-pinners-toggle' style='visibility:hidden;'> New Pinners </input>
                                                    </label>
                                                    <label class='comp-domain new-reach-toggle dashboard feature' style='text-align:left;'>
                                                        <input id='new-reach-toggle' type='radio' value='new-reach-toggle' style='visibility:hidden;'> Potential Impressions </input>
                                                    </label>
<!--                                                    <label class='comp-domain avg-pins-toggle dashboard feature' style='text-align:left;'>-->
<!--                                                        <input id='avg-pins-toggle' type='radio' value='avg-pins-toggle' style='visibility:hidden;'> Pins / Day </input>-->
<!--                                                    </label>-->
<!--                                                    <label class='comp-domain avg-pinners-toggle dashboard feature' style='text-align:left;'>-->
<!--                                                        <input id='avg-pinners-toggle' type='radio' value='avg-pinners-toggle' style='visibility:hidden;'> Pinners / Day </input>-->
<!--                                                    </label>-->
                                                    <!--<label class='comp-domain unique-pinners-toggle dashboard feature' style='text-align:left;'>
                                                        <input id='unique-pinners-toggle' type='radio' value='unique-pinners-toggle' style='visibility:hidden;'> Unique Pinners </input>
                                                    </label>-->
                                                    <label class='comp-domain dashboard help' style='text-align:left;'>
                                                        &nbsp;
                                                    </label>
                                                </form>
                                            </div>
                                        </div>

                                        <div id='comp_dom_chart_div' style='float: left; width:85%;'>
                                            <div id='chartDom' style='width: 100%; height: 230px;'></div>
                                            <div id='controlDom' style='width: 100%; height: 44px;'></div>
                                        </div>
                                    </div>
                                    <div class='competitor-table'>
                                        <table class='table table-hover table-striped table-bordered'>
                                            <thead>
                                            <th class="comp-labal-column">Domain</th>
                                            <th class="comp-domain-column new-pins-column active">New Pins</th>
                                            <th class="comp-domain-column new-pinners-column">New Pinners</th>
                                            <th class="comp-domain-column new-reach-column">New Potential Impressions</th>
                                            <th class="avg-pins-column">Pins / Day</th>
                                            <th class="reach-per-pin-column">Potential Impressions / Pin</th>
<!--                                            <th class="comp-domain-column avg-pinners-column">Pinners / Day</th>-->
                                            <!--			  									<th class="comp-domain-column unique-pinners-column">Unique Pinners</th>-->
                                            </thead>
                                            <tbody>
                                            <?php
                                            foreach($compdom_grid as $v){
                                                echo $v;
                                            }
                                            ?>
                                            </tbody>
                                        </table>
                                    </div>
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

        <script  type='text/javascript'>

        google.setOnLoadCallback(drawVisualizationDom);

        var colorArrayDom = ["#3366cc","#dc3912","#ff9900","#109618","#990099","#0099c6","#dd4477","#66aa00","#b82e2e","#316395","#994499","#22aa99","#aaaa11","#6633cc","#e67300","#8b0707","#651067","#329262","#5574a6","#3b3eac","#b77322","#16d620","#b91383","#f4359e","#9c5935","#a9c413","#2a778d","#668d1c","#bea413","#0c5922","#743411"];


        var colorArrayDefault = ["#3366cc","#dc3912","#ff9900","#109618","#990099","#0099c6","#dd4477","#66aa00","#b82e2e","#316395","#994499","#22aa99","#aaaa11","#6633cc","#e67300","#8b0707","#651067","#329262","#5574a6","#3b3eac","#b77322","#16d620","#b91383","#f4359e","#9c5935","#a9c413","#2a778d","#668d1c","#bea413","#0c5922","#743411"];

        google.load('visualization', '1', {packages: ['corechart', 'controls']});

        function drawVisualizationDom() {
            var dashboardDom = new google.visualization.Dashboard(document.getElementById('comp_dom_chart_div'));

            var controlDom = new google.visualization.ControlWrapper({
                'controlType': 'ChartRangeFilter',
                'containerId': 'controlDom',
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

            var chartDom = new google.visualization.ChartWrapper({
                'chartType': 'LineChart',
                'containerId': 'chartDom',
                'options': {
                    // Use the same chart area width as the control for axis alignment.
                    'chartArea': {'left':'5%','top':'30px','height': '75%', 'width': '92%'},
                    'hAxis': {'slantedText': false},
                    'legend': {'position': 'top'},
                    'colors': colorArrayDom,
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
                    'vAxis': {
                        'textPosition':'in',
                        'logScale': false,
                        'minValue': 0,
                        'viewWindow': {'min':0}
                    },
                    'explorer': {
                        'axis': 'horizontal',
                        'actions': ['dragToZoom', 'rightClickToReset'],
                        'keepInBounds': true
                    },
                    'crosshair': {
                        'trigger': 'both',
                        'orientation': 'vertical',
                        'opacity': 0.2,
                        'color': '#555555',
                        'selected': { 'color': '#555', 'opacity': 0.9 }
                    },
                    <?php if ($domain_count < 6): ?>
                        'focusTarget': 'category'
                    <?php endif ?>
                }

            });


            <?php echo $new_pins_table; ?>
            <?php echo $new_pinners_table; ?>
            <?php echo $new_reach_table; ?>
            <?php //echo $avg_pins_table; ?>
            <?php //echo $avg_pinners_table; ?>
	      		<?php //echo $unique_pinners_table; ?>

            <?php echo $new_pins_print; ?>
            <?php echo $new_pinners_print; ?>
            <?php echo $new_reach_print; ?>
            <?php //echo $avg_pins_print; ?>
            <?php //echo $avg_pinners_print; ?>
	      		<?php //echo $unique_pinners_print; ?>





            <?php
                foreach($compdom_toggle_vars as $k => $v){
                    echo $v;
                }
            ?>

            var newPinsBox = document.getElementById('new-pins-toggle');
            var newPinnersBox = document.getElementById('new-pinners-toggle');
            var newReachBox = document.getElementById('new-reach-toggle');
//            var avgPinsBox = document.getElementById('avg-pins-toggle');
//            var avgPinnersBox = document.getElementById('avg-pinners-toggle');
//				var uniquePinnersBox = document.getElementById('unique-pinners-toggle');



            function drawChartDom(viewTable) {

                var viewNewDom = viewTable;

                // Disabling the buttons while the chart is drawing.
                newPinsBox.checked = viewNewDom.getColumnLabel(viewNewDom.getNumberOfColumns()-1) == "newPins";

                newPinnersBox.checked = viewNewDom.getColumnLabel(viewNewDom.getNumberOfColumns()-1) == "newPinners";

                newReachBox.checked = viewNewDom.getColumnLabel(viewNewDom.getNumberOfColumns()-1) == "newReach";

//                avgPinsBox.checked = viewNewDom.getColumnLabel(viewNewDom.getNumberOfColumns()-1) == "avgPins";

//                avgPinnersBox.checked = viewNewDom.getColumnLabel(viewNewDom.getNumberOfColumns()-1) == "avgPinners";

//		          	uniquePinnersBox.checked = viewNewDom.getColumnLabel(viewNewDom.getNumberOfColumns()-1) == "uniquePinners";


                if(newPinsBox.checked){
                    viewNewDom = new google.visualization.DataView(newPins);
                    metricDom = newPins;
                } else if(newPinnersBox.checked){
                    viewNewDom = new google.visualization.DataView(newPinners);
                    metricDom = newPinners;
                } else if(newReachBox.checked){
                    viewNewDom = new google.visualization.DataView(newReach);
                    metricDom = newReach;
                }

//                else if(avgPinsBox.checked){
//                    viewNewDom = new google.visualization.DataView(avgPins);
//                    metricDom = avgPins;
//                }

//                else if(avgPinnersBox.checked){
//                    viewNewDom = new google.visualization.DataView(avgPinners);
//                    metricDom = avgPinners;
//                }
//		          	else if(uniquePinnersBox.checked){
//		          		viewNewDom = new google.visualization.DataView(uniquePinners);
//		          		metricDom = uniquePinners;
//		          	}


                <?php
                    foreach($compdom_toggle_check as $k => $v){
                        echo $v;
                    }
                ?>

                var currView = viewNewDom.getViewColumns();
                var removed = metricDom.getNumberOfColumns()-1;
                viewNewDom.hideColumns([removed]);

                chartDom.setOption('colors',colorArrayDom);
                dashboardDom.bind(controlDom, chartDom);
                dashboardDom.draw(viewNewDom);

            }



            <?php
                foreach($compdom_toggle_js as $k => $v){
                    echo $v;
                }
            ?>





            newPinsBox.onclick = function() {
                //adding followers
                if(newPinsBox.checked){
                    var view = new google.visualization.DataView(newPins);
                    var metricDom = newPins;
                    chartDom.setOption('vAxes', {
                        0: {
                            logScale: false, minValue: 0, title: "Pins"
                        }
                    });
                    drawChartDom(view);
                }
            }

            newPinnersBox.onclick = function() {
                //adding pins
                if(newPinnersBox.checked){
                    var view = new google.visualization.DataView(newPinners);
                    var metricDom = newPinners;
                    chartDom.setOption('vAxes', {
                        0: {
                            logScale: false, minValue: 0, title: "Pinners"
                        }
                    });
                    drawChartDom(view);
                }
            }

            newReachBox.onclick = function() {
                //adding pins
                if(newReachBox.checked){
                    var view = new google.visualization.DataView(newReach);
                    var metricDom = newReach;
                    chartDom.setOption('vAxes', {
                        0: {
                            logScale: false, minValue: 0, title: "Potential Impressions"
                        }
                    });
                    drawChartDom(view);
                }
            }

//            avgPinsBox.onclick = function() {
//                //adding repins
//                if(avgPinsBox.checked){
//                    var view = new google.visualization.DataView(avgPins);
//                    var metricDom = avgPins;
//                    drawChartDom(view);
//                }
//            }

//            avgPinnersBox.onclick = function() {
//                //adding likes
//                if(avgPinnersBox.checked){
//                    var view = new google.visualization.DataView(avgPinners);
//                    var metricDom = avgPinners;
//                    drawChartDom(view);
//                }
//            }

//			    uniquePinnersBox.onclick = function() {
//
            //adding comments
//			    	if(uniquePinnersBox.checked){
//			    		var view = new google.visualization.DataView(uniquePinners);
//			    		var metricDom = uniquePinners;
//			    		drawChartDom(view);
//			    	}
//			    }


            var metricDom = newPins;
            var view = new google.visualization.DataView(newPins);
            var lastingViewDom = view;

            drawChartDom(lastingViewDom);

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


            //------ DOMAIN CHART / TABLE CONTROLS --------//

            $('#new-pins-toggle').click(function(){
                $('.comp-domain-column').removeClass('active');
                $('.new-pins-column').addClass('active');

                $('label.comp-domain').removeClass('active');
                $('.new-pins-toggle').addClass('active');
            });
            $('#new-pinners-toggle').click(function(){
                $('.comp-domain-column').removeClass('active');
                $('.new-pinners-column').addClass('active');

                $('label.comp-domain').removeClass('active');
                $('.new-pinners-toggle').addClass('active');
            });
            $('#new-reach-toggle').click(function(){
                $('.comp-domain-column').removeClass('active');
                $('.new-reach-column').addClass('active');

                $('label.comp-domain').removeClass('active');
                $('.new-reach-toggle').addClass('active');
            });
//            $('#avg-pins-toggle').click(function(){
//                $('.comp-domain-column').removeClass('active');
//                $('.avg-pins-column').addClass('active');
//
//                $('label.comp-domain').removeClass('active');
//                $('.avg-pins-toggle').addClass('active');
//            });
//            $('#avg-pinners-toggle').click(function(){
//                $('.comp-domain-column').removeClass('active');
//                $('.avg-pinners-column').addClass('active');
//
//                $('label.comp-domain').removeClass('active');
//                $('.avg-pinners-toggle').addClass('active');
//            });
//			$('#unique-pinners-toggle').click(function(){
//				$('.comp-domain-column').removeClass('active');
//				$('.unique-pinners-column').addClass('active');
//
//				$('label.comp-domain').removeClass('active');
//				$('.unique-pinners-toggle').addClass('active');
//			});



            $('th.new-pins-column').click(function(){
                $("#new-pins-toggle").trigger('click');
                $("#new-pins-toggle").trigger('click');
            });
            $('th.new-pinners-column').click(function(){
                $("#new-pinners-toggle").trigger('click');
                $("#new-pinners-toggle").trigger('click');
            });
            $('th.new-reach-column').click(function(){
                $("#new-reach-toggle").trigger('click');
                $("#new-reach-toggle").trigger('click');
            });
//            $('th.avg-pins-column').click(function(){
//                $("#avg-pins-toggle").trigger('click');
//                $("#avg-pins-toggle").trigger('click');
//            });
//            $('th.avg-pinners-column').click(function(){
//                $("#avg-pinners-toggle").trigger('click');
//                $("#avg-pinners-toggle").trigger('click');
//            });
//			$('th.unique-pinners-column').click(function(){
//				$("#unique-pinners-toggle").trigger('click');
//				$("#unique-pinners-toggle").trigger('click');
//			});



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