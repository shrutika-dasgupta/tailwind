<?php

// Build the array of JS chart data table rows.
$chart_daily_calcs = array();
foreach ($daily_counts as $datetime => $topics) {
    if ($datetime <= 0) {
        continue;
    }

    $datetime_js  = $datetime * 1000;

    $row = array("new Date($datetime_js)");
    
    // Loop through $query_args instead of daily $topics to ensure a row is created for each topic.
    foreach ($query_args as $topic) {
        $data = array_get($topics, $topic);

        $pin_count    = array_get($data, 'pin_count', 0);
        $pinner_count = array_get($data, 'pinner_count', 0);
        $reach        = array_get($data, 'reach', 0);

        $row[] = $pin_count;
        $row[] = $pinner_count;
        $row[] = $reach;
        $row[] = "createVolumeChartTooltip(\"$topic\", $datetime, $pin_count, $pinner_count, $reach, 1)";
        $row[] = "createVolumeChartTooltip(\"$topic\", $datetime, $pin_count, $pinner_count, $reach, 0)";
    }

    $chart_daily_calcs[] = "data.addRow([" . implode(', ', $row) . "]);";
}

// Feature ACL.
$volume_menu_class = '';
if (!$customer->hasFeature('listening_insights_dailyvolume_menu')) {
    $volume_menu_class = ' class="disabled"';
}
$top_sources_enabled   = $customer->hasFeature('listening_insights_topsources');
$top_pinners_allowed   = $customer->maxAllowed('listening_insights_toppinners');
$top_pinners_anonymous = ($plan->plan_id == 1) ? true : false;

?>

<?= $navigation ?>

<div class="row-fluid">
    <div class="listening-dashboard span12" id="listening-insights" >
        <div class="accordion">
            <div class="accordion-body collapse in">
                <div class="accordion-inner">
                    <script type='text/javascript' src='https://www.google.com/jsapi'></script>
                    <script type='text/javascript'>
                        google.load('visualization', '1.1', {packages: ['corechart', 'controls']});

                        google.setOnLoadCallback(drawVisualization);

                        function createVolumeChartTooltip(topic, timestamp, pins, pinners, reach, showMenu)
                        {
                            tipDate = new Date(timestamp * 1000);

                            months = [
                                "Jan", "Feb", "Mar", "Apr",
                                "May", "Jun", "Jul", "Aug",
                                "Sep", "Oct", "Nov", "Dec"
                            ];

                            month   = tipDate.getMonth();
                            day     = tipDate.getDate();
                            year    = tipDate.getFullYear();
                            tipDate = months[month] + ' ' + day + ', ' + year;

                            function commaSeparateNumber(val){
                                while (/(\d+)(\d{3})/.test(val.toString())){
                                    val = val.toString().replace(/(\d+)(\d{3})/, '$1'+','+'$2');
                                }
                                return val;
                            }

                            pins    = commaSeparateNumber(pins);
                            pinners = commaSeparateNumber(pinners);
                            reach   = commaSeparateNumber(reach);

                            optionId = topic.replace(/[^a-zA-Z0-9]/g, "-") + '-' + timestamp;

                            // Word Cloud is currently only supported for keyword topics.
                            wordCloudOption = '';
                            if (topic.indexOf('.') == -1) {
                                wordCloudOption = '' + 
                                    '<li role="presentation"<?= $volume_menu_class ?>>' +
                                        '<a href="javascript:void(0)" id="wordcloud-' + optionId + '" class="track-click" onclick="handleWordCloud(\'#wordcloud-' + optionId + '\')" tabindex="-1" data-url="<?= URL::route("discover-wordcloud-snapshot", array("")) ?>/' + encodeURIComponent(topic) + '?date=' + timestamp + '" data-topic="' + topic + '" data-date="' + tipDate + '" data-component="Daily Volume" data-element="View Word Cloud Link">' +
                                            'View Word Cloud' +
                                        '</a>' +
                                    '</li>';
                            }

                            tooltip = '' +
                                '<div class="listen-tooltip-stats">' +
                                    '<div style="padding:5px 10px;">' +
                                        '<div>' +
                                            '<u>' + topic + '</u>' +
                                        '</div>' +
                                        '<div>' +
                                            '<strong>' + tipDate + '</strong>' +
                                        '</div>' +
                                        '<div>' +
                                            'Pins: ' + pins +
                                        '</div>' +
                                        '<div>' +
                                            'Pinners: ' + pinners +
                                        '</div>' +
                                        '<div>' +
                                            'Reach: ' + reach +
                                        '</div>' +
                                    '</div>';
                            if(showMenu == 1){
                                tooltip = tooltip + '<ul role="menu" class="dropdown-menu listening-tooltip-cta">' +
                                        '<li role="presentation"<?= $volume_menu_class ?>>' +
                                            '<a href="<?= URL::route("discover-feed", array("feed", "")) ?>/' + topic + '?date=' + timestamp + '" id="top-pins-' + optionId + '" onclick="return handleTopPins(\'#top-pins-' + optionId + '\');" tabindex="-1">' +
                                                'Explore Top Pins' +
                                            '</a>' +
                                        '</li>' +
                                        '<li role="presentation"<?= $volume_menu_class ?>>' +
                                            '<a href="javascript:void(0)" id="top-pinners-' + optionId + '"class="track-click" onclick="handleTopPinners(\'#top-pinners-' + optionId + '\')" tabindex="-1" data-url="<?= URL::route("discover-top-pinners-snapshot", array("")) ?>/' + encodeURIComponent(topic) + '?date=' + timestamp + '" data-topic="' + topic + '" data-date="' + tipDate + '" data-component="Daily Volume" data-element="Meet Top Pinners Link">' +
                                                'Meet Top Pinners' +
                                            '</a>' +
                                        '</li>' +
                                        wordCloudOption +
                                    '</ul>' +
                                '</div>';
                            } else {
                                tooltip = tooltip +
                                    '<div style="padding:3px 10px;">' +
                                        '<em>Click Star to Explore...</em>' +
                                    '</div>' +
                                '</div>';
                            }

                            return tooltip;
                        }

                        var dailyVolumeDashboard;
                        var dailyVolumeChart;
                        var dailyVolumeControl;

                        function drawVisualization()
                        {

                            dailyVolumeDashboard = new google.visualization.Dashboard(document.getElementById('listening_daily_chart_div'));

                            dailyVolumeControl = new google.visualization.ControlWrapper({
                                'controlType': 'ChartRangeFilter',
                                'containerId': 'daily-volume-control',
                                'options': {
                                    // Filter by the date axis.
                                    'filterColumnIndex': 0,
                                    'animation': {
                                        'duration': 500,
                                        'easing': 'inAndOut'
                                    },
                                    'ui': {
                                        'chartType': 'LineChart',
                                        'chartOptions': {
                                            'chartArea': {'width': '70%'},
                                            'hAxis': {'baselineColor': 'none'},
                                            'series':{
                                                0:{targetAxisIndex:0, color: '#5792B3'},
                                                1:{targetAxisIndex:0, color: '#D77E81'},
                                                2:{targetAxisIndex:0, color: '#82649E'},
                                                3:{targetAxisIndex:0, color: '#FEBC58'},
                                                4:{targetAxisIndex:0, color: '#F0E86D'}
                                            },
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
                                'state': {
                                    'range': {
                                        'start': new Date(<?=$new_last_chart_date;?>),
                                        'end': new Date(<?=$new_curr_chart_date;?>)
                                    }
                                }
                            });

                            dailyVolumeChart = new google.visualization.ChartWrapper({
                                'chartType': 'AreaChart',
                                'containerId': 'daily-volume-chart',
                                'options': {
                                    // Use the same chart area width as the control for axis alignment.
                                    'chartArea': {'top':50, 'height':'70%', 'width':'70%'},
                                    'hAxis': {'slantedText': false},
                                    'legend': {'position': 'top'},
                                    'pointShape':'star',
                                    'curveType':'none',
                                    'animation': {
                                        'duration': 500,
                                        'easing': 'inAndOut'
                                    },
                                    //'focusTarget': 'category',
                                    'tooltip': {
                                        'trigger': 'selection',
                                        'isHtml': true
                                    },
                                    'crosshair': {
                                        'trigger': 'both',
                                        'orientation': 'vertical'
                                    },
                                    vAxes: {
                                        0: {logScale: false, minValue: 0, title: "Pins"}
                                    },
                                    series:{
                                        0:{targetAxisIndex:0, color: '#5792B3'},
                                        1:{targetAxisIndex:0, color: '#D77E81'},
                                        2:{targetAxisIndex:0, color: '#82649E'},
                                        3:{targetAxisIndex:0, color: '#FEBC58'},
                                        4:{targetAxisIndex:0, color: '#F0E86D'}
                                    }
                                }

                            });

                            var data = new google.visualization.DataTable();
                            
                            data.addColumn('date', 'Date');

                            <?php foreach ($query_args as $topic): ?>
                                data.addColumn('number', "<?= $topic ?>");
                                data.addColumn('number', "<?= $topic ?>");
                                data.addColumn('number', "<?= $topic ?>");
                                data.addColumn({
                                    'type':'string',
                                    'role':'tooltip',
                                    'p':{
                                        'html':true
                                    }
                                });
                                data.addColumn({
                                    'type':'string',
                                    'role':'annotationText',
                                    'p':{
                                        'html':true
                                    }
                                });
                            <?php endforeach ?>

                            <?php
                            foreach ($chart_daily_calcs as $calc_row) {
                                echo $calc_row;
                            }
                            ?>

                            // Dynamically determine the number of columns based on query.
                            <?php $column_count = 1 + count($query_args) * 3 ?>
                            //view.setColumns(Array.apply(null, {length: <?= $column_count ?>}).map(Number.call, Number));

                            //set variables for toggling chart view
                            var pinBox = document.getElementById('pins-toggle');
                            var pinnerBox = document.getElementById('pinners-toggle');
                            var reachBox = document.getElementById('reach-toggle');

                            function drawChart()
                            {

                                // Disabling the buttons while the chart is drawing.
                                pinBox.checked = false;
                                pinnerBox.checked = false;
                                reachBox.checked = false;

                                // Check and enable only relevant boxes.
                                pinBox.checked = view.getViewColumns().indexOf(1) != -1;
                                pinnerBox.checked = view.getViewColumns().indexOf(2) != -1;
                                reachBox.checked = view.getViewColumns().indexOf(3) != -1;

                                dailyVolumeDashboard.bind(dailyVolumeControl, dailyVolumeChart);
                                dailyVolumeDashboard.draw(view);
                            }


                            pinBox.onclick = function () {
                                //adding pins
                                if (pinBox.checked) {

                                    view.setColumns([
                                        <?php
                                        $columns = "0";
                                        for ($i = 0; $i < count($query_args); $i++){
                                            $set = $i*5;
                                            $columns .= "," . ($set+1) . "," . ($set+4) . "," . ($set+5);
                                        }
                                        echo $columns;
                                        ?>
                                    ]);
                                    dailyVolumeChart.setOption('series', [
                                        {'color': '#5792B3'}
                                    ]);
                                    dailyVolumeChart.setOption('areaOpacity', 0.3);
                                    dailyVolumeControl.setOption('ui', {'chartOptions': {'series': [
                                        {'color': '#5792B3'}
                                    ], 'chartArea': {'width': '70%'}}, 'chartType': 'AreaChart'});
                                    drawChart();
                                }
                            }

                            pinnerBox.onclick = function () {

                                //adding pinners
                                if (pinnerBox.checked) {

                                    view.setColumns([
                                            <?php
                                            $columns = "0";
                                            for ($i = 0; $i < count($query_args); $i++){
                                                $set = $i*5;
                                                $columns .= "," . ($set+2) . "," . ($set+4) . "," . ($set+5);
                                            }
                                            echo $columns;
                                            ?>
                                    ]);
                                    dailyVolumeChart.setOption('series', [
                                        {'color': '#40343a'}
                                    ]);
                                    dailyVolumeChart.setOption('areaOpacity', 0.3);
                                    dailyVolumeControl.setOption('ui', {'chartOptions': {'series': [
                                        {'color': '#40343a'}
                                    ], 'chartArea': {'width': '70%'}}, 'chartType': 'AreaChart'});
                                    dailyVolumeChart.draw(view);
                                    dailyVolumeControl.draw(view);
                                    drawChart();
                                }
                            }

                            reachBox.onclick = function () {
                                //adding reach
                                if (reachBox.checked) {

                                    view.setColumns([
                                            <?php
                                            $columns = "0";
                                            for ($i = 0; $i < count($query_args); $i++){
                                                $set = $i*5;
                                                $columns .= "," . ($set+3) . "," . ($set+4) . "," . ($set+5);
                                            }
                                            echo $columns;
                                            ?>
                                    ]);
                                    dailyVolumeChart.setOption('series', [
                                        {'color': '#D77E81'}
                                    ]);
                                    dailyVolumeChart.setOption('areaOpacity', 0.3);
                                    dailyVolumeControl.setOption('ui', {'chartOptions': {'series': [
                                        {'color': '#D77E81'}
                                    ], 'chartArea': {'width': '70%'}}, 'chartType': 'AreaChart'});
                                    drawChart();

                                }
                            }


                           /**
                            * Set the initial view for pin counts
                            */
                            var view = new google.visualization.DataView(data);
                            view.setColumns([
                                    <?php
                                    $columns = "0";
                                    for ($i = 0; $i < count($query_args); $i++){
                                        $set = $i*5;
                                        $columns .= "," . ($set+1) . "," . ($set+4) . "," . ($set+5);
                                    }
                                    echo $columns;
                                    ?>
                            ]);
                            drawChart();

                            var selectionToggle = 0;

                            google.visualization.events.addListener(dailyVolumeChart, 'ready', function () {

                                google.visualization.events.addListener(dailyVolumeChart.getChart(), 'select', function (e) {
                                    var selection = dailyVolumeChart.getChart().getSelection();
                                    var message = '';
                                    for (var i = 0; i < selection.length; i++) {
                                        var item = selection[i];
                                        if (item.row != null && item.column != null) {
                                            selectionToggle = 1;
                                            $('#domain-tooltip').hide();
                                            message = 'yes';
                                        }
                                    }
                                    if (message == '') {
                                        selectionToggle = 0;
                                        $('#domain-tooltip').show();
                                    }
                                });

                                google.visualization.events.addListener(dailyVolumeChart.getChart(), 'onmouseover', function (e) {

                                    //Have to check to see if row returns null, which is the case
                                    //when you hover over the legend (trying to get its value when
                                    //null throws an error and we want to avoid this).
                                    if(e.row !== null){

                                        //we use getDataTable() to get the table of data that is
                                        //currently in view (this accounts for any changes the user
                                        //makes using the Range Filter scrubbers).
                                        var tmpData = dailyVolumeChart.getDataTable();
                                        var sel = tmpData.getValue(e.row, e.column+2);
                                        // populate the tooltip with data
                                        $('#domain-tooltip').html(sel);
                                        // show the tooltip
                                        if(selectionToggle == 0){
                                            $('#domain-tooltip').show();
                                        }

                                        // if you've already clicked on a data point (i.e. made
                                        // a selection to trigger the context menu),
                                        // then the following will allow you to
                                        // simply click on a new data point and trigger the
                                        // the context menu there, without having to click on
                                        // the original data point again first to de-select
                                        // the original selection that you made.
                                        if(selectionToggle == 1){
                                            google.visualization.events.trigger(dailyVolumeChart.getChart(), 'select', {});
                                            //$('#tooltip').show();
                                        }

                                    }

                                });

                                google.visualization.events.addListener(dailyVolumeChart.getChart(), 'onmouseout', function (e) {
                                    // hide the tooltip
                                    $('#domain-tooltip').hide();
                                });

                                $(document).ready(function () {
                                    $('#daily-volume-chart').mousemove(function (e) {

                                        var top = $('#main-content-scroll').scrollTop();

                                        $('#domain-tooltip').css({
                                            left: e.pageX -275,
                                            top: e.pageY - 290 + top
                                        });
                                    });
                                });
                            });



                            // Prepare Top Sources data.
                            <?php if ($top_sources_enabled && !empty($sources)): ?>
                                <?php
                                    if (array_get($keywords, 0) == 'all-keywords') {
                                        $top_sources_data = array(array('Domain', 'Keyword Mentions'));
                                        foreach ($sources as $i => $source) {
                                            $top_sources_data[] = array(
                                                $source->domain,
                                                (int) $source->keyword_mentions
                                            );
                                        }
                                    } else {
                                        $labels = array_merge(array('Keywords'), $keywords);

                                        $top_sources_data = array($labels);
                                        foreach ($sources as $domain => $source_data) {
                                            $data = array($domain);
                                            foreach ($source_data as $keyword => $mentions) {
                                                $data[] = $mentions;
                                            }

                                            $top_sources_data[] = $data;
                                        }
                                    }
                                ?>

                                var topSourcesChartData = google.visualization.arrayToDataTable(<?= json_encode($top_sources_data) ?>);

                                // Create and draw Top Sources chart.
                                var topSourcesChart = new google.visualization.BarChart(document.getElementById('top-sources'));
                                topSourcesChart.draw(
                                    topSourcesChartData,
                                    <?php if (array_get($keywords, 0) == 'all-keywords'): ?>
                                    {
                                        chartArea : {left:150},
                                        fontSize  : 13,
                                        height    : 300,
                                        legend    : {position:"none"}
                                    }
                                    <?php else: ?>
                                    {
                                        chartArea : {left:150},
                                        fontSize  : 13,
                                        height    : 300,
                                        legend    : {position:"top"},
                                        colors    : ['#5792B3', '#D77E81', '#82649E', '#FEBC58', '#F0E86D'],
                                        isStacked : true
                                    }
                                    <?php endif ?>
                                );
                            <?php endif ?>
                        }

                        function handleTopPins(selector)
                        {
                            if ($(selector).parent().hasClass('disabled')) {
                                return false;
                            }

                            return true;
                        }

                        function handleTopPinners(selector)
                        {
                            element = $(selector);
                            if (element.parent().hasClass('disabled')) {
                                return false;
                            }

                            $('#volumeSnapshotModalLabel').html('Top Pinners - ' + element.data('topic') + ' - ' + element.data('date'))
                            $('#volumeSnapshotModal').modal('show');
                            $('#volumeSnapshotModal .modal-body').attr('class', 'modal-body').html('Loading Top Pinners...');

                            $.ajax({
                                type: 'GET',
                                url: element.data('url'),
                                dataType: 'html',
                                success: function (html) {
                                    $('#volumeSnapshotModal .modal-body').html(html);

                                    // Render dynamically-generated social buttons.
                                    twttr.widgets.load();
                                    $.ajax({url:'http://assets.pinterest.com/js/pinit.js', dataType:'script', cache:true});
                                }
                            });
                        }

                        function handleWordCloud(selector)
                        {
                            element = $(selector);
                            if (element.parent().hasClass('disabled')) {
                                return false;
                            }

                            $('#volumeSnapshotModalLabel').html('Word Cloud - ' + element.data('topic') + ' - ' + element.data('date'))
                            $('#volumeSnapshotModal').modal('show');
                            $('#volumeSnapshotModal .modal-body').attr('class', 'modal-body').html('Loading Word Cloud...');

                            $.ajax({
                                type: 'GET',
                                url: element.data('url'),
                                dataType: 'json',
                                success: function (data) {
                                    if (data.length == 0) {
                                        $('#volumeSnapshotModal .modal-body').html('No Word Cloud Available')
                                        return;
                                    }

                                    $('#volumeSnapshotModal .modal-body').html('').jQCloud(
                                        data,
                                        {'width':560, 'height':300, 'delayedMode':true, 'removeOverflowing':false}
                                    );
                                }
                            });
                        }
                    </script>

                    <?php
                        $date_range = Input::get('date', 'week');
                        $date_range_label = '';
                        
                        if ($date_range == 'week') {
                            $date_range_label = '(Last 7 Days)';
                        } else if ($date_range == '2weeks') {
                            $date_range_label = '(Last 14 Days)';
                        } else if ($date_range == 'month') {
                            $date_range_label = '(Last 30 Days)';
                        } else if ($date_range == 'alltime') {
                            $date_range_label = '(All-Time)';
                        }
                    ?>

                    <div class="module">
                        <span class="title">
                            Daily Volume for "<?= implode('", "', $query_args) ?>"
                            <span class="muted"><?= $date_range_label ?></span>
                        </span>
                        <div id="listening_daily_chart_div">
                            <div id='daily-volume-chart' style='width: 100%; height: 230px;'></div>
                            <div id='daily-volume-control' style='width: 100%; height: 45px;'></div>
                        </div>
                        <div class="dashboard">
                            <div class="feature-wrap-chart-controls" style='text-align:left;position: absolute; left:-99999px;'>
                                <form>
                                    <input id='pins-toggle' type='radio' value='pins-toggle'> Pins </input>
                                    <input id='pinners-toggle' type='radio' value='pinners-toggle'> Pinners </input>
                                    <input id='reach-toggle' type='radio' value='reach-toggle'> Reach </input>
                                </form>
                            </div>
                        </div>
                        <div id='domain-tooltip' style="font-size:12px;"></div>
                    </div>

                    <?php if (!empty($sources)): ?>
                        <div class="module module-sources">
                            <span class="title">
                                Top Sources for "<?= implode('", "', $keywords) ?>"
                                <span class="muted"><?= $date_range_label ?></span>
                            </span>
                            <?php if ($top_sources_enabled): ?>
                                <div id="top-sources"></div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <a href="/upgrade">Upgrade your account</a> to view top sources.
                                </div>
                            <?php endif ?>
                        </div>
                    <?php endif ?>

                    <?php if (!empty($influencers)): ?>
                        <div class="module module-influencers">
                            <span class="title">
                                Top Pinners for "<?= implode('", "', $query_args) ?>"
                                <span class="muted"><?= $date_range_label ?></span>
                            </span>
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Pinner</th>
                                        <th>Topic Mentions</th>
                                        <th>
                                            Topic Affinity
                                            <i class          = "icon-help"
                                               data-toggle    = "popover"
                                               data-placement = "top"
                                               data-container = "body"
                                               data-content   = "The percentage of the pinner's total pins that match the topic."
                                            ></i>
                                        </th>
                                        <th class="sorting_desc">Followers</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($influencers as $i => $influencer): ?>
                                        <tr<?= ($i >= 5) ? ' class="row-togglable hidden"' : '' ?>>
                                            <td class="rank"><?= $i+1 ?></td>
                                            <td>
                                                <?= View::make('analytics.pages.discover.profile', array('profile' => $influencer, 'component' => 'Top Pinners', 'anonymous' => $top_pinners_anonymous)) ?>
                                            </td>
                                            <td class="mentions">
                                                <?= number_format($influencer->mentions_count) ?>
                                                <?php $label_type = !empty($influencer->keyword) ? 'label-info' : 'label-success' ?>
                                                <span class="label <?= $label_type ?> pull-right">
                                                    <?= $influencer->topic ?>
                                                </span>
                                            </td>
                                            <td class="affinity">
                                                <?= number_format($influencer->mentions_count / $influencer->pin_count * 100, 2) ?>%
                                            </td>
                                            <td class="followers"><?= number_format($influencer->follower_count) ?></td>
                                        </tr>
                                        <?php if ($i+1 >= $top_pinners_allowed) break ?>
                                    <?php endforeach ?>
                                    <?php if (count($influencers) > 5): ?>
                                        <tr class="row-toggle">
                                            <td colspan="5">
                                                <?php if ($plan->plan_id == 1): ?>
                                                    <a href="/upgrade">Upgrade to View Pinner Profiles & More Top Pinners</a>
                                                <?php elseif ($top_pinners_allowed == 5): ?>
                                                    <a href="/upgrade">Upgrade to View More Top Pinners</a>
                                                <?php else: ?>
                                                    <a href="javascript:void(0)" id="influencers-row-toggle" class="track-click" data-component="Top Pinners" data-element="Show More Link">
                                                        Show More Top Pinners
                                                    </a>
                                                <?php endif ?>
                                            </td>
                                        </tr>
                                    <?php endif ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="volumeSnapshotModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="volumeSnapshotModalLabel" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="volumeSnapshotModalLabel">Word Cloud</h3>
    </div>
    <div class="modal-body wordcloud-wrapper"></div>
</div>


<script type="text/javascript">
$(function () {
    $('#influencers-row-toggle').on('click', function () {
        $('.module-influencers .row-togglable').show();
        $(this).parents('tr').hide();
    });
});
</script>
