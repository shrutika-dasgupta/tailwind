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
                <a class='brand' href='/'><img
                        src='/img/tailwind-analytics-logo-new.png'></a>
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
                <li class="">
                    <div class="header-user-section">
                        <div class="pull-left user-image-wrapper"
                             style="background: url('<?= $cust_image; ?>');">
                        </div>
                        <div class="pull-left" style="width: 165px; margin-left:10px">
                            Welcome <?php echo $cust_first_name . " " . $cust_last_name; ?>
                        </div>
                    </div>
                    <div class="clearfix"></div>

                    <?= $account_select; ?>

                </li>
                <li class="heading old">
                    <a data-toggle="collapse" data-target="#growth" style="">
                        <span class="menu-icon-left"></span>
                        <span class="menu-title">Track Growth</span>
                        <span class="menu-icon-right"></span>
                    </a>
                </li>
                <li id='profile-nav-label' class="<?= $nav_profile_class; ?> ">
                    <a href="/profile">
                        <span class="menu-icon-left"><i class="icon-stats-up"></i></span>
                        <span class="menu-title">Your Profile</span>
                        <span class="menu-icon-right"></span>
                    </a>
                </li>
                <li id='board-nav-label' class="<?= $nav_boards_class; ?>">
                    <a href="/boards">
                        <span class="menu-icon-left"><i class="icon-grid-view"></i></span>
                        <span class="menu-title">Your Boards</span>
                        <span class="menu-icon-right"></span>
                    </a>
                </li>
                <li id='website-nav-label' class="<?= $nav_website_class; ?>">
                    <a <?=$nav_link_website;?>>
                        <span class="menu-icon-left"><i class="icon-earth"></i></span>
                        <span class="menu-title">Your Website</span>
                        <span class="menu-icon-right"></span>
                        <span class="menu-overlay">
                            Upgrade to Unlock →
                        </span>
                    </a>
                </li>
                <li class="heading old">
                    <a data-toggle="collapse" data-target="#audience" style="">
                        <span class="menu-icon-left"></span>
                        <span class="menu-title">Engage Your Audience</span>
                        <span class="menu-icon-right"></span>
                    </a>
                </li>
                <li id='trending-nav-label'
                    class="<?= $nav_trending_pins_class; ?>">
                    <a <?= $nav_link_trending_pins; ?>>
                        <span class="menu-icon-left"><i class="icon-fire"></i></span>
                        <span class="menu-title">Trending Pins</span>
                        <span class="menu-icon-right"></span>
                    </a>
                </li>
                <!--<li>
                    <a href="">
                        <span class="menu-icon-left"><i class="icon-user-add"></i></span>
                        <span class="menu-title">Newest Followers</span>
                        <span class="menu-icon-right"></span>
                    </a>
                </li>-->
                <li id='fans-nav-label' class="<?= $nav_top_repinners_class; ?>">
                    <a <?=$nav_link_top_repinners;?>>
                        <span class="menu-icon-left"><i class="icon-user-add"></i></span>
                        <span class="menu-title">Top Repinners</span>
                        <span class="menu-icon-right">
                            <span class="label label-important">
                                New!
                            </span>
                        </span>
                        <span class="menu-overlay">
                            Upgrade to Unlock →
                        </span>
                    </a>
                </li>
                <li id='influential-followers-nav-label' class="<?= $nav_influential_followers_class; ?>">
                    <a <?=$nav_link_influential_followers;?>>
                        <span class="menu-icon-left"><i class="icon-users"></i></span>
                        <span class="menu-title">Influential Followers</span>
                        <span class="menu-icon-right"></span>
                        <span class="menu-overlay">
                            Upgrade to Unlock →
                        </span>
                    </a>
                </li>
                <li id='domain-pinners-nav-label' class="<?= $nav_domain_pinners_class; ?>">
                    <a <?=$nav_link_domain_pinners;?>>
                        <span class="menu-icon-left"><i class="icon-heart-2"></i></span>
                        <span class="menu-title">Top Brand Promoters</span>
                        <span class="menu-icon-right"></span>
                        <span class="menu-overlay">
                            Upgrade to Unlock →
                        </span>
                    </a>
                </li>
                <li class="heading old">
                    <a data-toggle="collapse" data-target="#content" style="">
                        <span class="menu-icon-left"></span>
                        <span class="menu-title">Optimize Content</span>
                        <span class="menu-icon-right"></span>
                    </a>
                </li>
                <li id='pins-nav-label' class="<?= $nav_pins_class; ?>">
                    <a href="/pins/owned">
                        <span class="menu-icon-left"><i class="icon-pin"></i></span>
                        <span class="menu-title">Pin Inspector</span>
                        <span class="menu-icon-right">
                            <i class="icon-new"
                               data-toggle='popover'
                               data-placement='right'
                               data-content='Pin History now Available on Pro!'
                               data-container="body"></i>
                        </span>
                    </a>
                </li>
                <li id='category-nav-label'
                    class="<?= $nav_categories_class; ?>">
                    <a href="/categories">
                        <span class="menu-icon-left"><i class="icon-star"></i></span>
                        <span class="menu-title">Category Heatmaps</span>
                        <span class="menu-icon-right"></span>
                    </a>
                </li>
                <li id='day-time-nav-label'
                    class="<?= $nav_day_time_class; ?>">
                    <a <?= $nav_link_day_time;?>>
                        <span class="menu-icon-left"><i class="icon-history"></i></span>
                        <span class="menu-title">Peak Days & Times</span>
                        <span class="menu-icon-right"></span>
                        <span class="menu-overlay">
                            Upgrade to Unlock →
                        </span>
                    </a>
                </li>
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
                <li class="heading old">
                    <a data-toggle="collapse" data-target="#roi" style="">
                        <span class="menu-icon-left"></span>
                        <span class="menu-title">Measure ROI</span>
                        <span class="menu-icon-right"></span>
                    </a>
                </li>

                <li id='traffic-nav-label'
                    class="<?= $nav_traffic_class; ?>">
                    <a <?= $nav_link_traffic;?>>
                        <span class="menu-icon-left"><i class="icon-graph"></i></span>
                        <span class="menu-title">Traffic & Revenue</span>
                        <span class="menu-icon-right"></span>
                        <span class="menu-overlay">
                            Upgrade to Unlock →
                        </span>
                    </a>
                </li>

                <li id='roi-pinners-nav-label'
                    class="<?= $nav_roi_pinners_class; ?>">
                    <a <?= $nav_link_roi_pinners; ?>>
                        <span class="menu-icon-left"><i class="icon-dollar"></i></span>
                        <span class="menu-title" style='left:21px;'>Most Valuable Pinners</span>
                        <span class="menu-icon-right"></span>
                        <span class="menu-overlay">
                            Upgrade to Unlock →
                        </span>
                    </a>
                </li>
                <li id='roi-pins-nav-label' class="inactive disabled <?= $nav_roi_pins_class_fake; ?>">
                    <a <?=$nav_link_roi_pins_fake;?>>
                        <span class="menu-icon-left"><i class="icon-coins"></i></span>
                        <span class="menu-title">Most Valuable Pins</span>
                        <span class="menu-icon-right"></span>
                    </a>
                </li>
                <!--							</div>	-->

                <li class="heading old">
                    <a data-toggle="collapse" data-target="#roi" style="">
                        <span class="menu-icon-left"></span>
                        <span class="menu-title">Competitors</span>
                    </a>
                </li>
                <li id='comp-bench-nav-label'
                    class="<?= $nav_competitor_benchmarks_class; ?>">
                    <a <?=$nav_link_competitor_benchmarks;?>>
                        <span class="menu-icon-left"><i class="icon-chart"></i></span>
                        <span class="menu-title">Competitor Benchmarks</span>
                        <span class="menu-icon-right"></span>
                        <span class="menu-overlay">
                            Upgrade to Unlock →
                        </span>
                    </a>
                </li>
                <li id='comp-intel-nav-label' style="opacity:0.4">
                    <a data-toggle='popover' data-placement='right'
                       data-content='Available in Enterprise Plans'
                       data-container="body">
                        <span class="menu-icon-left"><i class="icon-lightbulb"></i></span>
                        <span class="menu-title">Competitor Intelligence</span>
                        <span class="menu-icon-right"></span>
                    </a>
                </li>

            </ul>
        </div>
    </div>
</div>

<?= $menu_bottom_js; ?>