<?php
/**
 * @author Alex
 * Date: 2/14/14 5:27 PM
 * 
 */
?>

<div class="navbar navbar-report-subnav nav-community">
    <div class="navbar-inner">
        <ul class="nav">

            <li id="newest-followers-subnav-label" class="<?=$nav_followers_class;?>">
                <a <?= $nav_link_followers ?>>
                    <strong>Followers</strong>
                </a>
            </li>

            <li class="divider-vertical"></li>
            <?php if ($brand_advocates_enabled) { ?>
            <?php if ($total_brand_mention_pins != 0) { ?>
                <li id="domain-pinners-subnav-label" class="<?= $nav_domain_pinners_class; ?>">
                    <a <?=$nav_link_domain_pinners;?>>
                        <strong>Brand Advocates</strong>
                    </a>
                </li>
            <?php } else if ($cust_domain || $cust_domain != ""){ ?>
                <li class='inactive' id='domain-pinners-subnav-label'>
                    <a>Brand Advocates </a>
                </li>
                <script>
                    $(document).ready(function () {
                        $('#domain-pinners-subnav-label').attr({
                            'data-toggle':'popover-click',
                            'data-placement':'bottom',
                            'data-title':'',
                            'data-content':'We\'re still on the lookout for pinners from <?=$cust_domain;?>! If you\'ve only recently began tracking a domain, this report may take a few days to populate.',
                            'data-container':'body'
                        });
                        $('#nav-domain-pinners').attr({
                            'data-toggle':'popover-click',
                            'data-placement':'right',
                            'data-title':'',
                            'data-content':'We\'re still on the lookout for pinners from <?=$cust_domain;?>! If you\'ve only recently began tracking a domain, this report may take a few days to populate.',
                            'data-container':'body'
                        });
                    });
                </script>
            <?php } else { ?>
                <li class='inactive' id='domain-pinners-subnav-label'>
                    <a <?=$nav_link_domain_pinners;?>>Brand Advocates </a>
                </li>
            <?php }} else { ?>
                <li class='inactive disabled go-pro' id='domain-pinners-subnav-label'>
                    <a href="<?=$brand_advocates_link;?>" <?=$brand_advocates_attributes;?>><strong>Brand Advocates</strong> </a>
                </li>
            <?php } ?>

            <li class="divider-vertical"></li>

            <li id="repinners-subnav-label" class="<?= $nav_top_repinners_class; ?>">
                <a href="<?=$repinners_link;?>"<?= $repinners_attributes;?>>
                    <strong>Repinners</strong>
                </a>
            </li>

            <li class="divider-vertical"></li>

            <li id="roi-pinners-subnav-label" class="<?= $nav_roi_pinners_class; ?>">
                <a href="<?= $most_valuable_pinners_link;?>" <?=$most_valuable_pinners_attributes;?>>
                    <strong>Top Referrers</strong>
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


            <li class="divider-vertical"></li>
            <li class="influencers-export">
                <a <?= $csv_url; ?> class='nav-export-button'>
                    <button id='profile-export-button' class='btn btn-mini <?= $export_class; ?>'>
                        Export <i class='icon-new-tab'></i>
                    </button>
                </a>
            </li>


            <li class="divider-vertical" style="margin-right:10px;"></li>
            <li>
                <div id="reportrange" class="pull-right dropdown static">

                <?php if ($customer->hasFeature('nav_fans')): ?>
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
                        <li role="presentation" class="<?=$alltime_show;?>">
                            <a role="menu-item" <?=$alltime_link;?>>All-Time</a>
                        </li>
                    </ul>
                </div>

                <?php else: ?>

                    <div id="influencer-date-drop" href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <i class="icon-calendar"></i>
                        <span>
                            <span style="color:#999">as of </span> &nbsp;
                            <span class="end-date"><?= date("M j, Y", $current_date); ?></span>
                        </span> &nbsp; <i class="icon-caret-down"></i>
                    </div>
                </div>
                <script type="text/javascript">
                    $('#reportrange').addClass('inactive');
                </script>

                <?php endif ?>
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