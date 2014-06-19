<?php
    $page = "Time";

    if (isset($_GET['csv'])) {
        $date = date("F-j-Y");
        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"Tailwind-Analytics-Date-Time-$date.csv\"");

        echo "day, time" . "\n";

        foreach ($day_counter as $day) {
            var_dump($day);
            exit;
        }
        exit;
    }

    $datePicker = true;
?>

<div class='clearfix'></div>
<div class=''>

<div class='accordion' id='accordion4' style='margin-bottom:25px'>
    <div class='accordion-group' style='margin-bottom:25px'>
        <div class='accordion-heading'>
            <div class='accordion-toggle'
                 data-parent='#accordion4'
                 href='#collapseThree' style='cursor:default; display:inline-block'
            >
                <h2 style='float:left'>When Should You Be Pinning?</h2>

                <span class='pull-right help-icon'>
                    <a data-toggle='popover'
                       data-container='body'
                       data-original-title='<strong>When Should You Be Pinning?</strong>'
                       data-content="
                            <strong>Size of Circle:</strong>
                            Number of pins you've created at a certain time. Larger circles
                            represent more pins.
                            <br><br>
                            <strong>Shade of Circle:</strong>
                            Virality (repins / pin) of your pins at a given time. Darker shades of
                            green mean higher virality.
                            <br><br>
                            Hover over any point with your mouse to see the exact number of pins and
                            their level of virality for that day and time.
                        "
                       data-placement='left'
                    >
                        <i id='header-icon' class='icon-help'></i>
                    </a>
                </span>
            </div>
        </div>

        <div id='collapseThree' class='accordion-body collapse in'>
            <div class='accordion-inner'>
                <div class="row-fluid" style='margin-bottom:10px;'>
                    <div class="span" style='text-align:center;padding-bottom:6px;padding-top:3px'>
                        <div style='margin:0 auto; width:100%;'>
                            <div style='width:45%; text-align:left;float:left;padding-left:2.5%;'>

                                <strong class='lead'>Best Day of the Week</strong>
                                <hr>

                                <em>You Pin the Most on</em>:
                                <span class='label label-warning'>
                                    <strong><?= day_name($best_pins_day) ?></strong>
                                </span>
                                <?= $max_day_pins ?> Pins (getting <?= $max_day_pins_avg_repins_per_pin ?> avg. Repins/Pin)
                                <br>

                                <em>Best Day to Pin</em>:
                                <span class='label label-success'>
                                    <strong><?= day_name($best_repins_per_pin_day) ?></strong>
                                </span>
                                <?= $best_repins_per_pin_day_pins?> Pins
                                (getting <?= $max_day_repins_per_pin ?> Repins/Pin -
                                <?= ((number_format($max_day_repins_per_pin / $total_repins_per_pin, 1) - 1) * 100) ?>%
                                above average)
                                <br><br>

                            </div>
                            <div style='width:45%; text-align:left;float:left;padding-left:2.5%;'>
                                <strong class='lead'>Best Time of Day</strong>
                                <hr>

                                <em>You Pin the Most at</em>:
                                <span class='label label-warning'>
                                    <strong><?= time_format($best_pins_hour) ?></strong>
                                </span>
                                <?= $max_hour_pins ?> Pins (<?= $max_hour_pins_avg_repins_per_pin ?> avg. Repins/Pin)
                                <br>

                                <em>Best Time to Pin</em>:
                                <span class='label label-success'>
                                    <strong><?= time_format($best_repins_per_pin_hour) ?></strong>
                                </span>
                                <?= $max_hour_repins_per_pin ?> Repins/Pin (<?= ((number_format($max_hour_repins_per_pin / $total_repins_per_pin, 1) - 1) * 100) ?>%
                                above average)
                                <br>
                            </div>
                        </div>
                    </div>
                </div>
            <div class="row-fluid" style='margin-bottom:10px;'>
                <div class="span" style='text-align:center;padding-bottom:6px;padding-top:3px'>

                    <? //-------------------------// BRAND MENTIONS PER DAY CHART //---------------------------// ?>
                    <script type='text/javascript' src='https://www.google.com/jsapi'></script>
                    <script type='text/javascript'>
                        google.load('visualization', '1', {packages: ['corechart']});

                        function drawVisualization() {
                            // Create and populate the data table.
                            var data = new google.visualization.DataTable();

                            data.addColumn('string', 'ID');
                            data.addColumn('number', 'Hours');
                            data.addColumn('number', 'Days');
                            data.addColumn('number', 'Repins/Pin');
                            data.addColumn('number', 'Pins');

                            <?php
                                $max_pins     = 0;
                                $max_pin_time = -1;
                                $max_pin_day  = -1;

                                $max_repins     = 0;
                                $max_repin_time = -1;
                                $max_repin_day  = -1;

                                $max_repins_per_pin      = 0;
                                $max_repins_per_pin_time = -1;
                                $max_repins_per_pin_day  = -1;

                                for ($i = 1; $i < 8; $i++) {
                                    for ($j = 1; $j < 25; $j++) {

                                        @$this_pins = $pin_counter["" . ($i - 1) . "-" . ($j - 1) . ""]['pins'];
                                        if (!$this_pins) {
                                            $this_pins = 0;
                                        }

                                        if ($this_pins > $max_pins) {
                                            $max_pins     = $this_pins;
                                            $max_pin_time = $j - 1;
                                            $max_pin_day  = $i - 1;
                                        }


                                        @$this_repins = $pin_counter["" . ($i - 1) . "-" . ($j - 1) . ""]['repins'];
                                        if (!$this_repins) {
                                            $this_repins = 0;
                                        }
                                        if ($this_repins > $max_repins) {
                                            $max_repins     = $this_repins;
                                            $max_repin_time = $j - 1;
                                            $max_repin_day  = $i - 1;
                                        }

                                        @$this_repins_per_pin = $pin_counter["" . ($i - 1) . "-" . ($j - 1) . ""]['repins_per_pin'];
                                        if (!$this_repins_per_pin) {
                                            $this_repins_per_pin = 0;
                                        }
                                        if ($this_repins_per_pin > $max_repins_per_pin) {
                                            $max_repins_per_pin      = $this_repins_per_pin;
                                            $max_repins_per_pin_time = $j - 1;
                                            $max_repins_per_pin_day  = $i - 1;
                                        }

                                        print "data.addRow(['', $j, $i, $this_repins_per_pin, $this_pins]);";
                                    }
                                }
                            ?>

                            var options = {
                                'title': 'When Are Your Pins Getting the Most Engagement?',
                                'hAxis': {'title': 'Time of Day (CST)','textPosition':'none','gridlines': {'count':26}, 'minValue':0,'maxValue':25},
                                'vAxis': {'title': '','gridlines': {'count':9},'textPosition':'none','direction':-1,'minValue':0,'maxValue':8},
                                'sizeAxis':{'minSize':0,'maxSize':25},
                                'backgroundColor':'transparent',
                                'chartArea':{
                                    'width':'73%'
                                }
                            };

                            var chart = new google.visualization.BubbleChart(document.getElementById('brand_pin_time'));
                            chart.draw(data, options);
                        };

                        google.setOnLoadCallback(drawVisualization);
                    </script>

                    <script type='text/javascript'>
                        google.load('visualization', '1', {packages:['corechart']});
                        google.setOnLoadCallback(drawChart);

                        function drawChart() {
                            var data = google.visualization.arrayToDataTable([
                                ['x', 'Cats'],
                                ['12am',  1],
                                ['1am',   1],
                                ['2am',   1],
                                ['3am',   1],
                                ['4am',   1],
                                ['5am',   1],
                                ['6am',   1],
                                ['7am',   1],
                                ['8am',   1],
                                ['9am',   1],
                                ['10am',  1],
                                ['11am',  1],
                                ['12pm',  1],
                                ['1pm',   1],
                                ['2pm',   1],
                                ['3pm',   1],
                                ['4pm',   1],
                                ['5pm',   1],
                                ['6pm',   1],
                                ['7pm',   1],
                                ['8pm',   1],
                                ['9pm',   1],
                                ['10pm',  1],
                                ['11pm',  1]
                            ]);

                            var options = {
                                'curveType': 'function',
                                'hAxis': {
                                    'textStyle': {
                                        'fontSize':10
                                    },
                                    'slantedText':true,
                                    'slantedTextAngle':30,
                                    'gridlines': {'count':26},
                                    'showTextEvery':1
                                },
                                'width':'85%',
                                'chartArea':{
                                    'width':'71%'
                                },
                                'vAxis': {
                                    'textPosition':'none',
                                    'gridlines':{
                                        'count':0
                                    }
                                },
                                'legend':{
                                    'position':'none'
                                },
                                'series':{0:{'color':'transparent'
                                    }
                                },
                                'backgroundColor':'transparent'
                            };

                            // Create and draw the visualization.
                            var chart = new google.visualization.ColumnChart(document.getElementById('visualization'));
                            chart.draw(data, options);
                        }

                        google.setOnLoadCallback(drawChart);
                    </script>

                    <div id='brand_pin_time' style='position:relative; z-index:10; float:left; margin-bottom:35px; width: 100%; height: 400px;'></div>
                        <div class='bpt-v-axis' style='z-index:12'>
                            <div class='bpt-v1'>&nbsp;</div>

                            <?php for ($i = 0; $i < 7; $i++): ?>
                                <div class='bpt-v2'><?= day_name($i) ?></div>
                            <?php endfor ?>

                            <div class='bpt-v1'>&nbsp;</div>
                        </div>
                        <div id='visualization' style='float:left; width:97.1%; height:300px; margin:81px auto; position:absolute'></div>
                    </div>
                </div>
                <div class="row" style='margin-bottom:10px;'>
                    <div class="span11" style='text-align:center;padding-bottom:6px;padding-top:3px'>

<?php
//-------------------------// PROFILE PINS PER DAY CHART //---------------------------//

//							print "<script type='text/javascript' src='https://www.google.com/jsapi'></script>
//							    <script type='text/javascript'>
//							      google.load('visualization', '1', {packages: ['corechart']});
//
//							      function drawVisualization() {
// Create and populate the data table.
//							        	var data = google.visualization.arrayToDataTable([
//							          		['Day','Pins','Engagement per Pin','Repins','Likes','Comments']";
//
//
//							         	$highest_pin_day = "";
//							         	$highest_pins = 0;
//							         	$highest_actions_day = "";
//							         	$highest_actions = 0;
//							         	$highest_efficiency_day = "";
//							         	$highest_actions_per_pin = 0;
//
//
//							   			for ($i = 0; $i < 7; $i++) {
//
//							   				if($day_counter[$i]['pins']==""){
//
//							   					$c_pins = 0;
//							   					$c_repins = 0;
//							   					$c_likes = 0;
//							   					$c_comments = 0;
//							   					$c_actions = 0;
//							   					$c_actions_per_pin = 0;
//							   				}
//							   				else{
//							   					$c_day = $day_counter[$i]['day'];
//							   					$c_pins = $day_counter[$i]['pins'];
//							   					$c_repins = $day_counter[$i]['repins'];
//							   					$c_likes = $day_counter[$i]['likes'];
//							   					$c_comments = $day_counter[$i]['comments'];
//							   					$c_actions = $day_counter[$i]['actions'];
//							   					$c_actions_per_pin = $day_counter[$i]['actions_per_pin'];
//							   				}
//
//							   				if($i==0){
//							   					$day_format =  "Sunday";
//							   				}
//							   				elseif($i==1){
//							   					$day_format = "Monday";
//							   				}
//							   				elseif($i==2){
//							   					$day_format = "Tuesday";
//							   				}
//							   				elseif($i==3){
//							   					$day_format = "Wednesday";
//							   				}
//							   				elseif($i==4){
//							   					$day_format = "Thursday";
//							   				}
//							   				elseif($i==5){
//							   					$day_format = "Friday";
//							   				}
//							   				elseif($i==6){
//							   					$day_format = "Saturday";
//							   				}
//
//							   				print ",['$day_format',$c_pins,$c_actions_per_pin,$c_repins,$c_likes,$c_comments]";
//
//
//											if($c_pins > $highest_pins){
//												$highest_pins = $c_pins;
//												$highest_pins_day = $day_format;
//											}
//											if($c_actions > $highest_actions){
//												$highest_actions = $c_actions;
//												$highest_actions_day = $day_format;
//											}
//											if($c_actions_per_pin > $highest_actions_per_pin){
//												$highest_actions_per_pin = $c_actions_per_pin;
//												$highest_efficiency_day = $day_format;
//											}
//
//										}
//
//							print "
//											]);
//
// Create and draw the visualization.
//
//							        	var options = {
//							        	          'title' : 'Your Pinning Activity and Engagement by Day',
//							        	          'height': 400,
//							        	          'vAxis': {'title': ''},
//							        	          'hAxis': {'title': ''},
//							        	          'seriesType': 'bars',
//							        	          'isStacked':true,
//							        	          'series': {0:{'type': 'area','pointSize':3,'color':'#DC3912'}, 1: {'type': 'line','targetAxisIndex':1,'pointSize':5,'lineWidth':3,'color': '#109618'}, 2:{'color': '#dae3f5'}, 3:{'color': '#a7bce7'}, 4:{'color': '#3366CC'}}
//							        	};
//
//							        	var days = new google.visualization.ComboChart(document.getElementById('combo_pins'));
//							        	days.draw(data, options);
//							        }
//
//							        google.setOnLoadCallback(drawVisualization);
//							    </script>";
//
//						print "<div id='combo_pins' style='float:left; margin-bottom:35px; width: 100%; height: 400px;'></div>
//
//							<div class='combo-h-axis'>
//
//								<div class='combo-h2-left'>Your Highest Pin Activity: <br><strong class='combo-day'>$highest_pins_day</strong> <br>(".$highest_pins." pins)</div>";
//
//								<div class='combo-h2'>Highest Engagement: <br><strong class='combo-day'>$highest_actions_day</strong> <br>(".$highest_actions." total actions)</div>
//								print
//								"<div class='combo-h2-right'>Your Best Day to Pin: <br><strong class='combo-day'>$highest_efficiency_day</strong> <br>(".$highest_actions_per_pin." Engagement/Pin)</div>
//
//
//							</div>
//							";
//
//
//
//
//				print "
//						</div>
//					</div>";
//
//
//
//			print "
//		</div>
//	</div>
//</div>";


//						print "
//						<div id='wcdiv' style='width: 500px; height:300px; border: 1px solid #ccc'></div>
//						<script type='text/javascript'>
//						     google.load('visualization', '1');
//						     google.setOnLoadCallback(draw);
//
//						     function draw() {
//						       var data = new google.visualization.DataTable();
//						        data.addColumn('string', 'Text1');
//						        data.addRows(".sizeof($descriptions).");";
//
//
//
//						       $i = 0;
//						       foreach($descriptions as $desc){
//
//						       		$pin_cloud_description = $desc['description'];
//
//						       		print "data.setCell(".$i.", 0, '".$pin_cloud_description."');
//						       		";
//
//						       		$i++;
//						       }
//
//						       print: "
//
//						       var options = {'stopWords': 'a an and is or the of for to'};
//						       var wc = new WordCloud(document.getElementById('wcdiv'));
//						       wc.draw(data, options);
//						     };
//						   </script>";
?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php

function day_name($i)
{

    if ($i == 0) {
        $day_format = "Sunday";
    } elseif ($i == 1) {
        $day_format = "Monday";
    } elseif ($i == 2) {
        $day_format = "Tuesday";
    } elseif ($i == 3) {
        $day_format = "Wednesday";
    } elseif ($i == 4) {
        $day_format = "Thursday";
    } elseif ($i == 5) {
        $day_format = "Friday";
    } elseif ($i == 6) {
        $day_format = "Saturday";
    } else {
        $day_format = "n/a";
    }

    return $day_format;
}

function time_format($i)
{
    if ($i > 0 && $i < 12) {
        $time_format = $i . "am";
    } elseif ($i == 12) {
        $time_format = "12pm";
    } elseif ($i == 0) {
        $time_format = "12am";
    } else {
        $time_format = $i - 12 . "pm";
    }

    return $time_format;
}


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

function formatNumber($x)
{
    if (!$x) {
        return "-";
    } else {
        return number_format($x);
    }
}

function formatPercentage($x)
{
    $x = $x * 100;
    if ($x >= 0) {
        return "<span style='color: green;font-weight:normal;font-size:12px'>(" . number_format($x, 1) . "%)</span>";
    } else if ($x < 0) {
        return "<span style='color: #aaa;font-weight:normal;font-size:12px'>(" . number_format($x, 1) . "%)</span>";
    } else if ($x == "na") {
        return "<span style='color: #aaa;font-weight:normal;font-size:12px'>(--%)</span>";
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

function renameCategories($a)
{

    if ($a == "womens_fashion") {
        $b = "womens fashion";
    } elseif ($a == "diy_crafts") {
        $b = "diy & crafts";
    } elseif ($a == "health_fitness") {
        $b = "health & fitness";
    } elseif ($a == "holidays_events") {
        $b = "holidays & events";
    } elseif ($a == "none") {
        $b = "not specified";
    } elseif ($a == "holiday_events") {
        $b = "holidays & events";
    } elseif ($a == "home_decor") {
        $b = "home decor";
    } elseif ($a == "food_drink") {
        $b = "food & drink";
    } elseif ($a == "film_music_books") {
        $b = "film, music & books";
    } elseif ($a == "hair_beauty") {
        $b = "hair & beauty";
    } elseif ($a == "cars_motorcycles") {
        $b = "cars & motorcycles";
    } elseif ($a == "science_nature") {
        $b = "science & nature";
    } elseif ($a == "mens_fashion") {
        $b = "mens fashion";
    } elseif ($a == "illustrations_posters") {
        $b = "illustrations & posters";
    } elseif ($a == "art_arch") {
        $b = "art & architecture";
    } elseif ($a == "wedding_events") {
        $b = "weddings & events";
    } else {
        $b = $a;
    }

    return $b;

}

function max_with_2keys($array, $key1, $key2)
{
    if (!is_array($array) || count($array) == 0) return false;
    $max = $array[0][$key1][$key2];
    foreach ($array as $a) {
        if ($a[$key1][$key2] > $max) {
            $max = $a[$key1][$key2];
        }
    }

    return $max;
}

function min_with_2keys($array, $key1, $key2)
{
    if (!is_array($array) || count($array) == 0) return false;
    $min = INF;
    foreach ($array as $a) {
        if (($a[$key1][$key2] < $min) && ($a[$key1][$key2] != 0)) {
            $min = $a[$key1][$key2];
        }
    }

    return $min;
}

function max_change_2keys($array, $key1, $key2, $data)
{
    if (!is_array($array) || count($array) == 0) return false;
    $max = $array[0][$key1][$data] - $array[0][$key2][$data];
    foreach ($array as $a) {
        if (($a[$key1][$data] != 0) && ($a[$key2][$data] != 0) && ($a[$key1][$data] - $a[$key2][$data] > $max)) {
            $max = $a[$key1][$data] - $a[$key2][$data];
        }
    }

    return $max;
}

function hex2rgb($hex)
{
    $hex = str_replace("#", "", $hex);

    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    $rgb = "rgba(" . $r . ", " . $g . ", " . $b . ", 0.4)";

    // returns the rgb values separated by commas
    return $rgb;
}
