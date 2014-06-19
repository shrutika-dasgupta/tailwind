<div id="nav-wrapper">
    <div id="top-bar">
        <div class="row">
            <div class="small-8 large-3 columns">
                <a href="/" class="tailwind logo"> Tailwind </a>
            </div>
            <div class="show-for-small small-4 columns mobile-navigation">
                <a class="btn" href="#navigation" onclick="

                 if($('#mobile-sub-navigation').css('top') == '45px'){
                $('#mobile-sub-navigation').stop().animate({top:'-300'},1000);
                };
                if($('#mobile-sub-navigation').css('top') == '-300px'){
                $('#mobile-sub-navigation').stop().animate({top:'45'},1000);
                };


                 return false;">
                    <i class="icon-reorder"></i>
                </a>
            </div>


            <div class="large-navigation hide-for-small large-9 columns">

                <div class="row nav-links right">
                    <span><a href="/features">FEATURES</a></span>
                    <span><a href="/agencies">AGENCIES</a></span>
                    <span><a href="/pricing">PRICING</a></span>
                    <span>
                        <a href="/about">ABOUT</a>
                        <span class="hiring"></span>
                    </span>
                    <span><a href="http://blog.tailwindapp.com/">BLOG</a></span>
                    <span class="btn-login text-right"><a class="btn" href="/login">LOGIN</a></span>
                    <span><a
                            onclick="jQuery('.help').show();jQuery('#popUpCTA').show(); return false;"
                            class="btn btn-gold btn-bright btn-cta-header">SIGN UP</a></span>

                    <!--                <span class="large-7 columns phone text-right">-->
                    <!--                    <i class="icon-phone"></i> Contact Us: (405) 702-9998-->
                    <!--                </span>-->
                </div>
            </div>

        </div>
    </div>

</div>

<ul class="show-for-small" id="mobile-sub-navigation">
    <li>
        <a class=" btn btn-info" href="/features">
            Features
        </a>
    </li>
    <li>
        <a class="btn btn-info" href="/agencies">
            Agencies
        </a>
    </li>
    <li>
        <a class="btn btn-info" href="/pricing">
            Pricing
        </a>
    </li>
    <li>
        <a class="btn btn-info" href="/about">
            About
        </a>
    </li>
    <li>
        <a class=" btn btn-info" href="http://blog.tailwindapp.com">
            Blog
        </a>
    </li>
</ul>

