<?php
/**
 * @author Alex
 * Date: 2/14/14 5:27 PM
 * 
 */
?>

<div class="navbar navbar-report-subnav nav-measure">
    <div class="navbar-inner">
        <ul class="nav">
            <li id="profile-subnav-label" class="<?= $nav_profile_class; ?>">
                <a href="/profile">
                    <strong>Your Profile</strong>
                </a>
            </li>

            <li class="divider-vertical"></li>

            <li id="boards-subnav-label" class="<?= $nav_boards_class; ?>">
                <a href="/boards">
                    <strong>Your Boards</strong>
                </a>
            </li>

            <li class="divider-vertical"></li>

            <li id="comp-bench-subnav-label" class="<?= $nav_competitor_benchmarks_class; ?>">
                <a <?= $nav_link_competitor_benchmarks;?>>
                    <strong>Competitor Benchmarks</strong>
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

            <?php if ($report_url == "profile") { ?>
                <li class="divider-vertical"></li>
                <li class="profile-export">
                    <a <?= $csv_url; ?> class='nav-export-button <?= $export_view_class; ?>'>
                        <button id='profile-export-button' class='btn btn-mini'>
                            Export <i class='icon-new-tab'></i>
                        </button>
                    </a>
                </li>
            <?php } else if ($report_url == "boards") { ?>
                <li class="divider-vertical"></li>
                <li class="profile-export">
                    <?= $export_module; ?>
                </li>
            <?php } ?>

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
                                'days': <?=$day_limit;?>
                            },
                            format: "MMM DD, YYYY"
                        }
                    );

                    $('#reportrange').on('apply', function(ev, picker) {
                        window.location = "/<?=$report_url;?>/custom/" + picker.startDate.format('MM-DD-YYYY') + "/" + picker.endDate.format('MM-DD-YYYY');
                    });

                <?php } else { ?>
                    $('#reportrange').addClass('inactive');
                <?php } ?>
                </script>
            </li>


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