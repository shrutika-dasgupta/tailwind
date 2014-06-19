<?php
/**
 * @author Alex
 *         Date: 8/29/13 12:39 AM
 *
 * TODO: fix logo path
 *
 * TODO: move profile image variable away from session var currently used ($_SESSION['image'])
 *
 * TODO:
 *
 */
?>

<div id="menu">
    <div id="menu-inner">
        <div id="menu-top-toolbar">
            <div id="menu-top-inner">
                <div class="pull-left slider" id="menu-toggle"><i class="icon-list"></i></div>
                <a class='brand' href='/'>
                    <img src='/img/tailwind-analytics-logo-new.png'></a>
            </div>
        </div>
        <div id="menu-bottom">
            <a <?= $upgrade_button_link; ?> id="nav-trial-button"
                                            class="btn btn-block btn-success <?= $upgrade_button_class; ?>">
                <?= $upgrade_button_text; ?>
            </a>
            <span class="<?= $upgrade_button_class; ?>"
                  id="nav-upgrade-sub-cta"><?= $upgrade_button_sub_text; ?></span>
        </div>
        <div id="menu-content-scroll">
            <ul>


                <?= $account_select; ?>

                <li class="heading" >
                    <a style="cursor:default;padding:0;">
                    </a>
                </li>

                <li class="heading no-sub-menu" id="nav-section-dashboard">
                    <a href="/" style="">
                        <span class="menu-icon-left"><i class="icon-home"></i></span>
                        <span class="menu-title">Weekly Summary</span>
                        <span class="menu-icon-right"><i class="icon-angle-right"></i></span>
                    </a>
                </li>


<!--                <li id='website-nav-label' class="--><?//= $nav_website_class; ?><!--">-->
<!--                    <a --><?//=$nav_link_website;?><!-->
<!--                        <span class="menu-icon-left"><i class="icon-earth"></i></span>-->
<!--                        <span class="menu-title">Your Website</span>-->
<!--                        <span class="menu-icon-right"></span>-->
<!--                        <span class="menu-overlay">-->
<!--                            Learn More →-->
<!--                        </span>-->
<!--                    </a>-->
<!--                </li>-->

                <li class="heading" id="nav-section-domains">
                    <a class="menu-heading-collapse" data-toggle="collapse" data-target="#nav_menu_domain" style="">
                        <span class="menu-icon-left" style="padding-left: 9px;"><i class="icon-globe"></i></span>
                        <span class="menu-title">Monitor Your Domain</span>
                        <span class="menu-icon-right"><i class="icon-caret-down"></i></span>
                    </a>
                </li>
                <div id="nav_menu_domain" class="collapse <?php echo $nav_menu_domains; ?>">
<!--                    <li id="pulse-nav-label" class="--><?//= $nav_pulse_class; ?><!--">-->
<!--                        <a href="--><?//= URL::route('domain') ?><!--">-->
<!--                            <span class="menu-icon-left"><i class="icon-resistor"></i></span>-->
<!--                            <span class="menu-title">Pulse</span>-->
<!--                        </a>-->
<!--                    </li>-->
                    <li id="insights-nav-label" class="<?= $nav_domain_insights_class; ?>">
                        <a href="<?= $domain_insights_link;  ?>" <?= $domain_insights_attributes;?>>
                            <span class="menu-icon-left" style="font-size: 25px; padding-left:6px;vertical-align: middle;"><i class="icon-pie-chart"></i></span>
                            <span class="menu-title" style="left:21px;">Insights</span>
                        </a>
                    </li>
                    <?php
                    $nav_domain_feed_class = '';
                    if ($nav_domain_most_repinned_class
                        || $nav_domain_most_liked_class
                        || $nav_domain_most_commented_class
                        || $nav_domain_latest_class
                        || $nav_domain_trending_images_class
                        || $nav_domain_most_clicked_class
                        || $nav_domain_most_visits_class
                        || $nav_domain_most_transactions_class
                        || $nav_domain_most_revenue_class
                    ) {
                        $nav_domain_feed_class = 'active';
                    }
                    ?>
                    <li id="domain-trending-nav-label" class="<?= $nav_domain_feed_class; ?>">
                        <a href="<?= URL::route('domain-feed', array('latest')) ?>">
                            <span class="menu-icon-left"><i class="icon-pin-feed"></i></span>
                            <span class="menu-title">Organic Activity</span>
                        </a>
                    </li>
                    <li id="domain-benchmarks-nav-label" class="<?= $nav_domain_benchmarks_class; ?>">
                        <a <?= $nav_link_domain_benchmarks ?>>
                            <span class="menu-icon-left"><i class="icon-chart"></i></span>
                            <span class="menu-title">Domain Benchmarks</span>
                            <span class="menu-icon-right"></span>
                            <span class="menu-overlay">
                                Learn More →
                            </span>
                        </a>
                    </li>
                    <li id='traffic-nav-label'
                        class="<?= $nav_traffic_class; ?>">

                        <a <?= $nav_link_traffic;?>>
                            <span class="menu-icon-left"><i class="icon-traffic"></i></span>
                            <span class="menu-title">Referral Traffic</span>
                            <span class="menu-icon-right"></span>
                            <span class="menu-overlay">
                                Learn More →
                            </span>
                        </a>
                    </li>
<!--                    --><?php
//                        $nav_popular_class = '';
//                        if ($nav_most_repinned_class || $nav_most_liked_class || $nav_most_commented_class) {
//                            $nav_popular_class = 'active';
//                        }
//                    ?>
<!--                    <li id="discover-popular-nav-label" class="--><?//= $nav_popular_class; ?><!--">-->
<!--                        <a href="--><?//= URL::route('domain-feed', array('most-repinned')) ?><!--">-->
<!--                            <span class="menu-icon-left"><i class="icon-medal"></i></span>-->
<!--                            <span class="menu-title">Popular</span>-->
<!--                        </a>-->
<!--                    </li>-->
                </div>

            <?php if ($publisher_enabled or $content_enabled): ?>
                <li class="heading" id="nav-section-publisher">
                    <a data-toggle="collapse"
                       data-target="#nav_menu_publisher" style="">
                        <span class="menu-icon-left"><i
                                class="icon-megaphone"></i></span>
                    <span class="menu-title">
                        Publish &nbsp;
                        <span class="label label-info">ALPHA</span>
                    </span>
                        <span class="menu-icon-right"><i
                                class="icon-caret-down"></i></span>
                    </a>
                </li>
                <div id="nav_menu_publisher" class="collapse <?= $nav_menu_publisher; ?>">
                <?php if ($publisher_enabled): ?>
                    <li id="scheduling-nav-label"
                        class="<?= $nav_schedule_class; ?>">
                        <a href="<?= URL::route('publisher') ?>">
                            <span class="menu-icon-left" style="font-size: 25px;"><i class="icon-calendar"></i></span>
                            <span class="menu-title">Pin Scheduling</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($content_enabled): ?>
                    <li id="content-nav-label" class="<?= $nav_content_class; ?>">
                        <a href="<?= URL::route('content') ?>">
                            <span class="menu-icon-left" style="font-size: 25px; padding-left:6px;"><i class="icon-lightbulb"></i></span>
                            <span class="menu-title" style="left:21px;">Content Discovery</span>
                        </a>
                    </li>
                <?php endif ?>
                </div>
            <?php endif ?>

                <li class="heading" id="nav-section-measure">
                    <a class="menu-heading-collapse" data-toggle="collapse" data-target="#nav_menu_profile" style="">
                        <span class="menu-icon-left"><i class="icon-pinterest-2"></i></span>
                        <span class="menu-title">Track Your Brand Page</span>
                        <span class="menu-icon-right"><i class="icon-caret-down"></i></span>
                    </a>
                </li>
                <div id="nav_menu_profile" class="collapse <?php echo $nav_menu_profile; ?>">
                    <li id='profile-nav-label' class="<?= $nav_profile_class; ?> ">
                        <a href="/profile">
                            <span class="menu-icon-left"><i class="icon-stats-up"></i></span>
                            <span class="menu-title">Profile Performance</span>
                            <span class="menu-icon-right"></span>
                        </a>
                    </li>
                    <li id='board-nav-label' class="<?= $nav_boards_class; ?>">
                        <a href="/boards">
                            <span class="menu-icon-left"><i class="icon-board"></i></span>
                            <span class="menu-title">Board Insights</span>
                            <span class="menu-icon-right"></span>
                        </a>
                    </li>
                    <li id='comp-bench-nav-label'
                        class="<?= $nav_competitor_benchmarks_class; ?>">
                        <a <?= $nav_link_competitor_benchmarks; ?>>
                            <span class="menu-icon-left"><i class="icon-chart"></i></span>
                            <span class="menu-title">Competitor Benchmarks</span>
                            <span class="menu-icon-right"></span>
                        <span class="menu-overlay">
                            Learn More →
                        </span>
                        </a>
                    </li>

                </div>


                <li class="heading" id="nav-section-community">
                    <a class="menu-heading-collapse" data-toggle="collapse" data-target="#nav_menu_community" style="">
                        <span class="menu-icon-left"><i class="icon-users"></i></span>
                        <span class="menu-title">Engage Your Community</span>
                        <span class="menu-icon-right"><i class="icon-caret-down"></i></span>
                    </a>
                </li>
                <div id="nav_menu_community" class="collapse <?= $nav_menu_community; ?>">

                    <li id='influential-followers-nav-label' class="<?= $nav_followers_class; ?>">
                        <a href="<?= $followers_link;?>" <?= $followers_link_attributes;?>>
                            <span class="menu-icon-left"><i class="icon-users2"></i></span>
                            <span class="menu-title">Followers</span>
                            <span class="menu-icon-right"></span>
                            <span class="menu-overlay">
                                Learn More →
                            </span>
                        </a>
                    </li>
                    <li id='repinners-nav-label' class="<?= $nav_top_repinners_class; ?>">
                        <a href="<?= $repinners_link;?>" <?= $repinners_attributes;?>>
                            <span class="menu-icon-left"><i class="icon-user-add"></i></span>
                            <span class="menu-title">Repinners</span>
                            <span class="menu-icon-right">
<!--                            <span class="label label-important">-->
<!--                                New!-->
<!--                            </span>-->
                            </span>
                            <span class="menu-overlay">
                                Learn More →
                            </span>
                        </a>
                    </li>
                    <li id='domain-pinners-nav-label' class="<?= $nav_domain_pinners_class; ?>">
                        <a  href="<?= $brand_advocates_link;?>" <?= $brand_advocates_attributes;?>>
                            <span class="menu-icon-left"><i class="icon-megaphone"></i></span>
                            <span class="menu-title">Brand Advocates</span>
                            <span class="menu-icon-right"></span>
                            <span class="menu-overlay">
                                Learn More →
                            </span>
                        </a>
                    </li>
                    <li id='roi-pinners-nav-label'
                        class="<?= $nav_roi_pinners_class; ?>">
                        <a href="<?= $most_valuable_pinners_link;?>" <?= $most_valuable_pinners_attributes; ?>>
                            <span class="menu-icon-left"><i class="icon-heart-2"></i></span>
                            <span class="menu-title">Top Referrers</span>
                            <span class="menu-icon-right"></span>
                            <span class="menu-overlay">
                                Learn More →
                            </span>
                        </a>
                    </li>
                </div>

                <li class="heading" id="nav-section-optimize">
                    <a class="menu-heading-collapse" data-toggle="collapse" data-target="#nav_menu_optimize" style="">
                        <span class="menu-icon-left"><i class="icon-filter"></i></span>
                        <span class="menu-title">Optimize Content</span>
                        <span class="menu-icon-right"><i class="icon-caret-down"></i></span>
                    </a>
                </li>

                <div id="nav_menu_optimize" class="collapse <?php echo $nav_menu_optimize; ?>">
                    <li id='pins-nav-label' class="<?= $nav_pins_class; ?>">
                        <a href="/pins/owned">
                            <span class="menu-icon-left"><i class="icon-uni0430"></i></span>
                            <span class="menu-title">Pin Inspector</span>
                            <span class="menu-icon-right">
<!--                                <i class="icon-new"-->
<!--                                   data-toggle='popover'-->
<!--                                   data-placement='right'-->
<!--                                   data-content='Pin History now Available on Pro!'-->
<!--                                   data-container="body"></i>-->
                            </span>
                        </a>
                    </li>
                    <li id='viral-pins-nav-label' class="<?= $nav_viral_pins_class; ?>">
                        <a href="<?= $viral_pins_link; ?>" <?= $viral_pins_attributes;?>>
                            <span class="menu-icon-left"><i class="icon-fire"></i></span>
                            <span class="menu-title">Trending Pins</span>
                            <span class="menu-icon-right">

                            </span>
                            <span class="menu-overlay">
                                Learn More →
                            </span>
                        </a>
                    </li>
                    <li id='category-nav-label'
                        class="<?= $nav_categories_class; ?>">
                        <a href="<?= $category_heatmaps_link;?>" <?= $category_heatmaps_attributes;?>>
                            <span class="menu-icon-left"><i class="icon-heatmap"></i></span>
                            <span class="menu-title">Category Heatmaps</span>
                            <span class="menu-icon-right"></span>
                            <span class="menu-overlay">
                                Learn More →
                            </span>
                        </a>
                    </li>
                    <li id='day-time-nav-label'
                        class="<?= $nav_day_time_class; ?>">
                        <a href="<?= $days_times_link;?>" <?= $days_times_attributes;?>>
                            <span class="menu-icon-left"><i class="icon-history"></i></span>
                            <span class="menu-title">Peak Days & Times</span>
                            <span class="menu-icon-right"></span>
                            <span class="menu-overlay">
                                Learn More →
                            </span>
                        </a>
                    </li>
                </div>
                <?php if ($listening_enabled): ?>
                    <li class="heading" id="nav-section-listening">
                        <a data-toggle="collapse" data-target="#nav_menu_listening" style="">
                            <span class="menu-icon-left"><i class="icon-podcast"></i></span>
                            <span class="menu-title">
                                Discover &nbsp;
    <!--                            <span class="label label-success">New!</span>-->
                            </span>
                            <span class="menu-icon-right"><i class="icon-caret-down"></i></span>
                        </a>
                    </li>
                    <div id="nav_menu_listening" class="collapse <?php echo $nav_menu_listening; ?>">
                        <li id="pulse-nav-label" class="<?= $nav_listening_pulse_class; ?>">
                            <a href="<?= URL::route('discover') ?>">
                                <span class="menu-icon-left"><i class="icon-resistor"></i></span>
                                <span class="menu-title">Pulse</span>
                            </a>
                        </li>
                        <li id="insights-nav-label" class="<?= $nav_listening_insights_class; ?>">
                            <a href="<?= URL::route('discover-insights') ?>">
                                <span class="menu-icon-left"><i class="icon-pie-chart"></i></span>
                                <span class="menu-title">Insights</span>
                            </a>
                        </li>
                        <li id="discover-trending-nav-label" class="<?= $nav_listening_trending_class; ?>">
                            <a href="<?= URL::route('discover-feed', array('trending')) ?>">
                                <span class="menu-icon-left"><i class="icon-pin-feed"></i></span>
                                <span class="menu-title">Trending</span>
                            </a>
                        </li>
                        <?php
                        $nav_popular_class = '';
                        if ($nav_listening_most_repinned_class || $nav_listening_most_liked_class || $nav_listening_most_commented_class) {
                            $nav_listening_popular_class = 'active';
                        }
                        ?>
                        <li id="discover-popular-nav-label" class="<?= $nav_listening_popular_class; ?>">
                            <a href="<?= URL::route('discover-feed', array('most-repinned')) ?>">
                                <span class="menu-icon-left"><i class="icon-medal"></i></span>
                                <span class="menu-title">Popular</span>
                            </a>
                        </li>
                    </div>
                <?php endif; ?>


<!--                <li class="heading">-->
<!--                    <a data-toggle="collapse" data-target="#nav_menu_recommend" style="">-->
<!--                        <span class="menu-icon-left"><i class="icon-lightbulb"></i></span>-->
<!--                        <span class="menu-title">Recommendations</span>-->
<!--                        <span class="menu-icon-right"><i class="icon-caret-down"></i></span>-->
<!--                    </a>-->
<!--                </li>-->
<!--                <div id="nav_menu_recommend" class="collapse --><?php //echo $nav_menu_recommend; ?><!--">-->
<!--                    -->
<!--                </div>-->
                <!--							</div>	-->
                <!--<li class="heading">
                    <a data-toggle="collapse" data-target="#industry" style="cursor:pointer">
                        <span class="menu-icon-left"></span>
                        <span class="menu-title">Industry Benchmarks</span>
                    </a>
                </li>-->
                <!--<li>
                    <a href="">
                        <span class="menu-icon-left"><i class="icon-tie"></i></span>
                        <span class="menu-title">Competitors</span>
                    </a>
                </li>-->
                <!--							</div>	-->


            </ul>


        </div>
    </div>
</div>
<div id="modal" class="modal hide fade" style=" top:5%; bottom:5%;width: 700px; margin-left: -350px;">
    <div class="modal-body" style="max-height:100%; height:85%;">
        <img style="margin:0 auto; display: block;" src="/img/loading-small.gif" />
    </div>
    <div class="modal-footer">

        <a href="/demo/pro" class="btn btn pull-right btn-demo-footer">
            Try the Pro demo →
        </a>
        <div class="pull-right" style="margin: 5px 15px 0 0; font-size:20px;">Still not convinced?</div>
    </div>
</div>

<script>
    $( document ).ready(function() {
        $('#modal').on('hidden', function () {
            $('#modal .modal-body').html('<img style="margin:0 auto; display: block;" src="/img/loading-small.gif" />');
            $(this).removeData('modal');
        });
        $('#modal').on('shown', function () {
            setTimeout(
                function(){$('#modal .btn-demo-footer').attr('href',$('#modal a.btn-demo').attr('href'))},
            2000);
        });
    });
</script>
