<?php

if ($account_analytics_ready) {

    $traffic_calcs = array();

    $overall_visits       = 0;
    $overall_visitors     = 0;
    $overall_new_visits   = 0;
    $overall_pageviews    = 0;
    $overall_transactions = 0;
    $overall_revenue      = 0;

    //get all calcs	by date
    $acc = "select *, DATE(FROM_UNIXTIME(`date`)) AS pDate from data_traffic
        where traffic_id='$cust_traffic_id' $date_limit_clause order by date desc;";
    $acc_res = mysql_query($acc, $conn) or die(mysql_error());
    while ($a = mysql_fetch_array($acc_res)) {
        $calcs_found = true;
        $query_date = $a['date'];

        $traffic_calcs["$query_date"]                 = array();
        $traffic_calcs["$query_date"]['timestamp']    = $a['date'];
        $traffic_calcs["$query_date"]['chart_date']   = $a['pDate'];
        $traffic_calcs["$query_date"]['visits']       = $a['visits'];
        $traffic_calcs["$query_date"]['visitors']     = $a['visitors'];
        $traffic_calcs["$query_date"]['new_visits']   = $a['new_visits'];
        $traffic_calcs["$query_date"]['pageviews']    = $a['pageviews'];
        $traffic_calcs["$query_date"]['transactions'] = $a['transactions'];
        $traffic_calcs["$query_date"]['revenue']      = $a['revenue'];
        @$traffic_calcs["$query_date"]['pv_per_visit'] = ($a['pageviews'] / $a['visits']);
        @$traffic_calcs["$query_date"]['rev_per_visit'] = ($a['revenue'] / $a['visits']);
        @$traffic_calcs["$query_date"]['rev_per_transaction'] = ($a['revenue'] / $a['transactions']);


        //set the date for totalling daily stats
        if ($query_date > $last_date) {
            $total_date = $current_date;
        } else if ($query_date <= $last_date && $query_date > $compare_date) {
            $total_date = $last_date;
        } else if ($query_date <= $compare_date && $query_date > $compare2_date) {
            $total_date = $compare_date;
        } else {
            $total_date = '';
        }

        //add up total calcs for each period
        if ($total_date != '') {
            if (!isset($traffic_calcs[$total_date]['total_visits'])) {
                $traffic_calcs[$total_date]['total_visits']       = 0 + $a['visits'];
                $traffic_calcs[$total_date]['total_visitors']     = 0 + $a['visitors'];
                $traffic_calcs[$total_date]['total_new_visits']   = 0 + $a['new_visits'];
                $traffic_calcs[$total_date]['total_pageviews']    = 0 + $a['pageviews'];
                $traffic_calcs[$total_date]['total_transactions'] = 0 + $a['transactions'];
                $traffic_calcs[$total_date]['total_revenue']      = 0 + $a['revenue'];
            } else {
                $traffic_calcs[$total_date]['total_visits'] += $a['visits'];
                $traffic_calcs[$total_date]['total_visitors'] += $a['visitors'];
                $traffic_calcs[$total_date]['total_new_visits'] += $a['new_visits'];
                $traffic_calcs[$total_date]['total_pageviews'] += $a['pageviews'];
                $traffic_calcs[$total_date]['total_transactions'] += $a['transactions'];
                $traffic_calcs[$total_date]['total_revenue'] += $a['revenue'];
            }
        }

        $overall_visits += $a['visits'];
        $overall_visitors += $a['visitors'];
        $overall_new_visits += $a['new_visits'];
        $overall_pageviews += $a['pageviews'];
        $overall_transactions += $a['transactions'];
        $overall_revenue += $a['revenue'];

    }

    if ($last_date < $date_limit) {
        $last_date = $date_limit;
    }

    $acc = "select traffic_id, count(distinct pin_id) from data_traffic_pins where traffic_id='$cust_traffic_id' AND date >='$last_date' and date <='$current_date';";
    $acc_res = mysql_query($acc, $conn) or die(mysql_error());
    while ($a = mysql_fetch_array($acc_res)) {
        $traffic_calcs["$current_date"]['pin_count'] = $a['count(distinct pin_id)'];
    }


    if ($compare_date < $date_limit) {
        $compare_date = strtotime("-1 day", $date_limit);
    }

    $acc = "select traffic_id, count(distinct pin_id) from data_traffic_pins where traffic_id='$cust_traffic_id' AND date >='$compare_date' and date <='$last_date';";
    $acc_res = mysql_query($acc, $conn) or die(mysql_error());
    while ($a = mysql_fetch_array($acc_res)) {
        $traffic_calcs["$last_date"]['pin_count'] = $a['count(distinct pin_id)'];
    }


    if ($compare2_date < $date_limit) {
        $compare2_date = strtotime("-2 days", $date_limit);
    }

    $acc = "select traffic_id, count(distinct pin_id) from data_traffic_pins where traffic_id='$cust_traffic_id' AND date >='$compare2_date' and date <='$compare_date';";
    $acc_res = mysql_query($acc, $conn) or die(mysql_error());
    while ($a = mysql_fetch_array($acc_res)) {
        $traffic_calcs["$compare_date"]['pin_count'] = $a['count(distinct pin_id)'];
    }


    $max_visits              = 0;
    $max_visitors            = 0;
    $max_new_visits          = 0;
    $max_pageviews           = 0;
    $max_transactions        = 0;
    $max_revenue             = 0;
    $max_pv_per_visit        = 0;
    $max_rev_per_visit       = 0;
    $max_rev_per_transaction = 0;


    //Create formatted values of calcs
    foreach ($traffic_calcs as $p) {

        $date                             = $p['timestamp'];
        $chart_date                       = $p['chart_date'];
        $traffic_calcs_formatted["$date"] = array();

        //set formatted values for daily data
        $traffic_calcs_formatted["$date"]['visits']       = number_format($p['visits'], 0);
        $traffic_calcs_formatted["$date"]['visitors']     = number_format($p['visitors'], 0);
        $traffic_calcs_formatted["$date"]['new_visits']   = number_format($p['new_visits'], 0);
        $traffic_calcs_formatted["$date"]['pageviews']    = number_format($p['pageviews'], 0);
        $traffic_calcs_formatted["$date"]['transactions'] = number_format($p['transactions'], 0);
        $traffic_calcs_formatted["$date"]['revenue']      = "$" . number_format($p['revenue'], 2);


        //set the formatted values, formatted average daily values and formatted percentage change values for each time period
        if ($date == $current_date || $date == $last_date || $date == $compare_date) {
            if ($date == $current_date) {
                $date2 = $last_date;
            } else if ($date == $last_date) {
                $date2 = $compare_date;
            } else if ($date == $compare_date) {
                $date2 = $compare2_date;
            }


            $traffic_calcs_formatted[$date]['total_visits']       = number_format($p['total_visits'], 0);
            $traffic_calcs_formatted[$date]['total_visitors']     = number_format($p['total_visitors'], 0);
            $traffic_calcs_formatted[$date]['total_new_visits']   = number_format($p['total_new_visits'], 0);
            $traffic_calcs_formatted[$date]['total_pageviews']    = number_format($p['total_pageviews'], 0);
            $traffic_calcs_formatted[$date]['total_transactions'] = number_format($p['total_transactions'], 0);
            $traffic_calcs_formatted[$date]['total_revenue']      = "$" . number_format($p['total_revenue'], 0);


            $traffic_calcs_formatted["$date"]['avg_daily_visits']       = formatAbsolute($p['total_visits'] / $day_range);
            $traffic_calcs_formatted["$date"]['avg_daily_visitors']     = formatAbsolute($p['total_visitors'] / $day_range);
            $traffic_calcs_formatted["$date"]['avg_daily_new_visits']   = formatAbsolute($p['total_new_visits'] / $day_range);
            $traffic_calcs_formatted["$date"]['avg_daily_pageviews']    = formatAbsolute($p['total_pageviews'] / $day_range);
            $traffic_calcs_formatted["$date"]['avg_daily_transactions'] = formatAbsolute($p['total_transactions'] / $day_range);
            $traffic_calcs_formatted["$date"]['avg_daily_revenue']      = formatRev($p['total_revenue'] / $day_range, 0);

            $traffic_calcs_formatted["$date"]['total_visits_growth_perc'] = formatPercentage(($traffic_calcs[$date2]['total_visits'] / $p['total_visits']) - 1);
            $traffic_calcs_formatted["$date"]['total_visitors_growth_perc'] = formatPercentage(($traffic_calcs[$date2]['total_visitors'] / $p['total_visitors']) - 1);
            $traffic_calcs_formatted["$date"]['total_new_visits_growth_perc'] = formatPercentage(($traffic_calcs[$date2]['total_new_visits'] / $p['total_new_visits']) - 1);
            $traffic_calcs_formatted["$date"]['total_pageviews_growth_perc'] = formatPercentage(($traffic_calcs[$date2]['total_pageviews'] / $p['total_pageviews']) - 1);
            $traffic_calcs_formatted["$date"]['total_transactions_growth_perc'] = formatPercentage(($traffic_calcs[$date2]['total_transactions'] / $p['total_transactions']) - 1);
            $traffic_calcs_formatted["$date"]['total_revenue_growth_perc'] = formatPercentage(($traffic_calcs[$date2]['total_revenue'] / $p['total_revenue']) - 1);

        }

        //get max all-time values
        $max_visits              = max($max_visits, $p['visits']);
        $max_visitors            = max($max_visitors, $p['visitors']);
        $max_new_visits          = max($max_new_visits, $p['new_visits']);
        $max_pageviews           = max($max_pageviews, $p['pageviews']);
        $max_transactions        = max($max_transactions, $p['transactions']);
        $max_revenue             = max($max_revenue, $p['revenue']);
        $max_pv_per_visit        = max($max_pv_per_visit, $p['pv_per_visit']);
        $max_rev_per_visit       = max($max_rev_per_visit, $p['rev_per_visit']);
        $max_rev_per_transaction = max($max_rev_per_transaction, $p['rev_per_transaction']);
    }


    $new_curr_chart_date = $current_date * 1000;
    $new_last_chart_date = $last_date * 1000;

    $this_time = "<span class='time-left muted'>$current_chart_label</span>";
    $last_time = "<span class='time-right muted'>$old_chart_label</span>";

}

?>

<?= $report_overlay; ?>
<?= $sub_navigation; ?>
<?= $popover_custom_date;?>



<div class='clearfix'></div>
<div class=''>

<?php if ($calcs_found) { ?>

<div class='accordion' id='accordion3' style='margin-bottom:25px'>
    <div class='accordion-group' style='margin-bottom:25px'>

	    <div id='collapseTwo' class='accordion-body collapse in'>
            <div class='accordion-inner'>
                <div class="row dashboard" style='margin-bottom:-10px;'>
                    <div class="" style='text-align:left;'>
                        <div class="row" style='margin:10px 0 10px 30px;'>
                            <div class='feature-wrap'>
                                <div id="visits-toggle-dash" class="feature feature-left active">

									<div>
										<div class='feature-stat'><?= $traffic_calcs_formatted[$current_date]['total_visits'];?></div>
									</div>
									<h4> Visits </h4>
									<div class='feature-growth'>
										<span class='time'>Daily Avg.</span>
										<span class='growth'><?= $traffic_calcs_formatted[$current_date]['avg_daily_visits'];?></span>
									</div>
                                    
								</div>


								<div id="visitors-toggle-dash" class="feature feature-middle">

									<div>
										<div class='feature-stat'><?=$traffic_calcs_formatted[$current_date]['total_visitors'];?></div>
									</div>
									<h4> Visitors </h4>
									<div class='feature-growth'>
										<span class='time'>Daily Avg.</span>
										<span class='growth'><?=$traffic_calcs_formatted[$current_date]['avg_daily_visitors'];?></span>
									</div>
								</div>


								<div id="pageviews-toggle-dash" class="feature feature-middle">

									<div>
										<div class='feature-stat'><?=$traffic_calcs_formatted[$current_date]['total_pageviews'];?></div>
									</div>
									<h4> Pageviews </h4>
									<div class='feature-growth'>
										<span class='time'>Daily Avg.</span>
										<span class='growth'><?=$traffic_calcs_formatted[$current_date]['avg_daily_pageviews'];?></span>
									</div>
								</div>


								<div id="revenue-toggle-dash" class="feature feature-right">

									<div>
										<div class='feature-stat'><?=$traffic_calcs_formatted[$current_date]['total_revenue'];?>
										</div>
									</div>
									<h4> Revenue </h4>
									<div class='feature-growth'>
										<span class='time'>Daily Avg.</span>
										<span class='growth'><?=$traffic_calcs_formatted[$current_date]['avg_daily_revenue'];?></span>
									</div>
								</div>


                            </div>
                        </div>
                    </div>

                    <div class="" style='margin-left:30px'>
                        <div class="row" style='margin-left:0px;'>
                            <div class="feature-wrap-charts" style='text-align:left; margin-bottom: 20px;'>

                                <script type='text/javascript' src='https://www.google.com/jsapi'></script>
							    <script type='text/javascript'>

							      google.load('visualization', '1.1', {packages: ['corechart', 'controls']});

							      google.setOnLoadCallback(drawVisualization);



							      function drawVisualization() {
							      		var dashboard2 = new google.visualization.Dashboard(document.getElementById('repin_chart_div'));

							      		var control2 = new google.visualization.ControlWrapper({
							              'controlType': 'ChartRangeFilter',
							              'containerId': 'control2',
							              'options': {
							                // Filter by the date axis.
							                'filterColumnIndex': 0,
							                'ui': {
							                  'chartType': 'AreaChart',
							                  'chartOptions': {
							                    'chartArea': {'width': '80%'},
							                    'hAxis': {'baselineColor': 'none'},
							                    'series': {0:{color: '#5792B3'}},
							                    'curveType':'function',
							                    'animation':{
							                        'duration': 500,
							                        'easing': 'inAndOut'
							                     }
							                  },


							                  // 1 day in milliseconds = 24 * 60 * 60 * 1000 = 86,400,000
							                  'minRangeSize': 86400000
							                }
							              },
							              // Initial range:
							              'state': {'range': {'start': new Date(<?=$new_last_chart_date;?>), 'end': new Date(<?=$new_curr_chart_date;?>)}}
							            });

							      		var chart2 = new google.visualization.ChartWrapper({
							      		  'chartType': 'AreaChart',
							      		  'containerId': 'chart2',
							      		  'options': {
							      		    // Use the same chart area width as the control for axis alignment.
							      		    'chartArea': {'left':'0px','top':'30px','height': '80%', 'width': '80%'},
							      		    'hAxis': {'slantedText': false},
							      		    'legend': {'position': 'top'},
							      		    'series': {0:{color: '#5792B3'}},
							      		    'curveType':'function',
							      		    'animation':{
							      		        'duration': 500,
							      		        'easing': 'inAndOut'
							      		     }
							      		  }

							      		});



										var data = new google.visualization.DataTable();
										   data.addColumn('date', 'Date');
										   data.addColumn('number', 'Visits');
										   data.addColumn('number', 'Visitors');
										   data.addColumn('number', 'Pageviews');
										   data.addColumn('number', 'Revenue');


                            

                            <?php 
                            foreach ($traffic_calcs as $d) {
                            
                                $chart_date_format = date("m/d", strtotime($d['chart_date']));
                                $chart_time        = $d['timestamp'];
                                $chart_time_js     = $chart_time * 1000;
                            
                                $chart_visits    = $d['visits'];
                                $chart_visitors  = $d['visitors'];
                                $chart_pageviews = $d['pageviews'];
                                $chart_revenue   = $d['revenue'];

                                if($chart_time_js != 0){
                                    echo "var date = new Date($chart_time_js);
                                     data.addRow([date, {$chart_visits}, {$chart_visitors}, {$chart_pageviews}, {$chart_revenue}]);";
                                }
                            
                            } ?>


									var visitsBox = document.getElementById('visits-toggle');
									var visitorsBox = document.getElementById('visitors-toggle');
									var pageviewsBox = document.getElementById('pageviews-toggle');
									var revenueBox = document.getElementById('revenue-toggle');


									function drawChart() {



									    // Disabling the buttons while the chart is drawing.
									    visitsBox.checked = false;
									    visitorsBox.checked = false;
									    pageviewsBox.checked = false;
									    revenueBox.checked = false;

									    //google.visualization.events.addListener(chart, 'ready', function() {
									          // Check and enable only relevant boxes.


									          visitsBox.checked = view.getViewColumns().indexOf(1) != -1;

									          visitorsBox.checked = view.getViewColumns().indexOf(2) != -1;

									          pageviewsBox.checked = view.getViewColumns().indexOf(3) != -1;

									          revenueBox.checked = view.getViewColumns().indexOf(4) != -1;



									   	dashboard2.bind(control2, chart2);
									   	dashboard2.draw(view);

									}


								    visitsBox.onclick = function() {
								    	//adding pins
								    	if(visitsBox.checked){
								    		view.setColumns([0,1]);
											chart2.setOption('series', [{'color':'#5792B3'}]);
											control2.setOption('ui',{'chartOptions':{'series': [{'color': '#5792B3'}],'chartArea': {'width': '80%'}}, 'chartType': 'AreaChart'});
											chart2.draw(view);
											control2.draw(view);
											drawChart();
								    	}
								    }

								    visitorsBox.onclick = function() {
								    	//adding repins
								    	if(visitorsBox.checked){
								    		view.setColumns([0,2]);
								    		chart2.setOption('series', [{'color':'#D77E81'}]);
								    		control2.setOption('ui',{'chartOptions':{'series': [{'color': '#D77E81'}],'chartArea': {'width': '80%'}}, 'chartType': 'AreaChart'});
											drawChart();
								    	}
								    }

								    pageviewsBox.onclick = function() {
								    	//adding likes
								    	if(pageviewsBox.checked){
								    		view.setColumns([0,3]);
								    		chart2.setOption('series', [{'color':'#FF9900'}]);
								    		control2.setOption('ui',{'chartOptions':{'series': [{'color': '#FF9900'}],'chartArea': {'width': '80%'}}, 'chartType': 'AreaChart'});
											drawChart();
								    	}
								    }

								    revenueBox.onclick = function() {

								    	//adding comments
								    	if(revenueBox.checked){
								    		view.setColumns([0,4]);
								    		chart2.setOption('series', [{'color':'#549E54'}]);
								    		control2.setOption('ui',{'chartOptions':{'series': [{'color': '#549E54'}],'chartArea': {'width': '80%'}}, 'chartType': 'AreaChart'});
											drawChart();
								    	}
								    }



									var view = new google.visualization.DataView(data);
									view.setColumns([0,1]);

									drawChart();

							      }
							    </script>

                                <div id='repin_chart_div' style='float:left; width:100%;'>
                                    <div id='chart2' style='width: 100%; height: 180px;'></div>
                                    <div id='control2' style='width: 100%; height: 30px;'></div>
                                </div>

<?php
//$curr_rpp = number_format($website_calcs[$current_date]['reach_per_pin'],0);
//$past_rpp = $website_calcs[$last_date]['reach_per_pin'];
//$max_rpp = number_format($max_reach_per_pin);
//$rppc = $curr_rpp - $past_rpp;
//$rpp_chart = $past_rpp;
//$rppc_chart = abs($rppc);
//
//if($rppc < 0){
//    $rppc_color = "max";
//    $rpp_chart = $curr_rpp;
//    $rppc_arrow = "";
//}
//else{
//    $rppc_color = "success";
//    $rppc_arrow = "arrow-up";
//}
//
//if($curr_rpp == $max_profile_reach_per_pin){
//    $rpp_max_color = "text-glow'>New";
//} else {
//    $rpp_max_color = "muted-more'>";
//}
//
//$perc_max_reach_per_pin = ($rpp_chart/$max_reach_per_pin)*100;
//$perc_avg_reach_per_pin = ($avg_reach_per_pin/$max_reach_per_pin)*100;
//$perc_rppc = ($rppc_chart/$max_reach_per_pin)*100-0.2;
//
//
//
////daily stats
//$max_daily_pins_avg = max($daily_pins_avgs);
//$curr_daily_pins_avg = $daily_pins_avgs[0];
//$avg_daily_pins_avg = number_format((array_sum($daily_pins_avgs)/count($daily_pins_avgs)),1);
//if($curr_daily_pins_avg == $max_daily_pins_avg){
//    $daily_pins_max_color = "text-glow'>New";
//} else {
//    $daily_pins_max_color = "muted-more'>";
//}
//
//
//$max_daily_pinners_avg = max($daily_pinners_avgs);
//$curr_daily_pinners_avg = $daily_pinners_avgs[0];
//$avg_daily_pinners_avg = number_format((array_sum($daily_pinners_avgs)/count($daily_pinners_avgs)),1);
//if($curr_daily_pinners_avg == $max_daily_pinners_avg){
//    $daily_pinners_max_color = "text-glow'>New";
//} else {
//    $daily_pinners_max_color = "muted-more'>";
//}

//								print
//								"<div class='visits-gauge active span' style='height: 150px; margin-left:0px'>
//									<div style='margin:0 auto; text-align:center;'><h4>Pins / Day</h4></div>
//									<div class='muted' style='margin:-10px auto 15px; text-align:center;'><small>7-day Moving Averages</small></div>
//									<div class='website-gauge-stats' style='margin-top:30px;'>
//										<h2 id='visits-value'>$curr_daily_pins_avg</h2>
//									</div>
//									<div class='website-gauge-stats' style='margin-top:70px;'>
//										<div style='width:70px; margin:0 auto;'>
//											<h4 style='margin-bottom:0px;border-top:1px solid #777; padding-top:3px;color:#999'>$avg_daily_pins_avg</h4>
//										</div>
//										<div style='width:70px; margin:-5px auto;'>
//											<small class='muted' style='margin-top:-15px;'>Average</small>
//										</div>
//									</div>
//									<div style='position: absolute;margin-top: 130px;margin-left: 190px;'>
//										<small class='$daily_pins_max_color 30-Day High</small>
//									</div>
//									<canvas id='visits-gauge' class='profile-gauge-canvas'></canvas>
//									<script type='text/javascript'>
//										var opts = {
//										  'lines': 12, // The number of lines to draw
//										  'angle': 0.18, // The length of each line
//										  'lineWidth': 0.12, // The line thickness
//										  'pointer': {
//										    'length': 0.9, // The radius of the inner circle
//										    'strokeWidth': 0.0, // The rotation offset
//										    'color': '#000000' // Fill color
//										  },
//										  'colorStart': '#D77E81',   // Colors
//										  'colorStop': '#E7B2B4',    // just experiment with them
//										  'strokeColor': '#EEEEEE',   // to see which ones work best for you
//										  'generateGradient': true
//										};
//										var target = document.getElementById('visits-gauge'); // your canvas element
//										var gauge = new Donut(target).setOptions(opts); // create sexy gauge!
//										gauge.maxValue = $max_daily_pins_avg; // set max gauge value
//										gauge.animationSpeed = 32; // set animation speed (32 is default value)
//										gauge.set($curr_daily_pins_avg); // set actual value
//										var textRenderer = new TextRenderer(document.getElementById('visits-value'))
//										textRenderer.render = function(gauge){
//										   percentage = gauge.displayedValue;
//										   this.el.innerHTML = (percentage).toFixed(1);
//										};
//										gauge.setTextField(textRenderer);
//										//gauge.setTextField(document.getElementById('visits-value'));
//									</script>
//								</div>";
//
//
//								print
//								"<div class='visitors-gauge span' style='height: 150px; margin-left:0px'>
//									<div style='margin:0 auto; text-align:center;'><h4>Pinners / Day</h4></div>
//									<div class='muted' style='margin:-10px auto 15px; text-align:center;'><small>7-day Moving Averages</small></div>
//									<div class='website-gauge-stats' style='margin-top:30px;'>
//										<h2 id='visitors-value'>$curr_daily_pinners_avg</h2>
//									</div>
//									<div class='website-gauge-stats' style='margin-top:70px;'>
//										<div style='width:70px; margin:0 auto;'>
//											<h4 style='margin-bottom:0px;border-top:1px solid #777; padding-top:3px;color:#999'>$avg_daily_pinners_avg</h4>
//										</div>
//										<div style='width:70px; margin:-5px auto;'>
//											<small class='muted' style='margin-top:-15px;'>Average</small>
//										</div>
//									</div>
//									<div style='position: absolute;margin-top: 130px;margin-left: 190px;'>
//										<small class='$daily_pinners_max_color 30-Day High</small>
//									</div>
//									<canvas id='visitors-gauge' class='profile-gauge-canvas'></canvas>
//									<script type='text/javascript'>
//										var opts = {
//										  'lines': 12, // The number of lines to draw
//										  'angle': 0.18, // The length of each line
//										  'lineWidth': 0.12, // The line thickness
//										  'pointer': {
//										    'length': 0.9, // The radius of the inner circle
//										    'strokeWidth': 0.0, // The rotation offset
//										    'color': '#000000' // Fill color
//										  },
//										  'colorStart': '#4E7E98',   // Colors
//										  'colorStop': '#79ABC6',    // just experiment with them
//										  'strokeColor': '#EEEEEE',   // to see which ones work best for you
//										  'generateGradient': true
//										};
//										var target2 = document.getElementById('visitors-gauge'); // your canvas element
//										var gauge2 = new Donut(target2).setOptions(opts); // create sexy gauge!
//										gauge2.maxValue = $max_daily_pinners_avg; // set max gauge value
//										gauge2.animationSpeed = 32; // set animation speed (32 is default value)
//										gauge2.set($curr_daily_pinners_avg); // set actual value
//										var textRenderer = new TextRenderer(document.getElementById('visitors-value'))
//										textRenderer.render = function(gauge2){
//										   percentage = gauge2.displayedValue;
//										   this.el.innerHTML = (percentage).toFixed(1);
//										};
//										gauge2.setTextField(textRenderer);
//										//gauge2.setTextField(document.getElementById('visitors-value'));
//									</script>
//								</div>";
//
//
//								print
//								"<div class='pageviews-gauge span' style='height: 150px; margin-left:0px'>
//									<div style='margin:0 auto; text-align:center;'><h4>Pinners / Day</h4></div>
//									<div class='muted' style='margin:-10px auto 15px; text-align:center;'><small>7-day Moving Averages</small></div>
//									<div class='website-gauge-stats' style='margin-top:30px;'>
//										<h2 id='pageviews-value'>$curr_daily_pinners_avg</h2>
//									</div>
//									<div class='website-gauge-stats' style='margin-top:70px;'>
//										<div style='width:70px; margin:0 auto;'>
//											<h4 style='margin-bottom:0px;border-top:1px solid #777; padding-top:3px;color:#999'>$avg_daily_pinners_avg</h4>
//										</div>
//										<div style='width:70px; margin:-5px auto;'>
//											<small class='muted' style='margin-top:-15px;'>Average</small>
//										</div>
//									</div>
//									<div style='position: absolute;margin-top: 130px;margin-left: 190px;'>
//										<small class='$daily_pinners_max_color 30-Day High</small>
//									</div>
//									<canvas id='pageviews-gauge' class='profile-gauge-canvas'></canvas>
//									<script type='text/javascript'>
//										var opts = {
//										  'lines': 12, // The number of lines to draw
//										  'angle': 0.18, // The length of each line
//										  'lineWidth': 0.12, // The line thickness
//										  'pointer': {
//										    'length': 0.9, // The radius of the inner circle
//										    'strokeWidth': 0.0, // The rotation offset
//										    'color': '#000000' // Fill color
//										  },
//										  'colorStart': '#4E7E98',   // Colors
//										  'colorStop': '#79ABC6',    // just experiment with them
//										  'strokeColor': '#EEEEEE',   // to see which ones work best for you
//										  'generateGradient': true
//										};
//										var target3 = document.getElementById('pageviews-gauge'); // your canvas element
//										var gauge3 = new Donut(target3).setOptions(opts); // create sexy gauge!
//										gauge3.maxValue = $max_daily_pinners_avg; // set max gauge value
//										gauge3.animationSpeed = 32; // set animation speed (32 is default value)
//										gauge3.set($curr_daily_pinners_avg); // set actual value
//										var textRenderer = new TextRenderer(document.getElementById('pageviews-value'))
//										textRenderer.render = function(gauge3){
//										   percentage = gauge3.displayedValue;
//										   this.el.innerHTML = (percentage).toFixed(1);
//										};
//										gauge3.setTextField(textRenderer);
//										//gauge2.setTextField(document.getElementById('pinners-value'));
//									</script>
//								</div>";
//
//								print
//								"<div class='revenue-gauge span' style='height: 150px; margin-left:0px'>
//									<div style='margin:0 auto; text-align:center;'><h4>Pinners / Day</h4></div>
//									<div class='muted' style='margin:-10px auto 15px; text-align:center;'><small>7-day Moving Averages</small></div>
//									<div class='website-gauge-stats' style='margin-top:30px;'>
//										<h2 id='revenue-value'>$curr_daily_pinners_avg</h2>
//									</div>
//									<div class='website-gauge-stats' style='margin-top:70px;'>
//										<div style='width:70px; margin:0 auto;'>
//											<h4 style='margin-bottom:0px;border-top:1px solid #777; padding-top:3px;color:#999'>$avg_daily_pinners_avg</h4>
//										</div>
//										<div style='width:70px; margin:-5px auto;'>
//											<small class='muted' style='margin-top:-15px;'>Average</small>
//										</div>
//									</div>
//									<div style='position: absolute;margin-top: 130px;margin-left: 190px;'>
//										<small class='$daily_pinners_max_color 30-Day High</small>
//									</div>
//									<canvas id='revenue-gauge' class='profile-gauge-canvas'></canvas>
//									<script type='text/javascript'>
//										var opts = {
//										  'lines': 12, // The number of lines to draw
//										  'angle': 0.18, // The length of each line
//										  'lineWidth': 0.12, // The line thickness
//										  'pointer': {
//										    'length': 0.9, // The radius of the inner circle
//										    'strokeWidth': 0.0, // The rotation offset
//										    'color': '#000000' // Fill color
//										  },
//										  'colorStart': '#4E7E98',   // Colors
//										  'colorStop': '#79ABC6',    // just experiment with them
//										  'strokeColor': '#EEEEEE',   // to see which ones work best for you
//										  'generateGradient': true
//										};
//										var target4 = document.getElementById('revenue-gauge'); // your canvas element
//										var gauge4 = new Donut(target4).setOptions(opts); // create sexy gauge!
//										gauge4.maxValue = $max_daily_pinners_avg; // set max gauge value
//										gauge4.animationSpeed = 32; // set animation speed (32 is default value)
//										gauge4.set($curr_daily_pinners_avg); // set actual value
//										var textRenderer = new TextRenderer(document.getElementById('revenue-value'))
//										textRenderer.render = function(gauge4){
//										   percentage = gauge4.displayedValue;
//										   this.el.innerHTML = (percentage).toFixed(1);
//										};
//										gauge4.setTextField(textRenderer);
//										//gauge2.setTextField(document.getElementById('pinners-value'));
//									</script>
//								</div>";
?>

                            </div>

                            <hr>

							<div class="feature-wrap-chart-controls" style='text-align:left;position: absolute; left:-99999px;'>

								<form>
									<input id='visits-toggle' type='radio' value='visits-toggle'> Pins </input>
									<input id='visitors-toggle' type='radio' value='visitors-toggle'> Pinners </input>
									<input id='pageviews-toggle' type='radio' value='pageviews-toggle'> Reach </input>
									<input id='revenue-toggle' type='radio' value='revenue-toggle'> Pinners </input>
								</form>

							</div>
						</div>

                        <div class="row" style='margin-bottom:10px;'>
                            <div class="span5">
                            </div>
						</div>
                    </div>
                </div>
            </div>

            <div class="span5 margin-fix" style='text-align:center;padding-bottom:6px;padding-top:3px'>
            </div>
        </div>
    </div>
</div>


<?php
}
?>

<script type="text/javascript">

    jQuery(document).ready(function ($) {
//$('.feature-stat').fitText(1.3);

        $('#visits-toggle').click(function () {
            $('.visits-gauge').addClass('active');
            $('.visitors-gauge').removeClass('active');
            $('.pageviews-gauge').removeClass('active');
            $('.revenue-gauge').removeClass('active');
        });
        $('#visitors-toggle').click(function () {
            $('.visits-gauge').removeClass('active');
            $('.visitors-gauge').addClass('active');
            $('.pageviews-gauge').removeClass('active');
            $('.revenue-gauge').removeClass('active');
        });
        $('#pageviews-toggle').click(function () {
            $('.visits-gauge').removeClass('active');
            $('.visitors-gauge').removeClass('active');
            $('.pageviews-gauge').addClass('active');
            $('.revenue-gauge').removeClass('active');
        });
        $('#revenue-toggle').click(function () {
            $('.visits-gauge').removeClass('active');
            $('.visitors-gauge').removeClass('active');
            $('.pageviews-gauge').removeClass('active');
            $('.revenue-gauge').addClass('active');
        });

        $("#visits-toggle-dash").on('click', function () {
            $("#visits-toggle").trigger('click');
            $("#visits-toggle").trigger('click');
            $("#visits-toggle-dash").addClass('active');
            $("#visitors-toggle-dash").removeClass('active');
            $("#pageviews-toggle-dash").removeClass('active');
            $("#revenue-toggle-dash").removeClass('active');
            gauge.set(0);
            gauge.set(<?php echo $curr_daily_pins_avg ?>);
        });
        $("#visitors-toggle-dash").on('click', function () {
            $("#visitors-toggle").trigger('click');
            $("#visitors-toggle").trigger('click');
            $("#visits-toggle-dash").removeClass('active');
            $("#visitors-toggle-dash").addClass('active');
            $("#pageviews-toggle-dash").removeClass('active');
            $("#revenue-toggle-dash").removeClass('active');
            gauge2.set(0);
            gauge2.set(<?php echo $curr_daily_pinners_avg ?>);
        });
        $("#pageviews-toggle-dash").on('click', function () {
            $("#pageviews-toggle").trigger('click');
            $("#pageviews-toggle").trigger('click');
            $("#visits-toggle-dash").removeClass('active');
            $("#visitors-toggle-dash").removeClass('active');
            $("#pageviews-toggle-dash").addClass('active');
            $("#revenue-toggle-dash").removeClass('active');
            gauge3.set(0);
            gauge3.set(<?php echo $curr_daily_pinners_avg ?>);
        });
        $("#revenue-toggle-dash").on('click', function () {
            $("#revenue-toggle").trigger('click');
            $("#revenue-toggle").trigger('click');
            $("#visits-toggle-dash").removeClass('active');
            $("#visitors-toggle-dash").removeClass('active');
            $("#pageviews-toggle-dash").removeClass('active');
            $("#revenue-toggle-dash").addClass('active');
            gauge4.set(0);
            gauge4.set(<?php echo $curr_daily_pinners_avg ?>);
        });

    });

</script>

<?php



function cmp($a, $b)
{
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

function timesort($a, $b)
{
    $t = "chart_date";

    if ($a["$t"] > $b["$t"]) {
        return 1;
    } else if ($a["$t"] == $b["$t"]) {
        return 0;
    } else {
        return -1;
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

function formatRatio($x)
{
    if (!$x) {
        return "-";
    } else {
        return number_format($x, 1) . "<span style='color:#777'>:1</span>";
    }
}

function formatPercentage($x)
{
    if ($x >= 0) {
        return "<span style='color: #549E54;font-weight:normal;font-size:12px'>(" . number_format($x * 100, 1) . "%)</span>";
    } else if ($x < 0 && $x > -1) {
        return "<span style='color: #aaa;font-weight:normal;font-size:12px'>(" . number_format($x * 100, 1) . "%)</span>";
    } else if ($x == "na") {
        return "<span style='color: #aaa;font-weight:normal;font-size:12px'>(--%)</span>";
    } else if ($x == -1) {
        return "<span style='color: #aaa;font-weight:normal;font-size:12px'>(--%)</span>";
    }
}

function formatRev($x)
{
    if ($x > 0) {
        return "<span class='pos'>$" . number_format($x, 0) . "</span>";
    } elseif ($x == 0) {
        return "<span class='neg'> &nbsp;--</span>";
    } else {
        return "<span class='neg'>$" . number_format($x, 0) . "</span>";
    }
}

function formatAbsoluteRatio($x)
{
    if ($x > 0) {
        return "<span class='pos'><i class='icon-arrow-up'></i>" . number_format($x, 1) . "</span><span style='color:#777'>:1</span>";
    } elseif ($x == 0) {
        return "<span class='neg'> &nbsp;--</span><span class='muted'>:1</span>";
    } else {
        return "<span class='neg'><i class='icon-arrow-down'></i>" . number_format($x, 1) . "</span><span style='color:#777'>:1</span>";
    }
}

function formatAbsoluteRev($x)
{
    if ($x > 0) {
        return "$" . number_format($x, 2) . "";
    } elseif ($x == 0) {
        return "<span class='neg'> &nbsp;--</span>";
    } else {
        return "<span class='neg'><i class='icon-arrow-down'></i>$" . number_format($x, 2) . "</span>";
    }
}

function formatAbsoluteKPI($x)
{
    if ($x > 0) {
        return "<span style='color: green;'><i class='icon-arrow-up'></i>" . number_format($x, 2) . "</span>";
    } elseif ($x == 0) {
        return "<span style='color: #aaa;'> &nbsp;--</span>";
    } else {
        return "<span style='color: #aaa;'><i class='icon-arrow-down'></i>" . number_format($x, 2) . "</span>";
    }
}
