<?php

// Build the array of JS chart data table rows.
$chart_daily_calcs = array();
$total_pins = 0;
$total_pinners = 0;
$total_reach = 0;
$total_days = 0;
$period_pins = 0;
$period_pinners = 0;
$period_reach = 0;
$prediction_certainty = "false";
$show_analytics = $metric_extras['show_analytics'];
$impressions_enabled = ($customer->hasFeature('domain_impressions_history') ? true : false);

foreach ($daily_counts as $datetime => $domains) {
    if ($day_range != 0 && $datetime < $max_data_age) {
        continue;
    }

    if ($datetime <= 0) {
        continue;
    }

    $datetime_js = $datetime * 1000;

    $row = array("new Date($datetime_js)");

    $total_days++;

    // Loop through $query_args instead of daily $domains to ensure a row is created for each topic.
    foreach ($query_args as $domain) {
        $data = array_get($domains, $domain);

        $pin_count    = array_get($data, 'pin_count', 0);
        $pinner_count = array_get($data, 'pinner_count', 0);
        $reach        = array_get($data, 'reach', 0);
        ($show_analytics ? $visits = array_get($data, 'visits', 0) : $visits = 'false');

        /*
         * Calculate the total pins in the actual date range period the user has selected,
         * so we can show total volume over that time.
         */
        if ($datetime >= $last_date && $datetime <= $current_date) {
            $period_pins    += $pin_count;
            $period_pinners += $pinner_count;
            $period_reach   += $reach;
            ($show_analytics ? $period_visits += $visits : $period_visits = false);
        }

        /*
         * Calculate the total pins across full allowed period for a given user.
         * E.g. Lite plan has 90 days of historical data.  This will sum values across that range.
         */
        $total_pins    += $pin_count;
        $total_pinners += $pinner_count;
        $total_reach   += $reach;
        ($show_analytics ? $total_visits += $visits : $total_visits = false);


        /*
         * Identify Spikes in activity for each metric so we can call them out
         */
        $spike_tooltip = "null";
        if ($pin_count > $metric_extras[$domain]['pin_count']['threshold'] && $metric_extras[$domain]['pin_count']['mean'] > 1){
            $pin_mean = $metric_extras[$domain]['pin_count']['mean'];
            $spike_tooltip = "createVolumeChartTooltip(\"$domain\", $datetime, $pin_count, $pinner_count, $reach, 1)";
            $pin_spike_hover_tooltip = "createVolumeChartSpikeTooltip(\"$domain\", $datetime, \"Pins\", $pin_count, $pin_mean)";
            $pin_spike = $metric_extras[$domain]['pin_count']['max'] * 1.2;
        } else {
            $pin_spike = "null";
            $pin_spike_hover_tooltip = "null";
        }
        if ($pinner_count > $metric_extras[$domain]['pinner_count']['threshold'] && $metric_extras[$domain]['pinner_count']['mean'] > 1){
            $pinner_mean = $metric_extras[$domain]['pinner_count']['mean'];
            $spike_tooltip = "createVolumeChartTooltip(\"$domain\", $datetime, $pin_count, $pinner_count, $reach, 1)";
            $pinner_spike_hover_tooltip = "createVolumeChartSpikeTooltip(\"$domain\", $datetime, \"Pinners\", $pinner_count, $pinner_mean)";
            $pinner_spike = $metric_extras[$domain]['pinner_count']['max'] * 1.2;
        } else {
            $pinner_spike = "null";
            $pinner_spike_hover_tooltip = "null";
        }
        if ($reach > $metric_extras[$domain]['reach']['threshold'] && $metric_extras[$domain]['reach']['mean'] > 5){
            $reach_mean = $metric_extras[$domain]['reach']['mean'];
            $spike_tooltip = "createVolumeChartTooltip(\"$domain\", $datetime, $pin_count, $pinner_count, $reach, 1)";
            $reach_spike_hover_tooltip = "createVolumeChartSpikeTooltip(\"$domain\", $datetime, \"Potential Impressions\", $reach, $reach_mean)";
            $reach_spike = $metric_extras[$domain]['reach']['max'];
        } else {
            $reach_spike = "null";
            $reach_spike_hover_tooltip = "null";
        }
        if ($show_analytics) {
            if ($visits > $metric_extras[$domain]['visits']['threshold'] && $metric_extras[$domain]['visits']['mean'] > 5){
                $visits_mean = $metric_extras[$domain]['visits']['mean'];
                $spike_tooltip = "createVolumeChartTooltip(\"$domain\", $datetime, $pin_count, $pinner_count, $visits, 1)";
                $visits_spike_hover_tooltip = "createVolumeChartSpikeTooltip(\"$domain\", $datetime, \"Visits\", $visits, $visits_mean)";
                $visits_spike = $metric_extras[$domain]['visits']['max'];
            } else {
                $visits_spike = "null";
                $visits_spike_hover_tooltip = "null";
            }
        }

        /*
         * Set the predicted value for today.
         */
        if ($datetime == $current_date) {
            $pin_prediction_value    = $metric_extras[$domain]['pin_prediction'];
            $pinner_prediction_value = $metric_extras[$domain]['pinner_prediction'];
            $reach_prediction_value  = $metric_extras[$domain]['reach_prediction'];
            ($show_analytics ? $visit_prediction_value = $metric_extras[$domain]['visit_prediction'] : $visit_prediction_value = false);
        } else if ($datetime == strtotime("-1 day", $current_date)) {
            $pin_prediction_value    = $pin_count;
            $pinner_prediction_value = $pinner_count;
            $reach_prediction_value  = $reach;
            ($show_analytics ? $visit_prediction_value = $visits : $visit_prediction_value = false);
        } else {
            $pin_prediction_value    = "null";
            $pinner_prediction_value = "null";
            $reach_prediction_value  = "null";
            ($show_analytics ? $visit_prediction_value = "null" : $visit_prediction_value = false);
        }

        /*
         * Create values for each ROW of the google chart data table.
         *
         * 18 rows for each
         */

        /**
         * Prediction Values and meta
         */
        $row[] = $pin_prediction_value;
        $row[] = $prediction_certainty;

        if (!$show_analytics) {
            $row[] = $pinner_prediction_value;
            $row[] = $prediction_certainty;
        }

        $row[] = ($impressions_enabled ? $reach_prediction_value : 'null');
        $row[] = $prediction_certainty;

        if ($show_analytics) {
            $row[] = $visit_prediction_value;
            $row[] = $prediction_certainty;
        }

        /**
         * Daily data
         */
        $row[] = $pin_count;
        if (!$show_analytics) {
            $row[] = $pinner_count;
        }
        $row[] = ($impressions_enabled ? $reach : 0);
        if ($show_analytics) {
            $row[] = $visits;
        }

        /**
         * Data tooltips
         * (1) on click, w/ context menu
         * (2) on hover w/ out context menu
         */
        $row[] = "createVolumeChartTooltip(\"$domain\", $datetime, $pin_count, $pinner_count, $reach, 1, $visits)";
        $row[] = "createVolumeChartTooltip(\"$domain\", $datetime, $pin_count, $pinner_count, $reach, 0, $visits)";

        $row[] = $pin_spike;
        if (!$show_analytics) {
            $row[] = $pinner_spike;
        }
        $row[] = ($impressions_enabled ? $reach_spike : 'null');
        if ($show_analytics) {
            $row[] = $visits_spike;
        }

        $row[] = $spike_tooltip;
        $row[] = $pin_spike_hover_tooltip;
        if (!$show_analytics) {
            $row[] = $pinner_spike_hover_tooltip;
        }
        $row[] = ($impressions_enabled ? $reach_spike_hover_tooltip : 'null');
        if ($show_analytics) {
            $row[] = $visits_spike_hover_tooltip;
        }
    }

    $chart_daily_calcs[] = "
    data.addRow([" . implode(', ', $row) . "]);";
}

// Feature ACL.
$volume_menu_class = '';
$volume_menu_icon  = '';
$volume_images_menu_class = '';
$volume_images_menu_icon  = '';
if (!$customer->hasFeature('domain_insights_dailyvolume_menu')) {
    $volume_menu_class = ' class="disabled"';
    $volume_menu_icon  = '<i class="icon-lock"></i>';
}
if (!$customer->hasFeature('domain_custom_date_range')) {
    $volume_images_menu_class = ' class="disabled"';
    $volume_images_menu_icon  = '<i class="icon-lock"></i>';
}


$top_sources_enabled     = $customer->hasFeature('domain_insights_topsources');
$trending_images_enabled = $customer->hasFeature('domain_trending_images');
$top_pinners_allowed     = $customer->maxAllowed('domain_insights_toppinners');
$top_pinners_anonymous   = ($plan->plan_id == 1) ? true : false;

$avg_pins    = formatAbsoluteAverage(number_format($period_pins / $day_spread,0,".",""));
$avg_pinners = formatAbsoluteAverage(number_format($period_pinners / $day_spread,0,".",""));
$avg_reach   = formatAbsoluteAverage(number_format($period_reach / $day_spread,0,".",""));

($show_analytics ? $avg_visits = formatAbsoluteAverage(number_format($period_visits / $day_spread,0,".","")) : $avg_visits = false);

?>

<?= $navigation ?>

<div class="row-fluid">
<div class="listening-dashboard span12" id="listening-insights">
<div class="accordion">
<div class="accordion-body collapse in">
<div class="accordion-inner">
<script type='text/javascript' src='https://www.google.com/jsapi'></script>
<script type='text/javascript'>
    google.load('visualization', '1', {packages: ['corechart', 'controls']});

    google.setOnLoadCallback(drawVisualization);

    /*
    |--------------------------------------------------------------------------
    | Tooltip Helper Functions - create the HTML found inside tooltips
    |--------------------------------------------------------------------------
    */

    function createVolumeChartTooltip(topic, timestamp, pins, pinners, reach, showMenu, visits) {
        tipDate = new Date(timestamp * 1000);
        var startDay = moment(timestamp, 'X');
        var endDay   = moment(timestamp, 'X').add('days',1);

        months = [
            "Jan", "Feb", "Mar", "Apr",
            "May", "Jun", "Jul", "Aug",
            "Sep", "Oct", "Nov", "Dec"
        ];

        month = tipDate.getMonth();
        day = tipDate.getDate();
        year = tipDate.getFullYear();
        tipDate = months[month] + ' ' + day + ', ' + year;

        function commaSeparateNumber(val) {
            while (/(\d+)(\d{3})/.test(val.toString())) {
                val = val.toString().replace(/(\d+)(\d{3})/, '$1' + ',' + '$2');
            }
            return val;
        }

        pins = commaSeparateNumber(pins);
        pinners = commaSeparateNumber(pinners);
        reach = commaSeparateNumber(reach);

        /**
         * Check if visits are available in this tooltip, and handle accordingly.
         */
        visits = (typeof visits === "undefined") ? 0 : visits;
        if(visits !== false){
            visits = commaSeparateNumber(visits);
        }

        optionId = topic.replace(/[^a-zA-Z0-9]/g, "-") + '-' + timestamp;

        // Word Cloud is currently only supported for keyword topics.
        wordCloudOption = '';
        if (topic.indexOf('.') == -1) {
            wordCloudOption = '' +
                '<li role="presentation"<?= $volume_menu_class ?>>' +
                '<a href="javascript:void(0)" id="wordcloud-' + optionId + '" class="track-click" onclick="handleWordCloud(\'#wordcloud-' + optionId + '\')" tabindex="-1" data-url="<?= URL::route("domain-wordcloud-snapshot", array("")) ?>/' + encodeURIComponent(topic) + '?date=' + timestamp + '" data-topic="' + topic + '" data-date="' + tipDate + '" data-component="Daily Volume" data-element="View Word Cloud Link">' +
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
                    '</div>';
        if (visits !== false) {
            tooltip = tooltip +
                    '<div>' +
                        'Visits: ' + visits +
                    '</div>';
        }
        tooltip = tooltip +
                '</div>';
        if (showMenu == 1) {
            tooltip = tooltip +
                '<ul role="menu" class="dropdown-menu listening-tooltip-cta">' +
                    '<li role="presentation"<?= $volume_images_menu_class ?>>' +
                        '<a href="<?= URL::route("domain-trending-images-default") ?>/' + topic + '/' + startDay.format('MM-DD-YYYY') + '/' + endDay.format('MM-DD-YYYY') + '" id="top-pins-' + optionId + '" onclick="return handleTopPins(\'#top-pins-' + optionId + '\');" tabindex="-1">' +
                            'Explore Top Images &nbsp;<?= $volume_images_menu_icon ?>' +
                        '</a>' +
                    '</li>' +
                    '<li role="presentation"<?= $volume_menu_class ?>>' +
                        '<a href="<?= URL::route("domain-feed", array("feed", "")) ?>/' + topic + '?date=' + timestamp + '" id="top-pins-' + optionId + '" onclick="return handleTopPins(\'#top-pins-' + optionId + '\');" tabindex="-1">' +
                            'Most Repinned Pins &nbsp;<?= $volume_menu_icon ?>' +
                        '</a>' +
                    '</li>' +
                    '<li role="presentation"<?= $volume_menu_class ?>>' +
                        '<a href="javascript:void(0)" id="top-pinners-' + optionId + '"class="track-click" onclick="handleTopPinners(\'#top-pinners-' + optionId + '\')" tabindex="-1" data-url="<?= URL::route("domain-top-pinners-snapshot", array("")) ?>/' + encodeURIComponent(topic) + '?date=' + timestamp + '" data-topic="' + topic + '" data-date="' + tipDate + '" data-component="Daily Volume" data-element="Meet Top Pinners Link">' +
                            'Meet Top Pinners &nbsp;<?= $volume_menu_icon ?>' +
                        '</a>' +
                    '</li>' +
                wordCloudOption +
                '</ul>' +
                '</div>';
        } else {
            tooltip = tooltip +
                '<div style="padding:3px 10px;">' +
                '<em>Click <span class="tooltip-star"><i class="icon-star"></i></span> to Explore...</em>' +
                '</div>' +
                '</div>';
        }

        return tooltip;
    }

    function createVolumeChartSpikeTooltip(topic, timestamp, type, spikeAmount, mean) {
        tipDate = new Date(timestamp * 1000);

        months = [
            "Jan", "Feb", "Mar", "Apr",
            "May", "Jun", "Jul", "Aug",
            "Sep", "Oct", "Nov", "Dec"
        ];

        month = tipDate.getMonth();
        day = tipDate.getDate();
        year = tipDate.getFullYear();
        tipDate = months[month] + ' ' + day + ', ' + year;

        function commaSeparateNumber(val) {
            while (/(\d+)(\d{3})/.test(val.toString())) {
                val = val.toString().replace(/(\d+)(\d{3})/, '$1' + ',' + '$2');
            }
            return val;
        }

        var spikeOverage = spikeAmount - mean;
        var spikePercentOverage = spikeOverage / mean * 100;

        mean = commaSeparateNumber(mean.toFixed(1));
        spikeAmount = commaSeparateNumber(spikeAmount);
        spikeOverage = commaSeparateNumber(spikeOverage);
        spikePercentOverage = spikePercentOverage.toFixed(1) + '%';

        optionId = topic.replace(/[^a-zA-Z0-9]/g, "-") + '-' + timestamp;

        // Word Cloud is currently only supported for keyword topics.
        wordCloudOption = '';
        if (topic.indexOf('.') == -1) {
            wordCloudOption = '' +
                '<li role="presentation"<?= $volume_menu_class ?>>' +
                '<a href="javascript:void(0)" id="wordcloud-' + optionId + '" class="track-click" onclick="handleWordCloud(\'#wordcloud-' + optionId + '\')" tabindex="-1" data-url="<?= URL::route("domain-wordcloud-snapshot", array("")) ?>/' + encodeURIComponent(topic) + '?date=' + timestamp + '" data-topic="' + topic + '" data-date="' + tipDate + '" data-component="Daily Volume" data-element="View Word Cloud Link">' +
                'View Word Cloud' +
                '</a>' +
                '</li>';
        }

        tooltip = '' +
            '<div class="listen-tooltip-stats">' +
                '<div class="spike-alert-header">' +
                    '<strong>Spike Alert!</strong> (' + tipDate  + ')' +
                '</div>' +
                '<div style="padding:5px 10px;">';

                tooltip = tooltip +
                    '<div class="spike-alert-value">' +
                        '<span>' + spikeAmount + ' ' + type + '!</span>' +
                    '</div>' +

                    '<div>' +
                        '<span class="spike-alert-change"><i class="icon-arrow-up"></i>' + spikePercentOverage + '</span>' +
                        'vs. Avg. (' + mean + ')' +
                    '</div>' +
                '</div>';

        tooltip = tooltip +
                '<hr style="margin: 3px 0px;">' +
                '<div style="padding:3px 10px;">' +
                    '<em>Click <span class="spike-dot">●</span> to Explore...</em>' +
                '</div>' +
            '</div>';

        return tooltip;
    }


    /*
    |--------------------------------------------------------------------------
    | INITIALIZE CHART CREATION
    |--------------------------------------------------------------------------
    */

    var dailyVolumeDashboard;
    var dailyVolumeChart;
    var dailyVolumeControl;

    function drawVisualization() {
        dailyVolumeDashboard = new google.visualization.Dashboard(document.getElementById('domain-daily-chart-div'));

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
                    'chartType': 'AreaChart',
                    'chartOptions': {
                        'chartArea': {'width': '85%', 'left': '10%'},
                        'hAxis': {'baselineColor': 'none'},
                        'series': {
                            0: {targetAxisIndex: 0, color: '#555555', areaOpacity: 0, visibleInLegend: false}, //predictions
                            1: {targetAxisIndex: 0, color: '#D77E81'},
                            2: {targetAxisIndex: 0, color: '#62C462', visibleInLegend: false, areaOpacity: 0, pointShape: 'circle', pointSize: 1}, // spikes
                            3: {targetAxisIndex: 0, color: '#FEBC58'},
                            4: {targetAxisIndex: 0, color: '#F0E86D'}
                        },
                        'animation': {
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
                'chartArea': {'top': 50, 'height': '70%', 'width': '85%', 'left': '10%', 'right': '0%'},
                'hAxis': {'slantedText': false},
                'vAxis': {'viewWindowMode':'maximized'},
                'legend': {'position': 'top'},
                'pointShape': 'star',
                'curveType': 'none',
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
                    'orientation': 'vertical',
                    'opacity': 0.2,
                    'color': '#555555',
                    'selected': { 'color': '#555', 'opacity': 0.9 }
                },
                vAxes: {
                    0: {logScale: false, minValue: 0, title: "Pins"}
                },
                series: {
                    0: {targetAxisIndex: 0, color: '#555555', areaOpacity: 0, visibleInLegend: false}, //predictions
                    1: {targetAxisIndex: 0, color: '#D77E81'},
                    2: {targetAxisIndex: 0, color: '#C7E4C1', visibleInLegend: false, pointSize: 10, pointShape: 'circle', lineWidth: 0, areaOpacity: 0}, // spikes
                    3: {targetAxisIndex: 0, color: '#FEBC58', areaOpacity: 0},
                    4: {targetAxisIndex: 0, color: '#F0E86D'}
                },
                explorer: {
                    axis: 'horizontal',
                    actions: ['dragToZoom', 'rightClickToReset'],
                    keepInBounds: true
                }
            }
        });


        /**
         * Create the chart DataTable and initialize each of the columns
         */
        var data = new google.visualization.DataTable();

        data.addColumn({'type':'date', 'role':'domain', 'label':'Date'});

        <?php foreach ($query_args as $topic): ?>
            /**
             * Prediction columns
             */
            data.addColumn('number', "Prediction for Today");
            data.addColumn({type:'boolean',role:'certainty'});
            data.addColumn('number', "Prediction for Today");
            data.addColumn({type:'boolean',role:'certainty'});
            data.addColumn('number', "Prediction for Today");
            data.addColumn({type:'boolean',role:'certainty'});


            /**
             * Actual Data columns
             */
            data.addColumn('number', "<?= $topic ?>");
            data.addColumn('number', "<?= $topic ?>");
            data.addColumn('number', "<?= $topic ?>");

            /**
             * Tooltip columns
             *  (1) shows on click w/ context menu
             *  (2) shows on hover w/ out context menu
             */
            data.addColumn({
                'type': 'string',
                'role': 'tooltip',
                'p': {
                    'html': true
                }
            });
            data.addColumn({
                'type': 'string',
                'role': 'annotationText',
                'p': {
                    'html': true
                }
            });

            /**
             * Spike Alert Columns
             */
            data.addColumn('number', "<?= $topic ?> - Spike Alert!");
            data.addColumn('number', "<?= $topic ?> - Spike Alert!");
            data.addColumn('number', "<?= $topic ?> - Spike Alert!");

            /**
             * Spike Alert Tooltips
             *  (1) shows on click w/ context menu
             *  TODO: (2) shows on hover w/ out context menu
             */
            data.addColumn({
                'type': 'string',
                'role': 'tooltip',
                'p': {
                    'html': true
                }
            });
            data.addColumn({
                'type': 'string',
                'role': 'annotationText',
                'p': {
                    'html': true
                }
            });
            data.addColumn({
                'type': 'string',
                'role': 'annotationText',
                'p': {
                    'html': true
                }
            });
            data.addColumn({
                'type': 'string',
                'role': 'annotationText',
                'p': {
                    'html': true
                }
            });
        <?php endforeach ?>

        <?php
        foreach ($chart_daily_calcs as $calc_row) {
            echo $calc_row;
        }
        ?>

        // Dynamically determine the number of columns based on query.
<!--        --><?php //$column_count = 1 + count($query_args) * 3 ?>
        //view.setColumns(Array.apply(null, {length:
    <!--    --><?//= $column_count ?><!--}).map(Number.call, Number));-->

        /**
         * Set variables for toggling the chart view
         */
        var pinBox = document.getElementById('pins-toggle');
        var pinnerBox = document.getElementById('pinners-toggle');
        var reachBox = document.getElementById('reach-toggle');

        function drawChart() {
            // Disabling the buttons while the chart is drawing.
            pinBox.checked = false;
            pinnerBox.checked = false;
            reachBox.checked = false;

            // Check and enable only relevant boxes.
            pinBox.checked = view.getViewColumns().indexOf(1) != -1;
            pinnerBox.checked = view.getViewColumns().indexOf(5) != -1;
            reachBox.checked = view.getViewColumns().indexOf(9) != -1;

            dailyVolumeDashboard.bind(dailyVolumeControl, dailyVolumeChart);
            dailyVolumeDashboard.draw(view);
        }

        function resize() {
            dailyVolumeDashboard.bind(dailyVolumeControl, dailyVolumeChart);
            dailyVolumeDashboard.draw(view);
        }

        /**
         * Define the actions that happen upon toggling the chart view for each type of chart
         */
        pinBox.onclick = function () {
            //adding pins
            if (pinBox.checked) {
                view.setColumns([
                    <?php
                    $columns = "0";
                    for ($i = 0; $i < count($query_args); $i++){
                        $set = $i*18;
                        $columns .= "," . ($set+1) . "," . ($set+2) . "," . ($set+7) . "," . ($set+10) . "," . ($set+11) . "," . ($set+12) . "," . ($set+15) . "," . ($set+16);
                    }
                    echo $columns;
                    ?>
                ]);
                dailyVolumeChart.setOption('areaOpacity', 0.3);
                dailyVolumeChart.setOption('series', {
                    0: {targetAxisIndex: 0, color: '#555555', areaOpacity: 0, visibleInLegend: false}, //predictions
                    1: {targetAxisIndex: 0, color: '#D77E81'},
                    2: {targetAxisIndex: 0, color: '#C7E4C1', visibleInLegend: false, pointSize: 10, pointShape: 'circle', lineWidth: 0, areaOpacity: 0}, // spikes
                    3: {targetAxisIndex: 0, color: '#FEBC58', areaOpacity: 0},
                    4: {targetAxisIndex: 0, color: '#F0E86D'}
                });
                dailyVolumeChart.setOption('vAxes', {
                    0: {
                        logScale: false, minValue: 0, title: "Pins"
                    }
                });
                dailyVolumeControl.setOption('ui',{
                    'chartOptions': {
                        series: {
                            0: {targetAxisIndex: 0, color: '#555555', areaOpacity: 0, visibleInLegend: false}, //predictions
                            1: {targetAxisIndex: 0, color: '#D77E81'},
                            2: {targetAxisIndex: 0, color: '#62C462', visibleInLegend: false, areaOpacity: 0}, // spikes
                            3: {targetAxisIndex: 0, color: '#FEBC58', areaOpacity: 0},
                            4: {targetAxisIndex: 0, color: '#F0E86D'}
                        },
                        'chartArea': {
                            'width': '85%', 'left': '10%'
                        }
                    },
                    'chartType': 'AreaChart'
                });
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
                        $set = $i*18;
                        $columns .= "," . ($set+3) . "," . ($set+4) . "," . ($set+8) . "," . ($set+10) . "," . ($set+11) . "," . ($set+13) . "," . ($set+15) . "," . ($set+17);
                    }
                    echo $columns;
                    ?>
                ]);
                dailyVolumeChart.setOption('areaOpacity', 0.3);
                dailyVolumeChart.setOption('series', {
                    0: {targetAxisIndex: 0, color: '#555555', areaOpacity: 0, visibleInLegend: false}, //predictions
                    1: {targetAxisIndex: 0, color: '<?= (!$show_analytics ? '#5792B3' : '#6F6EA0') ?>'},
                    2: {targetAxisIndex: 0, color: '#C7E4C1', visibleInLegend: false, pointSize: 10, pointShape: 'circle', lineWidth: 0, areaOpacity: 0}, // spikes
                    3: {targetAxisIndex: 0, color: '#FEBC58', areaOpacity: 0},
                    4: {targetAxisIndex: 0, color: '#F0E86D'}
                });
                dailyVolumeChart.setOption('vAxes', {0: {logScale: false, minValue: 0, title: "Pinners"}});
                dailyVolumeControl.setOption('ui', {
                    'chartOptions': {
                        series: {
                            0: {targetAxisIndex: 0, color: '#555555', areaOpacity: 0, visibleInLegend: false}, //predictions
                            1: {targetAxisIndex: 0, color: '<?= (!$show_analytics ? '#5792B3' : '#6F6EA0') ?>'},
                            2: {targetAxisIndex: 0, color: '#62C462', visibleInLegend: false, areaOpacity: 0}, // spikes
                            3: {targetAxisIndex: 0, color: '#FEBC58', areaOpacity: 0},
                            4: {targetAxisIndex: 0, color: '#F0E86D'}
                        },
                        'chartArea': {
                            'width': '85%', 'left': '10%'
                        }
                    },
                    'chartType': 'AreaChart'
                });
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
                        $set = $i*18;
                        $columns .= "," . ($set+5) . "," . ($set+6) . "," . ($set+9) . "," . ($set+10) . "," . ($set+11) . "," . ($set+14) . "," . ($set+15) . "," . ($set+18);
                    }
                    echo $columns;
                    ?>
                ]);
                dailyVolumeChart.setOption('areaOpacity', 0.3);
                dailyVolumeChart.setOption('series', {
                    0: {targetAxisIndex: 0, color: '#555555', areaOpacity: 0, visibleInLegend: false}, //predictions
                    1: {targetAxisIndex: 0, color: '<?= (!$show_analytics ? '#6F6EA0' : '#549E54') ?>'},
                    2: {targetAxisIndex: 0, color: '#C7E4C1', visibleInLegend: false, pointSize: 10, pointShape: 'circle', lineWidth: 0, areaOpacity: 0}, // spikes
                    3: {targetAxisIndex: 0, color: '#FEBC58', areaOpacity: 0},
                    4: {targetAxisIndex: 0, color: '#F0E86D'}
                });
                dailyVolumeChart.setOption('vAxes', {0: {logScale: false, minValue: 0, title: "Potential Impressions"}});
                dailyVolumeControl.setOption('ui', {
                    'chartOptions': {
                        series: {
                            0: {targetAxisIndex: 0, color: '#555555', areaOpacity: 0, visibleInLegend: false}, //predictions
                            1: {targetAxisIndex: 0, color: '<?= (!$show_analytics ? '#6F6EA0' : '#549E54') ?>'},
                            2: {targetAxisIndex: 0, color: '#62C462', visibleInLegend: false, areaOpacity: 0}, // spikes
                            3: {targetAxisIndex: 0, color: '#FEBC58', areaOpacity: 0},
                            4: {targetAxisIndex: 0, color: '#F0E86D'}
                        },
                        'chartArea': {
                            'width': '85%', 'left': '10%'
                        }
                    },
                    'chartType': 'AreaChart'
                });
                drawChart();

            }
        }


        /**
         * Set the initial DataTable view for pin counts, which is the default view that loads.
         */
        var view = new google.visualization.DataView(data);
        view.setColumns([
            <?php
            $columns = "0";
            for ($i = 0; $i < count($query_args); $i++){
                $set = $i*18;
                $columns .= "," . ($set+1) . "," . ($set+2) . "," . ($set+7) . "," . ($set+10) . "," . ($set+11) . "," . ($set+12) . "," . ($set+15) . "," . ($set+16);
            }
            echo $columns;
            ?>
        ]);
        drawChart();

        window.onresize = resize;


       /*
       |--------------------------------------------------------------------------
       | EVENT LISTENERS - On HOVER and On CLICK functionality for tooltips, etc.
       |--------------------------------------------------------------------------
       */
        var selectionToggle = 0;
        google.visualization.events.addListener(dailyVolumeChart, 'ready', function () {

            /**
             * On Click ("select") functionality - this logic ensures that any tooltip we might
             * be showing on hover disappears when the chart is clicked on.
             */
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

            /**
             * Show tooltip on hover over elements of the chart.
             *
             * The tooltip data is contained in a column of the datatable, so when we hover over
             * something, we specify to basically show what's in the 3rd column of each domain's
             * series of columns.
             *
             */
            google.visualization.events.addListener(dailyVolumeChart.getChart(), 'onmouseover', function (e) {
                //Have to check to see if row returns null, which is the case
                //when you hover over the legend (trying to get its value when
                //null throws an error and we want to avoid this).
                if (e.row !== null) {

                    //we use getDataTable() to get the table of data that is
                    //currently in view (this accounts for any changes the user
                    //makes using the Range Filter scrubbers).
                    var tmpData = dailyVolumeChart.getDataTable();

                    /**
                     * Since we have to tell
                     */
                    var offset = 2;
                    switch (e.column) {
                        case 1:
                            offset = 4;
                            break;

                        case 2:
                            offset = 3;
                            break;

                        case 3:
                            offset = 2;
                            break;

                        case 6:
                            offset = 2;
                            break;

                        default:
                            //
                    }

                    var sel = tmpData.getValue(e.row, e.column + offset);
                    // populate the tooltip with data
                    $('#domain-tooltip').html(sel);
                    // show the tooltip
                    if (selectionToggle == 0) {
                        $('#domain-tooltip').show();
                    }

                    // if you've already clicked on a data point (i.e. made
                    // a selection to trigger the context menu),
                    // then the following will allow you to
                    // simply click on a new data point and trigger the
                    // the context menu there, without having to click on
                    // the original data point again first to de-select
                    // the original selection that you made.
                    if (selectionToggle == 1) {
                        google.visualization.events.trigger(dailyVolumeChart.getChart(), 'select', {});
                        //$('#tooltip').show();
                    }

                }

            });

            /**
             * On mouse-out, hide the tooltip.
             */
            google.visualization.events.addListener(dailyVolumeChart.getChart(), 'onmouseout', function (e) {
                // hide the tooltip
                $('#domain-tooltip').hide();
            });

            /**
             * On Mouse move, move the tooltip so that it stays with the users mouse position.
             */
            $(document).ready(function () {
                $('#daily-volume-chart').mousemove(function (e) {

                    var top = $('#main-content-scroll').scrollTop();
                    if ($('#menu').hasClass('slid')) {
                        $('#domain-tooltip').css({
                            left: e.pageX - 275,
                            top: e.pageY - 290 + top
                        });
                    } else {
                        $('#domain-tooltip').css({
                            left: e.pageX - 65,
                            top: e.pageY - 290 + top
                        });
                    }
                });
            });
        });


        /*
        |--------------------------------------------------------------------------
        | TOP SOURCES CHART - LEGACY DISCOVER V1 - KEYWORDS ONLY
        |--------------------------------------------------------------------------
        */

        /**
         * Prepare the Top-Sources chart date
         *
         * THIS IS NOT USED IN THE DOMAINS REPORT
         * LEGACY CODE FROM DISCOVER V1 USED FOR KEYWORDS ONLY.
         */
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
                chartArea: {left: 150},
                fontSize: 13,
                height: 300,
                legend: {position: "none"}
            }
            <?php else: ?>
            {
                chartArea : {
                    left:150
                }
            ,
                fontSize  : 13,
                    height
            :
                300,
                    legend
            :
                {
                    position:"top"
                }
            ,
                colors    : ['#5792B3', '#D77E81', '#82649E', '#FEBC58', '#F0E86D'],
                    isStacked
            :
                true
            }
            <?php endif ?>
        );
            <?php endif ?>
    }


    /*
    |--------------------------------------------------------------------------
    | TOOLTIP CONTEXT MENU FUNCTIONALITY
    |--------------------------------------------------------------------------
    */

    /**
     * Top Pins context menu
     */
    function handleTopPins(selector) {
        if ($(selector).parent().hasClass('disabled')) {
            return false;
        }

        return true;
    }

    /**
     * Top Pinners Context menu
     */
    function handleTopPinners(selector) {
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
            }
        });
    }

    /**
     * Wordcloud context menu (NOT USED IN DOMAINS REPORT)
     * LEGACY CODE FROM DISCOVER V1 ONLY USED FOR KEYWORDS
     */
    function handleWordCloud(selector) {
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
                    {'width': 560, 'height': 300, 'delayedMode': true, 'removeOverflowing': false}
                );
            }
        });
    }
</script>

<?php
$date_range_label = '';

if ($day_range == 7) {
    $date_range_label = '(Last 7 Days)';
} else if ($day_range == 14) {
    $date_range_label = '(Last 14 Days)';
} else if ($day_range == 30) {
    $date_range_label = '(Last 30 Days)';
} else if ($day_range == 0) {
    $date_range_label = '(All-Time)';
}
?>
<div class="row margin-fix">
<div class="span8">
    <div class="dashboard" style='margin-bottom:-10px; margin-top:-10px;'>
        <div style='text-align:left;'>
            <div class="row" style='margin:10px 0 10px;'>
                <div class='feature-wrap'>
                    <div id="site-pins-toggle-dash" class="feature feature-left third active">
                        <div>
                            <div class='feature-stat'>
                                <?= number_format($period_pins, 0); ?>
                            </div>
                        </div>
                        <h4> Pins </h4>

                        <div class='feature-growth'>
                            <span class='time'>Daily Average</span>
                            <span class='growth'><?= $avg_pins; ?></span>
                        </div>
                    </div>

                    <div id="pinners-toggle-dash" class="feature feature-middle third">
                        <div>
                            <div class='feature-stat'>
                                <?= (!$show_analytics ? number_format($period_pinners, 0) : number_format($period_reach, 0)) ?>
                            </div>
                        </div>
                        <h4> <?= (!$show_analytics ? 'Pinners' : 'Potential Impressions') ?> </h4>

                        <div class='feature-growth'>
                            <span class='time'>Daily Average</span>
                            <span class='growth'>
                                <?= (!$show_analytics ? $avg_pinners : $avg_reach) ?>
                            </span>
                        </div>
                    </div>

                    <div id="reach-toggle-dash" class="feature feature-right third">
                        <div>
                            <div class='feature-stat'>
                                <?= (!$show_analytics ? number_format($period_reach, 0) : number_format($period_visits, 0)) ?>
                            </div>
                        </div>

                        <h4><?= (!$show_analytics ? 'Potential Impressions' : 'Visits') ?></h4>

                        <div class='feature-growth'>
                            <span class='time'>Daily Average</span>
                            <span class='growth'>
                                <?= (!$show_analytics ? $avg_reach : $avg_visits) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="module straight-top margin-fix">
        <div class="title">
            Daily Volume for "<?= implode('", "', $query_args) ?>"
            <span class="muted"><?= $date_range_label ?></span>
        </div>

        <div class="chart-upgrade well hidden">
            <strong>Upgrade to Unlock</strong><br>&nbsp;
            <ul>
                <li>
                    <strong>Track your Impressions</strong> across time<br> on the Pro plan.
                </li>
            </ul>
            <a class="btn-link" href="/upgrade?ref=domain_chart">
                <button class="btn btn-success btn-block">
                    <i class="icon-arrow-right"></i> Learn More
                </button>
            </a>
            <a class="btn-link" style="display: block; margin-top: 5px;" href="/demo/pro?follow=back">
                <button class="btn btn-mini btn-block">
                    or Try the Pro Demo
                </button>
            </a>
        </div>

        <div id="domain-daily-chart-div">
            <div id='daily-volume-chart' style='width: 100%; height: 230px;'></div>
            <div id='daily-volume-control' style='width: 100%; height: 45px;'></div>
        </div>
        <div class="dashboard">
            <div class="feature-wrap-chart-controls"
                 style='text-align:left;position: absolute; left:-99999px;'>
                <form>
                    <input id='pins-toggle' type='radio' value='pins-toggle'> Pins </input>
                    <input id='pinners-toggle' type='radio' value='pinners-toggle'> Pinners </input>
                    <input id='reach-toggle' type='radio' value='reach-toggle'> Reach </input>
                </form>
            </div>
        </div>
        <div id='domain-tooltip' style="font-size:12px;"></div>
    </div>
</div>

<div class="module module-trending-images span4">
    <?php if (empty($trending_topics)): ?>
        <div class="alert alert-info">
            No images currently trending from <?= implode('", "', $query_args) ?>.
            <a target="_blank" href="http://business.pinterest.com/en/pin-it-button">
                <button class="btn btn-primary">Install Pin-It Button</button>
            </a>
        </div>
    <?php else: ?>

        <div class="title sticky-module-title" style="margin-bottom:10px;width:26.5%;">
            <span class="module-title-text">
                Trending Images
                <span class="muted date-range-label"><?= $date_range_label ?></span>
            </span>
            <a href="<?= ($trending_images_enabled == 1 ? URL::route('domain-trending-images-default', array($query_string)) : "#") ?>"
               id="domain-insights-trending-images-cta"
               class="pull-right module-title-cta <?= (!$trending_images_enabled ? "disabled" : "") ?>">
                Full Report <?= (!$trending_images_enabled ? "<i class='icon-lock'></i>" : "→") ?>
            </a>
        </div>

        <div class="trending-images" style="margin-top:10px;">
            <?php foreach ($trending_topics as $topic => $pins): ?>
                <!--                                        --><?php //foreach ($pins as $i => $pin): ?>
                <!--                                            --><?php //if ($i > 20) break ?>
                <!--                                            <div class="item" data-popularity="--><?//=$pin->count;?><!--" data-repins="--><?//=$pin->sum_repins; ?><!--">-->
                <!--                                                <img src="--><?//= $pin->image_url ?><!--" style="width:100%;height:100%;"-->
                <!--                                                     data-toggle    = "popover"-->
                <!--                                                     data-placement = "top"-->
                <!--                                                     data-container = "body"-->
                <!--                                                     data-content   = "Pinned --><?//= $pin->count ?><!-- times. (--><?//= $pin->sum_repins; ?><!-- total repins)"-->
                <!--                                                    />-->
                <!--                                            </div>-->
                <!--                                        --><?php //endforeach ?>
                <?php foreach ($pins as $i => $pin): ?>
                    <?php if ($i > 0) break ?>
                    <a <?= ($trending_images_enabled == 1 ? 'href="' . URL::route('domain-trending-images-default', array($query_string)) . '"' : "") ?>
                       target="_blank"
                       data-popularity="<?= $pin->count; ?>"
                       style="background-color: <?= $pin->dominant_color ?>; background-image: url('<?= $pin->image_url ?>');"
                       class="item large-pin"
                       data-toggle="popover"
                       data-placement="top"
                       data-container="body"
                       data-content="Pins: <strong><?= $pin->count ?></strong>.
                                                                    <?= (($pin->total_engagement) > 0 ? "<br>Engagement: <strong>$pin->total_engagement</strong>" : "") ?>
                                                                    <?= ($trending_images_enabled == 1 ? '<br><em>click to view reach & pinners</em>' : '') ?>">
                        <div class="trending-image-pin-count"><?= $pin->count ?></div>
                    </a>
                <?php endforeach ?>
                <?php foreach ($pins as $i => $pin): ?>
                    <?php if ($i < 6 && $i > 0): ?>
                        <a <?= ($trending_images_enabled == 1 ? 'href="' . URL::route('domain-trending-images-default', array($query_string)) . '"' : "") ?>
                           target="_blank"
                           data-popularity="<?= $pin->count; ?>"
                           style="background-color: <?= $pin->dominant_color ?>; background-image: url('<?= $pin->image_url ?>');"
                           class="item medium-pin"
                           data-toggle="popover"
                           data-placement="top"
                           data-container="body"
                           data-content="Pins: <strong><?= $pin->count ?></strong>.
                                                                    <?= (($pin->total_engagement) > 0 ? "<br>Engagement: <strong>$pin->total_engagement</strong>" : "") ?>
                                                                    <?= ($trending_images_enabled == 1 ? '<br><em>click to view reach & pinners</em>' : '') ?>">
                            <div class="trending-image-pin-count"><?= $pin->count ?></div>
                        </a>
                    <?php endif ?>
                <?php endforeach ?>
                <?php foreach ($pins as $i => $pin): ?>
                    <?php if ($i > 18) break ?>
                    <?php if ($i >= 6): ?>
                        <a <?= ($trending_images_enabled == 1 ? 'href="' . URL::route('domain-trending-images-default', array($query_string)) . '"' : "") ?>
                           target="_blank"
                           data-popularity="<?= $pin->count; ?>"
                           style="background-color: <?= $pin->dominant_color ?>; background-image: url('<?= $pin->image_url ?>');"
                           class="item small-pin"
                           data-toggle="popover"
                           data-placement="top"
                           data-container="body"
                           data-content="Pins: <strong><?= $pin->count ?></strong>.
                                                                    <?= (($pin->total_engagement) > 0 ? "<br>Engagement: <strong>$pin->total_engagement</strong>" : "") ?>
                                                                    <?= ($trending_images_enabled == 1 ? '<br><em>click to view reach & pinners</em>' : '') ?>">
                        </a>
                    <?php endif ?>
                <?php endforeach ?>
            <?php endforeach ?>
        </div>



        <!--                                <table class="table table-condensed" style="margin-top:0px;">-->
        <!--                                    <tbody>-->
        <!--                                    --><?php //foreach ($trending_topics as $topic => $pins): ?>
        <!--                                        --><?php //$encoded_topic = str_replace('+', ' ', urlencode($topic)) ?>
        <!--                                        <tr>-->
        <!--                                            --><?php //if (count($query_args) > 1): ?>
        <!--                                            <td class="topic">-->
        <!--                                                <a href="--><?//= URL::route('domain-insights', array($encoded_topic)) ?><!--">-->
        <!--                                                    --><?php //if (strpos($topic, '.') !== false): ?>
        <!--                                                        <span class="label label-success">--><?//= $topic ?><!--</span>-->
        <!--                                                    --><?php //else: ?>
        <!--                                                        <span class="label label-info">--><?//= $topic ?><!--</span>-->
        <!--                                                    --><?php //endif ?>
        <!--                                                </a>-->
        <!--                                            </td>-->
        <!--                                            --><?php //endif ?>
        <!--                                            <td class="pins">-->
        <!--                                                --><?php //foreach ($pins as $i => $pin): ?>
        <!--                                                    --><?php //if ($i > 0) break ?>
        <!--                                                    <a href="http://pinterest.com/pin/--><?//= $pin->pin_id ?><!--" target="_blank"-->
        <!--                                                        style="background-color: --><?//= $pin->dominant_color ?><!--; background-image: url('--><?//= $pin->image_url ?><!--');"-->
        <!--                                                        class="large-pin"-->
        <!--                                                        data-toggle    = "popover"-->
        <!--                                                        data-placement = "top"-->
        <!--                                                        data-container = "body"-->
        <!--                                                        data-content   = "Pinned --><?//= $pin->count ?><!-- times. (--><?//= $pin->sum_repins ?><!-- total repins)">-->
        <!--                                                    </a>-->
        <!--                                                --><?php //endforeach ?>
        <!--                                            </td>-->
        <!--                                            <td class="pins" style="padding:0px 2px 4px;">-->
        <!--                                                --><?php //foreach ($pins as $i => $pin): ?>
        <!--                                                    --><?php //if ($i < 7 && $i > 0): ?>
        <!--                                                        <a href="http://pinterest.com/pin/--><?//= $pin->pin_id ?><!--" target="_blank"-->
        <!--                                                            style="background-color: --><?//= $pin->dominant_color ?><!--; background-image: url('--><?//= $pin->image_url ?><!--');"-->
        <!--                                                            class="medium-pin"-->
        <!--                                                            data-toggle    = "popover"-->
        <!--                                                            data-placement = "top"-->
        <!--                                                            data-container = "body"-->
        <!--                                                            data-content   = "Pinned --><?//= $pin->count ?><!-- times. (--><?//= $pin->sum_repins ?><!-- total repins)">-->
        <!--                                                        </a>-->
        <!--                                                    --><?php //endif ?>
        <!--                                                --><?php //endforeach ?>
        <!--                                            </td>-->
        <!--                                        </tr>-->
        <!--                                        <tr>-->
        <!--                                            <td class="pins" colspan="2">-->
        <!--                                                --><?php //foreach ($pins as $i => $pin): ?>
        <!--                                                    --><?php //if ($i > 26) break ?>
        <!--                                                        --><?php //if ($i >= 7): ?>
        <!--                                                        <a href="http://pinterest.com/pin/--><?//= $pin->pin_id ?><!--" target="_blank"-->
        <!--                                                            style="background-color: --><?//= $pin->dominant_color ?><!--; background-image: url('--><?//= $pin->image_url ?><!--');"-->
        <!--                                                            class="small-pin"-->
        <!--                                                            data-toggle    = "popover"-->
        <!--                                                            data-placement = "top"-->
        <!--                                                            data-container = "body"-->
        <!--                                                            data-content   = "Pinned --><?//= $pin->count ?><!-- times. (--><?//= $pin->sum_repins ?><!-- total repins)">-->
        <!--                                                        </a>-->
        <!--                                                        --><?php //endif ?>
        <!--                                                --><?php //endforeach ?>
        <!--                                            </td>-->
        <!--                                            <td class="cta">-->
        <!--                                                <a href="--><?//= URL::route('domain-feed', array('trending', $encoded_topic)) ?><!--">-->
        <!--                                                    <i class="icon-fire"></i>-->
        <!--                                                    Discover More-->
        <!--                                                </a>-->
        <!--                                            </td>-->
        <!--                                        </tr>-->
        <!--                                    --><?php //endforeach ?>
        <!--                                    </tbody>-->
        <!--                                </table>-->
    <?php endif ?>
</div>

</div>


<div class="row margin-fix" style="margin-top:20px;">

    <?php if (!empty($influencers)): ?>
        <div class="module module-influencers span7">
            <div class="title sticky-module-title" style="width:51%;">
                <span class="module-title-text">
                    Top Pinners
                    <span class="muted date-range-label"><?= $date_range_label ?></span>
                </span>

                <?php $top_pinner_report_url = ($top_pinners_anonymous) ? 'javascript:void(0)' : '/domain-pinners'; ?>
                <a class="pull-right module-title-cta <?= ($top_pinners_anonymous ? "disabled" : "") ?>" id="js-top-pinners-full-report" href="<?= $top_pinner_report_url ?>" >
                    Full Report <?= ($top_pinners_anonymous ? "<i class='icon-lock'></i>" : "→") ?>
                </a>
            </div>

            <?php $table_class = ($top_pinners_anonymous) ? ' muted' : ''; ?>
            <table class="table table-striped table-bordered active-pinners<?= $table_class ?>">
                <thead>
                <tr>
                    <th>Rank</th>
                    <th>Pinner</th>
                    <th>Mentions</th>
                    <th class="sorting_desc">
                        Potential Impressions
                        <i class="icon-help"
                           data-toggle="popover"
                           data-placement="top"
                           data-container="body"
                           data-content="Potential Impressions this Pinner has generated for <?= implode('", "', $query_args) ?>"
                            ></i>
                    </th>
                </tr>
                </thead>
                <tbody>
                    <?php foreach ($influencers as $i => $influencer): ?>
                        <tr<?= ($i >= 5) ? ' class="row-togglable hidden"' : '' ?>>
                            <td class="rank"><?= $i + 1 ?></td>
                            <td>
                                <?= View::make('analytics.pages.domain.profile', array('profile' => $influencer, 'component' => 'Top Pinners', 'anonymous' => $top_pinners_anonymous)) ?>
                            </td>
                            <td class="mentions">
                                <?= number_format($influencer->mentions_count) ?>
                            </td>
                            <td class="reach"><?= number_format($influencer->reach) ?></td>
                        </tr>
                        <?php if ($i + 1 >= $top_pinners_allowed) break ?>
                    <?php endforeach ?>

                    <tr class="row-toggle">
                        <td colspan="5">
                            <?php if ($plan->plan_id == 1): ?>
                                <a href="/upgrade">
                                    Upgrade to View Pinner Profiles & More Top Pinners
                                </a>
                            <?php elseif ($top_pinners_allowed == 5): ?>
                                <a href="/upgrade">Upgrade to View More Top Pinners</a>
                            <?php else: ?>
                                <a href="javascript:void(0)" id="influencers-row-toggle"
                                   class="track-click" data-component="Top Pinners"
                                   data-element="Show More Link">
                                    Show More Top Pinners
                                </a>
                            <?php endif ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endif ?>

    <div class="module module-hashtags span5">
        <div class="title sticky-module-title" style="margin-bottom:10px; width:34.5%">
            <span class="module-title-text">
                Top Hashtags
                <span class="muted date-range-label"><?= $date_range_label ?></span>
            </span>
        </div>

        <?php if ($example_wordcloud): ?>
            <div class="alert alert-info">
                No hashtag matches for your domain have been found.<br />
                This is an example word cloud.
            </div>
        <?php endif ?>
        <div id="half-wordcloud" class="wordcloud-wrapper"></div>
    </div>
</div>

</div>
</div>
</div>
</div>
</div>

<div id="volumeSnapshotModal" class="modal hide fade" tabindex="-1" role="dialog"
     aria-labelledby="volumeSnapshotModalLabel" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="volumeSnapshotModalLabel">Word Cloud</h3>
    </div>
    <div class="modal-body wordcloud-wrapper"></div>
</div>


<?= $top_pinners_report_popover ?>
<?= $trending_images_report_popover ?>

<script type="text/javascript">
    $(function () {
        $('#influencers-row-toggle').on('click', function () {
            $('.module-influencers .row-togglable').show();
            $(this).parents('tr').hide();
        });
    });
</script>

<script type="text/javascript" src="/js/isotope.pkgd.min.js"></script>

<script type="text/javascript">
    $(function () {
        $('.trending-images').isotope({
            layoutMode: 'masonry',
            masonry: {
                columnWidth: 23,
                gutter: 2
            }
        });

        var moduleWidth = $('#half-wordcloud').width();
        var moduleHeight = $('.active-pinners').height();

        $('#half-wordcloud').jQCloud(
            <?= $wordcloud_data ?>,
            {'width': moduleWidth, 'height': moduleHeight, 'delayedMode': true, 'removeOverflowing': false,
                'afterCloudRender': function () {
                    $("[data-toggle='popover']").popover({html: true, delay: { show: 0, hide: 0 }, animation: false, trigger: 'hover'});
                }
            }
        );
    });
</script>

<?= $show_impressions_chart ?>