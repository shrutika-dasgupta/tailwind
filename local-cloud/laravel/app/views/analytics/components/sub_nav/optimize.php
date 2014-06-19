<?php
/**
 * @author Alex
 * Date: 2/14/14 5:27 PM
 * 
 */
?>

<div class="navbar navbar-report-subnav nav-optimize">
    <div class="navbar-inner">
        <ul class="nav">

            <li id="pins-subnav-label" class="<?= $nav_pins_class; ?>">
                <a href="/pins/owned">
                    <strong>Pin Inspector</strong>
                </a>
            </li>

            <li class="divider-vertical"></li>

            <li id="top-pins-subnav-label"
                class="<?= $nav_viral_pins_class; ?>">
                <a href="<?= $viral_pins_link; ?>" <?= $viral_pins_attributes; ?>>
                    <strong>Trending Pins</strong>
                </a>
            </li>

            <li class="divider-vertical"></li>

            <li id="categories-subnav-label"
                class="<?= $nav_categories_class; ?>">
                <a href="<?= $category_heatmaps_link; ?>" <?= $category_heatmaps_attributes; ?>>
                    <strong>Category Heatmaps</strong>
                </a>
            </li>

            <li class="divider-vertical"></li>

            <li id="day-time-subnav-label" class="<?= $nav_day_time_class; ?>">
                <a href="<?= $days_times_link; ?>" <?= $days_times_attributes; ?>>
                    <strong>Peak Days & Times</strong>
                </a>
            </li>

            <li class="divider-vertical"></li>

<!--            <li class="dropdown --><?//= $hi; ?><!--">-->
<!--                <a data-toggle="dropdown" class="dropdown-toggle dropdown-view-summary" href="#">-->
<!--                    <strong>Popular Feed</strong> <b class="caret"></b>-->
<!--                </a>-->
<!---->
<!--                <ul class="dropdown-menu">-->
<!--                    <li>-->
<!--                        <a href="/listening/most-repinned/etsy.com">Most Repinned</a>-->
<!--                    </li>-->
<!--                    <li>-->
<!--                        <a href="/listening/most-liked/etsy.com">Most Liked</a>-->
<!--                    </li>-->
<!--                    <li>-->
<!--                        <a href="/listening/most-commented/etsy.com">Most Commented</a>-->
<!--                    </li>-->
<!--                    <li class="disabled">-->
<!--                        <a href="javascript:void(0)">Most Traffic</a>-->
<!--                    </li>-->
<!--                    <li class="disabled">-->
<!--                        <a href="javascript:void(0)">Most Revenue</a>-->
<!--                    </li>-->
<!--                </ul>-->
<!--            </li>-->
        </ul>

        <ul class="nav pull-right">

<?php if($report_url == "categories"){ ?>
            <li class="divider-vertical"></li>

            <li class="optimize-export">
                <a <?= $csv_url; ?> class='nav-export-button <?= $export_view_class; ?>'>
                    <button id='profile-export-button' class='btn btn-mini'>
                        Export <i class='icon-new-tab'></i>
                    </button>
                </a>
            </li>
<?php } ?>

<?php if ($report_url == "pins/owned" || $report_url == "pins/owned/trending"){ ?>
            <li class="divider-vertical"></li>

            <li class="optimize-export">
                <?=$export_module;?>
                <?=$export_popover;?>
            </li>
<?php } ?>

<?php if ($report_url == "pins/owned") { ?>
    <li class="divider-vertical"></li>
    <div class="pull-left" style='text-align:left;margin: 9px 5px 0 10px;'>
        <button id='start_tour' class='btn btn-mini' style='color:#468b60'><strong>
                <i class='icon-directions'></i> Take the Tour</strong>
        </button>
        <br/>
    </div>
<?php } ?>

<?php if($report_url == "pins/most-valuable"){ ?>
            <li class="divider-vertical" style="margin-right:10px;"></li>
            <li>
                <div id="reportrange" class="pull-right dropdown static">
                    <div id="influencer-date-drop" href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <i class="icon-calendar"></i>
                        <span>
                            <?=$datepicker_show;?>
                        </span> &nbsp; <i class="icon-caret-down"></i>
                    </div>

                    <ul role="menu" class="daterangepicker dropdown-menu static opensleft ranges" aria-labelledby="influencer-date-drop">

                        <li role="presentation" class="<?=$range7_show;?>">
                            <a role="menu-item" <?=$range7_link;?>>Last 7 Days</a>
                        </li>
                        <li role="presentation" class="<?=$range14_show;?>">
                            <a role="menu-item" <?=$range14_link;?>>Last 14 Days</a>
                        </li>
                        <li role="presentation" class="<?=$range30_show;?>">
                            <a role="menu-item" <?=$range30_link;?>>Last 30 Days</a>
                        </li>
                        <li role="presentation" class="<?=$range60_show;?>">
                            <a role="menu-item" <?=$range60_link;?>>Last 60 Days</a>
                        </li>
                        <li role="presentation" class="<?=$alltime_show;?>">
                            <a role="menu-item" <?=$alltime_link;?>>All-Time</a>
                        </li>
                    </ul>
                </div>

            </li>
<?php } ?>

<?php if($report_url == "pins/owned/trending"){ ?>
            <li class="divider-vertical" style="margin-right:10px;"></li>
            <li>
                <div id="reportrange" class="pull-right">
                    <i class="icon-calendar"></i>
                    <span><span class="start-date"><?= date("M j, Y", $last_date); ?></span> - <span class="end-date"><?= date("M j, Y", $current_date); ?></span></span> &nbsp; <i class="icon-caret-down"></i>
                </div>

                <script type="text/javascript">
                    <?php if(!$is_free_account){ ?>

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
                                'days': <?=$max_date_range_limit;?>
                            },
                            format: "MMM DD, YYYY"
                        }
                    );

                    $('#reportrange').on('apply', function(ev, picker) {
                        window.location = "/<?=$report_url;?>/" + picker.startDate.format('MM-DD-YYYY') + "/" + picker.endDate.format('MM-DD-YYYY');
                    });

                    <?php } else { ?>
                        $('#reportrange').addClass('inactive');
                    <?php } ?>
                </script>
            </li>


<?php } ?>

<!--            <li class="dropdown dropdown-date">-->
<!--                <a href="#" data-toggle="dropdown" class="dropdown-toggle">-->
<!--                    <strong>Last 7 Days</strong> <b class="caret"></b>-->
<!--                </a>-->
<!--                <ul class="dropdown-menu">-->
<!--                    <li class="active">-->
<!--                        <a href="http://beta.analytics.tailwindapp.com/listening/summary/etsy.com?date=week">-->
<!--                            Last 7 Days-->
<!--                        </a>-->
<!--                    </li>-->
<!--                    <li>-->
<!--                        <a href="http://beta.analytics.tailwindapp.com/listening/summary/etsy.com?date=2weeks">-->
<!--                            Last 14 Days </a>-->
<!--                    </li>-->
<!--                    <li>-->
<!--                        <a href="http://beta.analytics.tailwindapp.com/listening/summary/etsy.com?date=month">-->
<!--                            Last 30 Days </a>-->
<!--                    </li>-->
<!--                    <li>-->
<!--                        <a href="http://beta.analytics.tailwindapp.com/listening/summary/etsy.com?date=alltime">-->
<!--                            All-Time </a>-->
<!--                    </li>-->
<!--                </ul>-->
<!--            </li>-->
        </ul>
    </div>
</div>