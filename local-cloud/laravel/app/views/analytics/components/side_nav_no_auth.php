<?php
/**
 * @author Alex
 * Date: 8/29/13 12:39 AM
 * 
 */
?>

<div id="menu">
    <div id="menu-inner">
        <div id="menu-top-toolbar">
            <div id="menu-top-inner">
                <a class='brand' href='/'><img src='/v2/html/images/pinleague-analytics-logo.png'></a>
            </div>
        </div>
        <div id="menu-bottom">
        </div>
        <div id="menu-content-scroll">
            <ul>
                <li style="opacity:0.4">
                    <a>
                        <span class="menu-icon-left"></span>
                        <span class="menu-title"></span>
                        <span class="menu-icon-right"></span>
                    </a>
                </li>
                <li class="heading"  style="opacity:0.4">
                    <a data-toggle="collapse" data-target="#growth">
                        <span class="menu-icon-left"></span>
                        <span class="menu-title">Track Growth</span>
                    </a>
                </li>
                <div id="growth" class="collapse <?php echo $nav_growth; ?>">
                    <li <?php echo $nav_pro; ?> style="opacity:0.4">
                        <a>
                            <span class="menu-icon-left"><i class="icon-stats-up"></i></span>
                            <span class="menu-title">Your Profile</span>
                        </a>
                    </li>
                    <li <?php echo $nav_boards; ?> style="opacity:0.4">
                        <a>
                            <span class="menu-icon-left"><i class="icon-grid"></i></span>
                            <span class="menu-title">Your Boards</span>
                            <span class="menu-icon-right"></span>
                        </a>
                    </li>
                    <li <?php echo $nav_website; ?> style="opacity:0.4">
                        <a>
                            <span class="menu-icon-left"><i class="icon-earth"></i></span>
                            <span class="menu-title">Your Website</span>
                            <span class="menu-icon-right"></span>
                        </a>
                    </li>
                </div>
                <li class="heading" style="opacity:0.4">
                    <a data-toggle="collapse" data-target="#content">
                        <span class="menu-icon-left"></span>
                        <span class="menu-title">Optimize Content</span>
                    </a>
                </li>
                <div id="content" class="collapse <?php echo $nav_content; ?>">
                    <li <?php echo $nav_pro_pins; ?> style="opacity:0.4">
                        <a>
                            <span class="menu-icon-left"><i class="icon-pin"></i></span>
                            <span class="menu-title">Pin Analyzer</span>
                            <span class="menu-icon-right"></span>
                        </a>
                    </li>
                    <li <?php echo $nav_cat; ?> style="opacity:0.4">
                        <a>
                            <span class="menu-icon-left"><i class="icon-star"></i></span>
                            <span class="menu-title">Category Heatmap</span>
                        </a>
                    </li>
                    <li <?php echo $nav_time; ?> style="opacity:0.4">
                        <a>
                            <span class="menu-icon-left"><i class="icon-history"></i></span>
                            <span class="menu-title">Peak Days & Times</span>
                            <span class="menu-icon-right"></span>
                        </a>
                    </li>
                </div>
                <li class="heading" style="opacity:0.4">
                    <a data-toggle="collapse" data-target="#audience">
                        <span class="menu-icon-left"></span>
                        <span class="menu-title">Engage Your Audience</span>
                    </a>
                </li>
                <div id="audience" class="collapse <?php echo $nav_audience; ?>">
                    <li <?php echo $nav_trend; ?> style="opacity:0.4">
                        <a>
                            <span class="menu-icon-left"><i class="icon-fire"></i></span>
                            <span class="menu-title">Trending Pins</span>
                        </a>
                    </li>
                    <li style="opacity:0.4">
                        <a>
                            <span class="menu-icon-left"><i class="icon-user-add"></i></span>
                            <span class="menu-title">Newest Followers</span>
                            <span class="menu-icon-right"></span>
                        </a>
                    </li>
                    <li style="opacity:0.4">
                        <a>
                            <span class="menu-icon-left"><i class="icon-users-2"></i></span>
                            <span class="menu-title">Fans & Influencers</span>
                        </a>
                    </li>
                </div>
                <!--<li class="heading">
                    <a data-toggle="collapse" data-target="#industry" style="cursor:pointer">
                        <span class="menu-icon-left"></span>
                        <span class="menu-title">Industry Benchmarks</span>
                    </a>
                </li>-->
                <!--							<div id="industry" class="collapse <?php echo $nav_industry; ?>">-->
                <!--<li>
                    <a href="">
                        <span class="menu-icon-left"><i class="icon-tie"></i></span>
                        <span class="menu-title">Competitors</span>
                    </a>
                </li>-->
                <!--							</div>	-->
                <li class="heading" style="opacity:0.4">
                    <a data-toggle="collapse" data-target="#roi">
                        <span class="menu-icon-left"></span>
                        <span class="menu-title">Measure ROI</span>
                    </a>
                </li>
                <div id="roi" class="collapse <?php echo $nav_roi; ?>">
                    <li style="opacity:0.4">
                        <a>
                            <span class="menu-icon-left"><i class="icon-graph"></i></span>
                            <span class="menu-title">Traffic & Revenue</span>
                        </a>
                    </li>
                    <li style="opacity:0.4">
                        <a>
                            <span class="menu-icon-left"><i class="icon-coins"></i></span>
                            <span class="menu-title">Most Valuable Pins</span>
                            <span class="menu-icon-right"></span>
                        </a>
                    </li>
                    <li style="opacity:0.4">
                        <a>
                            <span class="menu-icon-left"><i class="icon-dollar"></i></span>
                            <span class="menu-title">Top Revenue Generators</span>
                        </a>
                    </li>
                </div>

                <li class="heading">
                    <a data-toggle="collapse" data-target="#comp" style="cursor:pointer">
                        <span class="menu-icon-left"></span>
                        <span class="menu-title">Competitors</span>
                    </a>
                </li>
                <div id="comp" class="collapse <?php echo $nav_comp; ?>">
                    <li <?php echo $nav_comp_bench." ".$nav_show_comp_bench; ?>>
                        <a <?php echo $nav_link_comp_bench ?>>
                            <span class="menu-icon-left"><i class="icon-chart"></i></span>
                            <span class="menu-title">Competitor Benchmarks</span>
                        </a>
                    </li>
                    <li style="opacity:0.4">
                        <a data-toggle='tooltip' data-placement='right' data-original-title='Coming Soon!' data-container="body">
                            <span class="menu-icon-left"><i class="icon-lightbulb"></i></span>
                            <span class="menu-title">Competitor Intelligence</span>
                            <span class="menu-icon-right"></span>
                        </a>
                    </li>
                </div>

            </ul>
        </div>
    </div>
</div>
<div id="main" style="left: 60px;">
    <div id="main-top-toolbar">

    </div>