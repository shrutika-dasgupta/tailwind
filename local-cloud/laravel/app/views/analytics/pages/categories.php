<?php ini_set('display_errors', 'off');
error_reporting(0);

$page = 'categories';

if (isset($_GET['csv'])){
    $date = date("F-j-Y");

    echo "\n" . ",,Per,,Category,,Analysis," . "" . "\n" . "\n";
    echo "category,pin_count,repin_count,likes_count,cumulative_followers,boards_count,virality_score,engagement_score" . "" . "\n";

    @usort($categories, function($a, $b)
    {
        $t = "engagement_score";

        if ($a["$t"] < $b["$t"]) {
            return 1;
        } else if ($a["$t"] == $b["$t"]) {
            return 0;
        } else {
            return -1;
        }
    });

    //usort($categories, "pinscmp");
    foreach($categories as $category){
        $category_name = $category['category'];
        $pin_count = $category['pins'];
        $repin_count = $category['repins'];
        $likes_count = $category['likes'];
        $followers = $category['total_followers'];
        $boards_count = $category['boards'];
        $engagement_score = $category['engagement_score'];
        $virality_score = $category['repins_per_pin'];
        echo "$category_name,$pin_count,$repin_count,$likes_count,$followers,$boards_count,$virality_score,$engagement_score" . "\n";
    }


    echo "\n" . "\n" . ",,Per,,Board,,Analysis," . "" . "\n" . "\n";
    echo "board_name,category,pin_count,repin_count,comments_count,likes_count,followers,virality_score,engagement_score" . "\n";

    @usort($boards, function($a, $b)
       {
        $t = "repins";

        if ($a["$t"] < $b["$t"]) {
            return 1;
        } else if ($a["$t"] == $b["$t"]) {
            return 0;
        } else {
            return -1;
        }
    });

    foreach($boards as $board){
        if($board['is_owner'] == 1){
            $board_name = $board['name'];
            $category = $board['category'];
            @$number_of_pins = $board['pins'];
            @$number_of_repins = $board['repins'];
            @$number_of_comments = $board['comments'];
            @$number_of_likes = $board['likes'];
            @$board_followers = $board['followers'];
            @$board_engagement = number_format($number_of_repins / $number_of_pins / $board_followers * 1000,2);
            @$board_virality = number_format($number_of_repins / $number_of_pins,2);
            echo "$board_name,$category,$number_of_pins,$number_of_repins,$number_of_comments,$number_of_likes,$board_followers,$board_virality,$board_engagement" . "\n";
        }
    }

    exit;
}


print "<div class='clearfix'></div>";
print "<div class=''>";

echo $export_popover;


print "<div class='accordion' id='accordion3' style='margin-bottom:75px'>
  <div class='accordion-group' style='margin-bottom:25px'>
    <div class='accordion-heading' style='height:40px'>
    	<span class='pull-left' style='height:40px'>
     		<div class='accordion-toggle' data-parent='#accordion3' href='#collapseTwo' style='cursor:default'>
      			<h2 style='color:#333;'> Virality Score Heatmap</h2>
      		</div>
      	</span>
      	<span class='pull-right help-icon'>
            <a data-toggle='popover' data-container='body' data-original-title='<strong>Heatmap Legend</strong>' data-content='<strong>Size of Category:</strong> Number of Pins. <br><strong>Color Brightness:</strong> Virality of Category (Repins/pin). <br><br><strong>Click:</strong> to zoom into your Boards. <br><strong>Right-Click:</strong> to zoom back out to your Categories.' data-placement='left'><i id='header-icon' class='icon-help'></i></a>
        </span>
    </div>
    <div id='collapseTwo' class='accordion-body collapse in'>
         <div class='accordion-inner'>";





print "<div class=\"\" style=''>";

print "<div class=\"row\" style='margin-left:0px;'>";

print "<div class=\"feature-wrap-tree-map\" style='text-align:left;'>";



//-------------------------// CATEGORY CHART //---------------------------//


print "<script type='text/javascript' src='https://www.google.com/jsapi'></script>
						    <script type='text/javascript'>
						      google.load('visualization', '1', {packages:['treemap']});
						      google.setOnLoadCallback(drawChart);
						      function drawChart() {
						        // Create and populate the data table.
						        var data = google.visualization.arrayToDataTable([
						          ['Category','Boards','Pins (size)', 'Repins (color)']
						          ,['Virality per Board by Category (click to drill down, right click to go back)','',0,0]";

//asort($categories['category']);

//asort($boards['category']);


foreach($virality_categories as $c){
    echo $c;
}

foreach($virality_boards as $b){
    echo $b;
}


print "]);




						        // Create and draw the visualization.
						        var tree = new google.visualization.TreeMap(document.getElementById('treemap_div'));

						        google.visualization.events.addListener(tree, 'onmouseover', function (e) {
                                    var provider = data.getValue(e.row, 0);
                                    // populate the tooltip with data
                                    $('#tooltip').html(provider);
                                    // show the tooltip
                                    $('#tooltip').show();
                                });
                                google.visualization.events.addListener(tree, 'onmouseout', function (e) {
                                    // hide the tooltip
                                    $('#tooltip').hide();
                                });

						        $(function () {
                                    $('#treemap_div').mousemove(function (e) {
                                        $('#tooltip').css({
                                            left: e.pageX -280,
                                            top: e.pageY - 90
                                        });
                                    });
                                });



						        tree.draw(data, {
						          minColor: '#efdfe0',
						          midColor: '#d78285',
						          maxColor: '#cc2127',
						          minHighlightColor: '#04f',
						          maxHighlightColor: '#e3c',
						          headerHeight: 30,
						          fontColor: 'black',
						          showTooltips: true,
						          showScale: false});
						        }




						    </script>";

//					print "<div class='treemap-legend-title' style='margin-top:15px'>
//                            <strong style='font-size:30px;'>Virality Score:</strong>
//                           </div>";

print
    "<div class='treemap-legend' style='margin-top:15px'>
					    <span class='tree-min-label'>0</span>
					    <div class='legend-box'>&nbsp;</div>
					    <div class='legend-box'>&nbsp;</div>
					    <div class='legend-box'>&nbsp;</div>
					    <div class='legend-box'>&nbsp;</div>
					    <span class='tree-max-label'>$max_repins_per_pin &nbsp;</span>
					</div>
					<small class='muted treemap-legend-label'>(repins/pin)</small>
					<div id='treemap_div' style='float:left; margin-bottom:15px; width: 100%; height: 300px;'></div>
					<div id='tooltip'>
                    </div>";


print "</div>";

print "</div>";

print "</div>";

print "<div class=\"row dashboard categories\" style='margin-bottom:10px;'>";

print "<div class=\"\" style='text-align:left;'>";

print "<div class=\"row\" style='margin:10px 0 10px 30px;'>";

print "
						<div class='feature-wrap-tree-map'>
							<div class='feature feature-left-half'>
								<strong>Where You Pin Most</strong>
							</div>
							<div class='feature feature-right-half'>
								<strong>Where Your Pins are Most Viral</strong>
							</div>
						</div>";

print "
						<div class='feature-wrap-tree-map'>";

print "
							<div class=\"feature feature-left\" style='text-align:center;p'>
								<h4 class='active'>Most Active<br>Category</h4>
								<div>
									<div class='feature-cat'>$most_active_cat</div>
									<div class='feature-growth cat'>
										<span><strong>$max_cat_pins</strong> Pins</span>
									</div>
								</div>
							</div>";

print "
							<div class=\"feature feature-right\" style='text-align:center;margin-right:0px;'>
								<h4 class='active'>Most Active<br>Board</h4>
								<div>
									<div class='feature-cat'>$most_active_board </div>
									<!--<div class='feature-under-stat'>($most_active_board_cat)</div>-->
									<div class='feature-growth cat'>
										<span><strong>$max_board_pins</strong> Pins</span>
									</div>
								</div>
							</div>";

print
    "<div class='comparison'><i class='icon-arrow-right'></i></div>";

print "
							<div class=\"feature feature-left\" style='text-align:center;'>
								<h4 class='viral'>Most Viral<br>Category</h4>
								<div>
									<div class='feature-cat viral'>$most_viral_cat</div>
									<div class='feature-growth cat'>
										<span><strong>$max_cat_repins_per_pin</strong> Virality Score</span>
									</div>
								</div>
							</div>";

print "
							<div class=\"feature feature-right\" style='text-align:center;margin-right:0px;'>
								<h4 class='viral'>Most Viral<br>Board</h4>
								<div>
									<div class='feature-cat viral'>$most_viral_board </div>
									<!--<div class='feature-under-stat'>($most_viral_board_cat)</div>-->
									<div class='feature-growth cat'>
										<span><strong>$max_board_repins_per_pin</strong> Virality Score</span>
									</div>
								</div>
							</div>";


print "</div>";

print "</div>";

print "</div>";

print "</div>";

print "</div>
    </div>
    </div>
</div>";

print "<div class='clearfix'></iv>";

print "<div class='accordion' id='accordion3' style='margin-bottom:25px'>
  <div class='accordion-group' style='margin-bottom:25px'>
    <div class='accordion-heading' style='height:40px'>
    	<span class='pull-left' style='height:40px'>
     		<div class='accordion-toggle' data-parent='#accordion3' href='#collapseTwo' style='cursor:default'>
      			<h2 style='color:#333;'> Engagement Score Heatmap</h2>
      		</div>
      	</span>
    </div>
    <div id='collapseTwo' class='accordion-body collapse in'>
         <div class='accordion-inner'>";

print "<div class=\"\" style=''>";

print "<div class=\"row\" style='margin-left:0px;'>";

print "<div class=\"feature-wrap-tree-map\" style='text-align:left;'>";

//-------------------------// CATEGORY CHART //---------------------------//

print "<script type='text/javascript' src='https://www.google.com/jsapi'></script>
						    <script type='text/javascript'>
						      google.load('visualization', '1', {packages:['treemap']});
						      google.setOnLoadCallback(drawChart);
						      function drawChart() {
						        // Create and populate the data table.
						        var data = google.visualization.arrayToDataTable([
						          ['Category','Boards','Followers (size)', 'Repins (color)']
						          ,['Engagement per Board by Category (click to drill down, right click to go back)','',0,0]";

//asort($categories['category']);

//asort($boards['category']);


foreach($engagement_categories as $c){
    echo $c;
}

foreach($engagement_boards as $b){
    echo $b;
}


print "]);

						        // Create and draw the visualization.
						        var tree = new google.visualization.TreeMap(document.getElementById('treemap_div2'));

						        google.visualization.events.addListener(tree, 'onmouseover', function (e) {
                                    var provider = data.getValue(e.row, 0);
                                    // populate the tooltip with data
                                    $('#tooltip2').html(provider);
                                    // show the tooltip
                                    $('#tooltip2').show();
                                });
                                google.visualization.events.addListener(tree, 'onmouseout', function (e) {
                                    // hide the tooltip
                                    $('#tooltip2').hide();
                                });

						        $(function () {
                                    $('#treemap_div2').mousemove(function (e) {
                                        $('#tooltip2').css({
                                            left: e.pageX -280,
                                            top: e.pageY -150
                                        });
                                    });
                                });


						        tree.draw(data, {
						          minColor: '#E6E0EC',
						          midColor: '#B4A2C5',
						          maxColor: '#82649E',
						          minHighlightColor: '#04f',
						          maxHighlightColor: '#e3c',
						          headerHeight: 30,
						          fontColor: 'black',
						          showScale: false});
						        }
						    </script>";

//					print "<div class='treemap-legend-title' style='margin-top:15px'>
//                            <strong style='font-size:30px;'>Engagement Score:</strong>
//                           </div>";
print
    "<div class='treemap-legend2' style='margin-top:15px'>
					    <span class='tree-min-label'>0</span>
					    <div class='legend-box'>&nbsp;</div>
					    <div class='legend-box'>&nbsp;</div>
					    <div class='legend-box'>&nbsp;</div>
					    <div class='legend-box'>&nbsp;</div>
					    <span class='tree-max-label'>$max_repins_per_pin_per_follower &nbsp;</span>
					</div>
					<small class='muted treemap-legend-label'>(repins/pin/follower)</small>
					<span class='pull-right help-icon'>
						<a data-toggle='popover' data-container='body' data-original-title='<strong>Heatmap Legend</strong>' data-content=\"<strong>Size of Category:</strong> Number of Followers. <br><strong>Color Brightness:</strong> Category's level of Audience Engagement (Repins/pin/follower). <br><br><strong>Click:</strong> to drill-down into your Boards. <br><strong>Right-Click:</strong> to zoom back out to your Categories.\" data-placement='left'><i id='header-icon' class='icon-help'></i></a>
					</span>
					<div id='treemap_div2' style='float:left; margin-bottom:15px; width: 100%; height: 300px;'></div>
					<div id='tooltip2'>
                    </div>";


print "</div>";

print "</div>";

print "</div>";

print "<div class=\"row dashboard categories\" style='margin-bottom:10px;'>";

print "<div class=\"\" style='text-align:left;'>";

print "<div class=\"row\" style='margin:10px 0 10px 30px;'>";

print "
						<div class='feature-wrap-tree-map'>
							<div class='feature feature-left-half'>
								<strong>Where You Pin Most</strong>
							</div>
							<div class='feature feature-right-half'>
								<strong>Where Your Audience is Most Successful</strong>
							</div>
						</div>";

print "
						<div class='feature-wrap-tree-map'>";

print "
							<div class=\"feature feature-left\" style='text-align:center;p'>
								<h4 class='active'>Most Active<br>Category</h4>
								<div>
									<div class='feature-cat'>$most_active_cat</div>
									<div class='feature-growth cat'>
										<span><strong>$max_cat_pins</strong> Pins</span>
									</div>
								</div>
							</div>";

print "
							<div class=\"feature feature-right\" style='text-align:center;margin-right:0px;'>
								<h4 class='active'>Most Active<br>Board</h4>
								<div>
									<div class='feature-cat'>$most_active_board </div>
									<!--<div class='feature-under-stat'>($most_active_board_cat)</div>-->
									<div class='feature-growth cat'>
										<span><strong>$max_board_pins</strong> Pins</span>
									</div>
								</div>
							</div>";

print
    "<div class='comparison'><i class='icon-arrow-right'></i></div>";

print "
							<div class=\"feature feature-left\" style='text-align:center;'>
								<h4 class='engaged'>Most Engaged<br>Category</h4>
								<div>
									<div class='feature-cat engaged'>$most_engaged_cat</div>
									<div class='feature-growth cat'>
										<span><strong>$max_cat_engagement</strong> Engagement Score</span>
									</div>
								</div>
							</div>";

print "
							<div class=\"feature feature-right\" style='text-align:center;margin-right:0px;'>
								<h4 class='engaged'>Most Engaged<br>Board</h4>
								<div>
									<div class='feature-cat engaged'>$most_engaged_board </div>
									<!--<div class='feature-under-stat'>($most_viral_board_cat)</div>-->
									<div class='feature-growth cat'>
										<span><strong>$max_board_engagement</strong> Engagement Score</span>
									</div>
								</div>
							</div>";


print "</div>";

print "</div>";

print "</div>";

print "</div>";


print "
	</div>
</div>
</div>
</div>";


print "

<script>
	//$('.feature-stat').fitText();
	$('.feature-under-stat').fitText(1.3);
</script>";


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

function formatNumber($x) {
    if (!$x) {
        return "-";
    } else {
        return number_format($x);
    }
}

function formatPercentage($x) {
    $x = $x * 100;
    if ($x >= 0) {
        return "<span style='color: green;font-weight:normal;font-size:12px'>(" . number_format($x,1) . "%)</span>";
    } else {
        return "<span style='color: #aaa;font-weight:normal;font-size:12px'>(" . number_format($x,1) . "%)</span>";
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


?>
