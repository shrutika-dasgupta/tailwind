<?//= $topic_bar ?>

<?php $popular_feeds_enabled       = $customer->hasFeature('domain_popularfeeds') ?>
<?php $date_range_enabled          = $customer->hasFeature('domain_custom_date_range'); ?>
<?php $insights_date_range_enabled = $customer->hasFeature('domain_insights_custom_date_range'); ?>
<?php $max_data_age                = $customer->maxAllowed('domain_data_age_max'); ?>
<?php $competitors_enabled         = $customer->hasFeature('nav_comp_bench'); ?>

<?php
/**
 * Create array of available periodic date ranges
 */
$periodic_date_ranges = array('week');
if ($customer->hasFeature('domain_feeds_extra_date_ranges')) {
    array_push($periodic_date_ranges, '2weeks', 'month', '2months');
}
if ($customer->hasFeature('domain_history_alltime')) {
    array_push($periodic_date_ranges, 'alltime');
}
?>


<?php
if ($day_limit > 0) {
    $max_data_age = min($max_data_age, $day_limit);
    $day_limit;
}

if (!$insights_date_range_enabled) {
    $popover_custom_date = createPopover("#reportrange", "hover", "bottom", "<span class=\"text-success\"><strong>Upgrade to Unlock</strong></span>", "domain_insights_date_range",
        $customer->plan()->plan_id, "<strong><ul><li>Get more historical data</li><li>Filter using custom date ranges</li></ul>");
}
?>

<div class="navbar navbar-listening navbar-report-subnav">
    <div class="navbar-inner">
        <ul class="nav">

            <li<?= ($type == 'insights') ? ' class="active"' : '' ?>>
                <a href="<?= URL::route('domain-insights', array($query_string)) ?>">
                    <strong>Insights</strong>
                </a>
            </li>
            <li class="divider-vertical"></li>
            <?php $popular = in_array($type, array('most-repinned', 'most-liked', 'most-commented', 'trending-images', 'latest', 'most-clicked', 'most-visits','most-transactions','most-revenue')) ? 'active ' : '' ?>
            <li class="<?= $popular ?>dropdown">
                <a href="javascript:void(0)" data-toggle="dropdown" class="dropdown-toggle track-click" data-component="Top Nav" data-element="Popular Feed Toggle">
                    <strong><?= (!in_array($type, array('insights', 'benchmarks', 'traffic')) ? "$pretty_report_name" : "Organic Activity") ?></strong> <b class="caret"></b>
                </a>

                <ul class="dropdown-menu">
                    <li<?= ($type == 'latest') ? ' class="active"' : '' ?>>
                        <a href="<?= URL::route('domain-feed', array('latest', $query_string)) ?>">
                            Latest Pins
                        </a>
                    </li>
                    <?php if ($popular_feeds_enabled): ?>
                        <li <?= ($type == 'trending-images') ? ' class="active"' : '' ?>>
                            <a href="<?= URL::route('domain-trending-images-default', array($query_string)) ?>"  id="trending-images-subnav-label">Most Pinned Images</a>
                        </li>
                        <li<?= (in_array($type, array('most-clicked', 'most-visits', 'most-transactions', 'most-revenue'))) ? ' class="active"' : '' ?>>
                            <a href="<?= URL::route('domain-feed', array('most-clicked')) ?>?date=<?= Input::get('date', 'week')?>" id="most-clicked-subnav-label">Most Clicked Pins</a>
                        </li>
                        <li<?= (in_array($type, array('most-repinned', 'most-liked'))) ? ' class="active"' : '' ?>  id="most-engaged-subnav-label">
                            <a href="<?= URL::route('domain-feed', array('most-repinned', $query_string)) ?>?date=<?= Input::get('date', 'week')?>">Most Engaged Pins</a>
                        </li>
                        <li<?= ($type == 'most-commented') ? ' class="active"' : '' ?>>
                            <a href="<?= URL::route('domain-feed', array('most-commented', $query_string)) ?>?date=<?= Input::get('date', 'week')?>" id="most-comments-subnav-label">Comments & Conversations</a>
                        </li>
                    <?php else: ?>
                        <li class="disabled"><a href="javascript:void(0)" id="trending-images-subnav-label">Most Pinned Images</a></li>
                        <li class="disabled"><a href="javascript:void(0)" id="most-clicked-subnav-label">Most Clicked Pins</a></li>
                        <li class="disabled"><a href="javascript:void(0)" id="most-engaged-subnav-label">Most Engaged Pins</a></li>
                        <li class="disabled"><a href="javascript:void(0)" id="most-comments-subnav-label">Comments & Conversations</a></li>

                    <?php endif ?>
                </ul>
            </li>

            <li class="divider-vertical" style=""></li>
            <li class="<?= ($type == 'benchmarks') ? 'active' : '' ?> <?= $nav_domain_benchmarks_class ?>" id="comp-domain-bench-subnav-label">
                <a <?= $nav_link_domain_benchmarks; ?>>
                    <strong>Benchmarks </strong>
                </a>
            </li>
            <li class="divider-vertical" style=""></li>

            <li id="traffic-subnav-label" class="<?= $nav_traffic_class; ?>">
                <a <?=$nav_link_traffic;?>>
                    <strong>Referral Traffic</strong>
                </a>
            </li>
            <li class="divider-vertical"></li>
        </ul>

<?php

/*
|--------------------------------------------------------------------------
| Insights (Free - Disabled), (Lite, Pro, Enterprise - Custom Date Range)
|
| Trending Images (Enterprise - Custom Date Range)
|--------------------------------------------------------------------------
*/

?>

        <?php if ($type == "trending-images" && $date_range_enabled
                    || $type == "insights"): ?>
            <ul class="nav pull-right">
                <li class="divider-vertical" style="margin-right:10px;"></li>
                <li>
                    <div id="reportrange" class="pull-right">
                        <i class="icon-calendar"></i>
                        <span><span class="start-date"><?= date("M j, Y", $last_date); ?></span> - <span class="end-date"><?= date("M j, Y", $current_date); ?></span></span> &nbsp; <i class="icon-caret-down"></i>
                    </div>

                        <?php if ($insights_date_range_enabled): ?>
                            <script type="text/javascript">
                                $('#reportrange').daterangepicker(
                                    {
                                        ranges: {
                                            'Last 7 Days': [moment("<?=$cache_timestamp_print;?>", "MM-DD-YYYY").subtract('days', 7), moment("<?=$cache_timestamp_print;?>", "MM-DD-YYYY")],
                                            'Last 14 Days': [moment("<?=$cache_timestamp_print;?>", "MM-DD-YYYY").subtract('days', 14), moment("<?=$cache_timestamp_print;?>", "MM-DD-YYYY")],
                                            'Last 30 Days': [moment("<?=$cache_timestamp_print;?>", "MM-DD-YYYY").subtract('days', 30), moment("<?=$cache_timestamp_print;?>", "MM-DD-YYYY")],
                                            'This Month': [moment().startOf('month'), moment().endOf('month')],
                                            'Last Month': [moment().subtract('month', 1).startOf('month'), moment().subtract('month', 1).endOf('month')]
                                        },
                                        startDate: moment("<?=$last_date_print;?>", "MM-DD-YYYY"),
                                        endDate: moment("<?=$current_date_print;?>", "MM-DD-YYYY"),
                                        minDate: moment("<?=$cache_timestamp_print;?>", "MM-DD-YYYY").subtract('days', <?=$max_data_age;?>),
                                        maxDate: moment("<?=$cache_timestamp_print;?>", "MM-DD-YYYY"),
                                        dateLimit: {
                                            'days': <?=$max_data_age;?>
                                        },
                                        format: "MMM DD, YYYY"
                                    }
                                );

                                $('#reportrange').on('apply', function(ev, picker) {
                                    window.location = "/domain/<?=$type;?>/<?=$query_string;?>/" + picker.startDate.format('MM-DD-YYYY') + "/" + picker.endDate.format('MM-DD-YYYY');
                                });
                            </script>
                        <?php else: ?>
                            <script type="text/javascript">
                                $('#reportrange').addClass('inactive');
                            </script>
                            <?= $popover_custom_date ?>
                        <?php endif ?>


                </li>
            </ul>
        <?php endif ?>


<?php
/*
|--------------------------------------------------------------------------
| Trending Images (Pro - PERIODIC DATE RANGES ONLY)
|--------------------------------------------------------------------------
*/
?>

        <?php if ($type == 'trending-images' && !$date_range_enabled): ?>

            <ul class="nav pull-right">

                <?php
                $dates = array(
                    'day'     => 'Last Day',
                    'week'    => 'Last Week',
                    'month'   => 'Last Month'
                );
                ?>

                <li class="divider-vertical" style="margin-right:10px;"></li>
                <li>
                    <div id="reportrange" class="pull-right dropdown static">
                        <div id="influencer-date-drop" href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <i class="icon-calendar"></i>
                        <span>
                            <?php foreach ($dates as $key => $timeframe): ?>
                                <?php if ($range == $key) echo $timeframe; ?>
                            <?php endforeach ?>
                        </span> &nbsp; <i class="icon-caret-down"></i>
                        </div>

                        <ul role="menu" class="daterangepicker dropdown-menu static opensleft ranges" aria-labelledby="influencer-date-drop">

                            <li role="presentation" class="<?=$day_class;?>">
                                <a role="menu-item" <?=$day_link;?>>Last Day</a>
                            </li>
                            <li role="presentation" class="<?=$week_class;?>">
                                <a role="menu-item" <?=$week_link;?>>Last Week</a>
                            </li>
                            <li role="presentation" class="<?=$month_class;?>">
                                <a role="menu-item" <?=$month_link;?>>Last Month</a>
                            </li>
                        </ul>
                    </div>

                </li>
            </ul>
        <?php endif ?>

<?php
/*
|--------------------------------------------------------------------------
| LATEST PINS (All Plans - Custom Date Range Enabled)
|--------------------------------------------------------------------------
*/
?>

        <?php if ($type == "latest"): ?>
            <ul class="nav pull-right">
                <li class="divider-vertical" style="margin-right:10px;"></li>
                <li>
                    <div id="reportrange" class="pull-right">
                        <i class="icon-calendar"></i>
                        <span> <span style="color:#999">as of </span> &nbsp;<span class="end-date"><?= date("M j, Y", $current_date); ?></span></span> &nbsp; <i class="icon-caret-down"></i>
                    </div>

                    <script type="text/javascript">

                        $('#reportrange').daterangepicker(
                            {
                                opens: 'left',
                                ranges: {
                                    'Today': [moment("<?=$cache_timestamp_print;?>", "MM-DD-YYYY").subtract('days', 1), moment("<?=$cache_timestamp_print;?>", "MM-DD-YYYY")]
                                },
                                startDate: moment("<?=$last_date_print;?>", "MM-DD-YYYY"),
                                endDate: moment("<?=$current_date_print;?>", "MM-DD-YYYY"),
                                minDate: moment("<?=$cache_timestamp_print;?>", "MM-DD-YYYY").subtract('days', <?=$max_data_age;?>),
                                maxDate: moment("<?=$cache_timestamp_print;?>", "MM-DD-YYYY"),
                                dateLimit: {
                                    'days': 1
                                },
                                format: "MMM DD, YYYY"
                            }
                        );

                        $('#reportrange').on('apply', function(ev, picker) {
                            window.location = "/domain/<?=$type;?>/<?=$query_string;?>/" + picker.startDate.format('MM-DD-YYYY') + "/" + picker.endDate.format('MM-DD-YYYY');
                        });

                    </script>
                </li>
            </ul>
        <?php endif ?>


<?php
/*
|--------------------------------------------------------------------------
| Competitor Benchmarks - (PRO, ENTERPRISE - CUSTOM DATE RANGE ENABLED)
|--------------------------------------------------------------------------
*/
?>

        <?php if ($type == "benchmarks"): ?>
            <ul class="nav pull-right">
                <li class="divider-vertical" style="margin-right:10px;"></li>
                <li>
                    <div id="reportrange" class="pull-right">
                        <i class="icon-calendar"></i>
                        <span><span class="start-date"><?= date("M j, Y", $last_date); ?></span> - <span class="end-date"><?= date("M j, Y", $current_date); ?></span></span> &nbsp; <i class="icon-caret-down"></i>
                    </div>

                    <script type="text/javascript">

                        $('#reportrange').daterangepicker(
                            {
                                ranges: {
                                    'Last 7 Days': [moment("<?=$cache_timestamp_print;?>", "MM-DD-YYYY").subtract('days', 7), moment("<?=$cache_timestamp_print;?>", "MM-DD-YYYY")],
                                    'Last 14 Days': [moment("<?=$cache_timestamp_print;?>", "MM-DD-YYYY").subtract('days', 14), moment("<?=$cache_timestamp_print;?>", "MM-DD-YYYY")],
                                    'Last 30 Days': [moment("<?=$cache_timestamp_print;?>", "MM-DD-YYYY").subtract('days', 30), moment("<?=$cache_timestamp_print;?>", "MM-DD-YYYY")],
                                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                                    'Last Month': [moment().subtract('month', 1).startOf('month'), moment().subtract('month', 1).endOf('month')]
                                },
                                startDate: moment("<?=$last_date_print;?>", "MM-DD-YYYY"),
                                endDate: moment("<?=$current_date_print;?>", "MM-DD-YYYY"),
                                minDate: moment("<?=$cache_timestamp_print;?>", "MM-DD-YYYY").subtract('days', <?=$max_data_age;?>),
                                maxDate: moment("<?=$cache_timestamp_print;?>", "MM-DD-YYYY"),
                                dateLimit: {
                                    'days': <?=$max_data_age;?>
                                },
                                format: "MMM DD, YYYY"
                            }
                        );

                        $('#reportrange').on('apply', function(ev, picker) {
                            window.location = "/domain/<?=$type;?>/custom/" + picker.startDate.format('MM-DD-YYYY') + "/" + picker.endDate.format('MM-DD-YYYY');
                        });

                    </script>
                </li>
            </ul>
        <?php endif ?>


<?php
/*
|--------------------------------------------------------------------------
| TRAFFIC REFERRALS (Free - Disabled), (Lite, Pro, Enterprise - Custom Date Range)
|--------------------------------------------------------------------------
*/
?>

        <?php if ($type == "traffic"): ?>

            <ul class="nav pull-right">
                <li class="divider-vertical" style="margin-right:10px;"></li>
                <li>
                    <div id="reportrange" class="pull-right">
                        <i class="icon-calendar"></i>
                        <span><span class="start-date"><?= date("M j, Y", $last_date); ?></span> - <span class="end-date"><?= date("M j, Y", $current_date); ?></span></span> &nbsp; <i class="icon-caret-down"></i>
                    </div>
                    <?php if ($insights_date_range_enabled): ?>
                        <script type="text/javascript">
                            $('#reportrange').daterangepicker(
                                {
                                    ranges: {
                                        'Last 7 Days': [moment("<?=$cache_timestamp_print;?>", "MM-DD-YYYY").subtract('days', 7), moment("<?=$cache_timestamp_print;?>", "MM-DD-YYYY")],
                                        'Last 14 Days': [moment("<?=$cache_timestamp_print;?>", "MM-DD-YYYY").subtract('days', 14), moment("<?=$cache_timestamp_print;?>", "MM-DD-YYYY")],
                                        'Last 30 Days': [moment("<?=$cache_timestamp_print;?>", "MM-DD-YYYY").subtract('days', 30), moment("<?=$cache_timestamp_print;?>", "MM-DD-YYYY")],
                                        'This Month': [moment().startOf('month'), moment().endOf('month')],
                                        'Last Month': [moment().subtract('month', 1).startOf('month'), moment().subtract('month', 1).endOf('month')]
                                    },
                                    startDate: moment("<?=$last_date_print;?>", "MM-DD-YYYY"),
                                    endDate: moment("<?=$current_date_print;?>", "MM-DD-YYYY"),
                                    minDate: moment("<?=$cache_timestamp_print;?>", "MM-DD-YYYY").subtract('days', <?=$day_limit;?>),
                                    maxDate: moment("<?=$cache_timestamp_print;?>", "MM-DD-YYYY"),
                                    dateLimit: {
                                        'days': <?=$day_limit;?>
                                    },
                                    format: "MMM DD, YYYY"
                                }
                            );

                            $('#reportrange').on('apply', function(ev, picker) {
                                window.location = "/domain/<?=$type;?>/custom/" + picker.startDate.format('MM-DD-YYYY') + "/" + picker.endDate.format('MM-DD-YYYY');
                            });
                        </script>

                    <?php else: ?>
                        <script type="text/javascript">
                            $('#reportrange').addClass('inactive');
                        </script>
                        <?= $popover_custom_date ?>
                    <?php endif ?>
                </li>
            </ul>
        <?php endif ?>



<?php
/*
|--------------------------------------------------------------------------
| Most Engaged Pins Feeds  (Pro, Enterprise - PERIODIC DATE RANGES)
|
| Most Valuable Pins Feeds (Pro, Enterprise - PERIODIC DATE RANGES)
|--------------------------------------------------------------------------
*/
?>

        <?php if (!in_array($type, array('latest','trending-images','insights','benchmarks','traffic'))): ?>

            <ul class="nav pull-right">

                <?php
                $date  = Input::get('date', 'week');
                $dates = array(
                    'week'    => 'Last 7 Days',
                    '2weeks'  => 'Last 14 Days',
                    'month'   => 'Last 30 Days',
                    '2months' => 'Last 60 Days',
                    'alltime' => 'All-Time',
                );
                ?>

                <li class="divider-vertical" style="margin-right:10px;"></li>
                <li>
                    <div id="reportrange" class="pull-right dropdown static">
                        <div id="influencer-date-drop" href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <i class="icon-calendar"></i>
                        <span>
                            <?= array_get($dates, $date) ?>
                        </span> &nbsp; <i class="icon-caret-down"></i>
                        </div>

                        <ul role="menu" class="daterangepicker dropdown-menu static opensleft ranges" aria-labelledby="influencer-date-drop">

                            <?php foreach ($dates as $key => $timeframe): ?>
                                <?php if (!in_array($key, $periodic_date_ranges)): ?>
                                <li class="disabled">
                                    <a href="javascript:void(0)"><?= $timeframe ?></a>
                                </li>
                            <?php else: ?>
                                <li<?php if ($date == $key) echo ' class="active"' ?>>
                                    <a href="<?= URL::route('domain-feed', array($type, $query_string, "date=$key")) ?>">
                                        <?= $timeframe ?>
                                    </a>
                                </li>
                            <?php endif ?>
                        <?php endforeach ?>
                    </ul>
                </li>
            </ul>
        <?php endif ?>


    </div>
</div>

