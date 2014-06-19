<?php
/**
 * @author Alex
 * Date: 9/4/13 1:43 AM
 * 
 */

if(isset($dataTable_pins) && $dataTable_pins){ ?>


    <!--Tour Options-->

    <!--{
            speed: 1, 				//> 0.1	 			//the multiplikator for the general duration of the steps
            axis: xy,	 			//'x', 'y', 'xy'	//The animated axis (iOS can't animate on both axis without flickering)
            autostart: false,		//true | false		//starts the Tour immediately after initialization
            autoplay: true,	 		//true | false	 	//steps change without user action
            pauseOnHover: true,		//true | false		//pauses the autoplay on mouseover
            keyboardNav: true,		//true | false	 	//activated keyboard navigation (left, right, up, down arrow, ESC, space)
            showProgress: true,		//true | false		//shows the progressbar
            showControls: true,		//true | false		//enables controls under the content.
            scrollBack: false,		//true | false	 	//scrolls back to the starting position after the tour has been finishe
            scrollDuration: 300,	//integer	 		//time (in ms) how long scrolling to the next step take.
            easing: "swing",  		//"linear"|"swing"	//the easing method for scrolling. Can be extended with the jquery.easing
            onStart: function (current) {},	 	 		//callback for start method. current is the id of the current step
            onStop: function (current) {},	 	 		//callback for stop method. current is the id of the current step
            onPause: function (current) {},	 	 		//callback for pause method. current is the id of the current step
            onPlay: function (current) {},	 	 		//callback for continue method. current is the id of the current step
            onChange: function (current) {},	 		//callback for every step. current is the id of the current step
            onFinish: function (current) {}	 			//callback if the tour is finished. current is the id of the current step
        }-->


    <!--	Options for each step-->

    <!--{
            html: 'Hello World', //The content of the box (required)
            position: 'c' //position of the box
            live: 'auto', //duration of the box ('auto' is for calculating)
            offset: 0, //offset in pixels
            wait: 0, //pause time before the next box is displayed
            expose: false, //exposures the target element
            overlayOpacity: 0.2, //defines the opacity of the overlay (false disables it)
            delayIn: 200,  //time to reveal the box
            delayOut: 100, //time to hide the box
            animationIn: 'fadeIn',  //animation type for showing the box
            animationOut: 'fadeOut', //animation type for hiding the box
            onBeforeShow: function(element) {}, //callback
            onShow: function(element) {}, //callback
            onBeforeHide: function(element) {}, //callback
            onHide: function(element) {}, //callback
            onStep: function(element, percentage) {}, //callback
            element: 'body', //the target element for the box
            goTo: null //required for multipage tours
    }-->


    <script src="/js/jtour/js/jTour.min.js"></script>
    <link rel="stylesheet" href="/js/jtour/css/theme2/style.css">

    <script>

        $(document).ready(function() {

            var options = {
                autoplay: false,
                pauseOnHover: false,
                scrollBack: true,
                showControls: false,
                axis: 'y',
                onStop: function(){
                    setCookie('tourplay4', 1, 365);
                }
            };

            var pinsTourData = [
                {
                    html: "<div class='tour_top'>Welcome to your Pins page! </div><div class='tour_middle'>Let's take a quick tour of all the features.. <br><br><span class='muted'>You can use your keyboard to control the tour: </span><br><span class='text-success'>[→] Next</span> , <span class='text-info'>[←] Previous</span> , <span class='muted'> [ESC] Exit </span></div><div class='tour_bottom'><a href='#' class='tour_btn_right' onclick='pinsTour.next();return false;'><button class='btn btn-success'>Get Started</button></a></div>"
                },{
                    html: "<a href='#' class='tour_close close' onclick='pinsTour.stop();return false;'>&times;</a><div class='tour_top'>Here are Your Pins</div><div class='tour_middle'>This table holds <i>everything</i> you've ever pinned </div><div class='tour_bottom'><a href='#' class='tour_btn_left' onclick='pinsTour.prev();return false;'><button class='btn'>Back</button></a><a href='#' class='tour_btn_right' onclick='pinsTour.next();return false;'><button class='btn btn-success'>Next</button></a></div>",
                    element: $('div#dynamic'),
                    position: 'n',
                    expose: true,
                    overlayOpacity: 0.7
                },{
                    html: "<a href='#' class='tour_close close' onclick='pinsTour.stop();return false;'>&times;</a><div class='tour_top'>Sorting: </div><div class='tour_middle'><span class='tour_action'>Click</span> on the title of any column to sort your pins any way you like.<br><span><br><blockquote><ul><li><b><u>TIP:</b></u> click on more than one column while holding the [shift] key to sort by multiple fields</li></ul></blockquote></span></div><div class='tour_bottom'><a href='#' class='tour_btn_left' onclick='pinsTour.prev();return false;'><button class='btn'>Back</button></a><a href='#' class='tour_btn_right' onclick='pinsTour.next();return false;'><button class='btn btn-success'>Next</button></a></div>",
                    element: $('tr').eq(0),
                    position: 's',
                    expose: true,
                    offset: {x:80,y:0},
                    overlayOpacity: 0.2,
                    //onShow: function(element){
                    //this.offset(-500,0,0);
                    //this.moveTo(my_move.offset().left, my_move.offset().top)
                    //this.offset(1000,0,800);
                    //this.box.position('se');
                    //this.offset(400,0,300);
                    //},
//		    	steps: 0:function(element){
//		    				this.offset(1000,0,2500);
//		    			}

                },{
                    html: "<a href='#' class='tour_close close' onclick='pinsTour.stop();return false;'>&times;</a><div class='tour_top'>Search: </div><div class='tour_middle'>Just <span class='tour_action'>start typing</span> and watch your results appear <i>instantly.</i><br><br>Go ahead, give it a try. <br><br>  <ul>Examples:<li>Find pins by <b><i>#hashtag</i></b></li><li>Look for pins from the same <b>Domain.</b></li></ul></div><div class='tour_bottom'><a href='#' class='tour_btn_left' onclick='pinsTour.prev();return false;'><button class='btn'>Back</button></a><a href='#' class='tour_btn_right' onclick='pinsTour.next();return false;'><button class='btn btn-success'>Next</button></a></div>",
                    element: $('div.dataTables_filter'), //use the second (starting with 0) a tag within a li tag as target element
                    position: 's', //display the box right to the target element (east)
                    expose: true,
                    overlayOpacity: 0.7
                },{
                    html: "<a href='#' class='tour_close close' onclick='pinsTour.stop();return false;'>&times;</a><div class='tour_top'>Filter: </div><div class='tour_middle'>Choose specific boards and categories to narrow things down.</div><div class='tour_bottom'><a href='#' class='tour_btn_left' onclick='pinsTour.prev();return false;'><button class='btn'>Back</button></a><a href='#' class='tour_btn_right' onclick='pinsTour.next();return false;'><button class='btn btn-success'>Next</button></a></div>",
                    element: $('div.column-filter-widgets'), //use the second (starting with 0) a tag within a li tag as target element
                    position: 'e', //display the box right to the target element (east)
                    expose: true,
                    overlayOpacity: 0.7
                },

                <?php if($board_filter){ ?>
                {
                    html: "<a href='#' class='tour_close close' onclick='pinsTour.stop();return false;'>&times;</a><div class='tour_middle'>For example, you're looking at pins from your <b><?php print "$board_filter"; ?></b> board right now.  Looking good! </div><div class='tour_bottom'><a href='#' class='tour_btn_left' onclick='pinsTour.prev();return false;'><button class='btn'>Back</button></a><a href='#' class='tour_btn_right' onclick='pinsTour.next();return false;'><button class='btn btn-success'>Next</button></a></div>",
                    element: $('div.column-filter-widget-selected-terms'), //use the second (starting with 0) a tag within a li tag as target element
                    position: 'sw', //display the box right to the target element (east)
                    expose: true
                },
                <?php } ?>

                {
                    html: "<a href='#' class='tour_close close' onclick='pinsTour.stop();return false;'>&times;</a><div class='tour_top'>Choose a Time Period:</div><div class='tour_middle'> Explore your pinning activity from any part of the calendar.</div><div class='tour_bottom'><a href='#' class='tour_btn_left' onclick='pinsTour.prev();return false;'><button class='btn'>Back</button></a><a href='#' class='tour_btn_right' onclick='pinsTour.next();return false;'><button class='btn btn-success'>Next</button></a></div>",
                    element: $('div#date-section'), //use the second (starting with 0) a tag within a li tag as target element
                    position: 'se', //display the box right to the target element (east)
                    expose: true,
                    overlayOpacity: 0.7
                },{
                    html: "<a href='#' class='tour_close close' onclick='pinsTour.stop();return false;'>&times;</a><div class='tour_top'>Export Your Data:</div><div class='tour_middle'> Need to export your data?  No problem.  <br><br><ul><li><b>Copy</b> it into excel,</li><li><b>Export</b> a CSV file, or </li><li><b>Print</b> it straight out as a report</li></ul></div><div class='tour_bottom'><a href='#' class='tour_btn_left' onclick='pinsTour.prev();return false;'><button class='btn'>Back</button></a><a href='#' class='tour_btn_right' onclick='pinsTour.next();return false;'><button class='btn btn-success'>Next</button></a></div>",
                    element: $('div.DTTT'), //use the second (starting with 0) a tag within a li tag as target element
                    position: 'w', //display the box right to the target element (east)
                    expose: true,
                    overlayOpacity: 0.7
                },{
                    html: "<a href='#' class='tour_close close' onclick='pinsTour.stop();return false;'>&times;</a><div class='tour_top'>Need Help? </div><div class='tour_middle'> If you need anything else, we're always here to help.  <br><br><ul><li>Message us anytime using the <i class='icon-mail'></i> icon in the top-right corner, or </li><li>Email us directly at <a href='mailto:help@tailwindapp.com'>help@tailwindapp.com</a>.</li></ul></div><div class='tour_bottom'><a href='#' class='tour_btn_left' onclick='pinsTour.restart();return false;'><button class='btn'>Restart Tour</button></a><a href='#' class='tour_btn_right' onclick='pinsTour.stop();return false;'><button class='btn btn-success'>Got it!</button></a></div>",
                    element: $('.message_menu'),
                    position: 'se',
                    overlayOpacity: 0.7
                }

            ];



            //create tour for this page
            window.pinsTour = jTour(pinsTourData, options);

            if(!getCookie('tourplay4')) {
                setTimeout(function(){
                    pinsTour.start();
                }, 2000);
            }

            $('#start_tour').click(function(){
                pinsTour.restart();
                return false;
            });

            function getCookie(cookieName) {
                var cookiePattern = new RegExp('(^|;)[ ]*' + cookieName + '=([^;]*)'),
                    cookieMatch = cookiePattern.exec(document.cookie);
                if(cookieMatch){
                    return cookieMatch[2];
                }
                return 0;
            };

            function setCookie(cookieName, value, daysToExpire, path, domain, secure) {
                var expiryDate;

                if (daysToExpire) {
                    expiryDate = new Date();
                    expiryDate.setTime(expiryDate.getTime() + (daysToExpire * 8.64e7));
                }

                document.cookie = cookieName + '=' + (value.toString()) +
                    (daysToExpire ? ';expires=' + expiryDate.toGMTString() : '') +
                    ';path=' + (path ? path : '/') +
                    (domain ? ';domain=' + domain : '') +
                    (secure ? ';secure' : '');
            };



        });

    </script>

    <!--<ol id="your_pins_tour" style="display:none;">

	  <li data-button="Get Started">Let's take a quick tour of the new features at your fingertips..</li>

	  <li data-id="dynamic" data-options="tipLocation:top;tipAnimation:fade">This table has everything you've ever pinned</li>

	  <li data-class="datatable-header" data-options="tipAnimation:fade" data-button="Show Me More">You can <strong>Sort</strong> your pins by <i>any</i> column<br>
	  	<span>TIP: click on more than one column while holding the [shift] key to sort by multiple fields</span>
	  </li>

	  <li data-class="dataTables_filter" class="custom-class">You can also search for pins that meet any criteria you want, like having the same <i><strong class="muted">#hashtag</i></strong>, or being pinned from the same domain.  Just start typing and watch your results appear <i>instantly.</i> </li>

	  <li data-class="column-filter-widget" class="custom-class">You can even filter by specific boards and categories... </li>

	  <li data-class="filter-term" class="custom-class" data-button="A Few More Goodies..">For example, you're looking at pins from your <?php print "$board_filter"; ?> board right now.  Looking good! </li>

	  <li data-class="date-range-filter" class="custom-class">Narrowing down your pinning activity to a specific time-frame is a breeze.  </li>

	  <li data-class="dataTable-export" class="custom-class" data-button="Got it">Need to export your data?  No problem.  Copy it into excel, Export to CSV or even Print it straight out as a report.</li>

	   <li class="custom-class" data-button="Ok, I'm ready!">Ready to get started?  If you need anything else, we're always here to help.  Message us anytime using the <i class='icon-mail'></i> icon in the top-right corner, or email us directly at <a href="mailto:help@tailwindapp.com">help@tailwindapp.com</a>. </li>


	</ol>
-->

<?php } ?>




<?php

if(isset($is_profile) && $is_profile){ ?>




    <script src="/js/jtour/js/jTour.min.js"></script>
    <link rel="stylesheet" href="/js/jtour/css/theme2/style.css">

    <script>

        $(document).ready(function() {

            var options = {
                autoplay: false,
                pauseOnHover: false,
                scrollBack: true,
                showControls: false,
                axis: 'y',
                onStop: function(){
                    setCookie('first_tour', 1, 365);
                    <?php if(isset($cust_source) && $cust_source == "pinreach"){ ?>
                        setCookie('social_share', 1, 1);
                    <?php } ?>
                }
            };

            var options2 = {
                autoplay: false,
                pauseOnHover: false,
                scrollBack: true,
                showControls: false,
                axis: 'y',
                onStop: function(){
                    setCookie('social_share', 1, 90);
                }
            };




            var firstTourData = [

            <?php if(isset($cust_source) && $cust_source == "pinreach"){ ?>
                {
                    html: "<a href='#' class='tour_close close' onclick='socialTour.stop();return false;'>&times;</a><div class='tour_top'>Welcome to Tailwind!</div><div class='tour_middle'><span>Thank you so much for transitioning your account from PinReach to Tailwind!  We're so excited to have you here and can't wait to help you make the most out of Pinterest.</span><br><br> <span style='font-weight:bold;'>Connect with us</span> to stay in the loop and get in touch with the Tailwind team!<br><br> <a href='https://twitter.com/tailwindapp' class='twitter-follow-button' data-show-count='true'>Follow @TailwindApp</a> <br><br><a data-pin-do='buttonFollow' href='http://pinterest.com/tailwind'>Follow Tailwind</a> </div><div class='tour_bottom'> <span class='pull-right'><a href='#' class='tour_btn_right' onclick='firstTour.next();return false;'><button class='btn btn-success'>Take the Tour</button></a></span>     <span class='pull-right' style='margin-top:8px; margin-right:30px'><iframe src='//www.facebook.com/plugins/like.php?href=http%3A%2F%2Fwww.facebook.com%2FTailwind&amp;width=85&amp;height=21&amp;colorscheme=light&amp;layout=button_count&amp;action=like&amp;show_faces=false&amp;send=false&amp;appId=506257189449413' scrolling='no' frameborder='0' style='border:none; overflow:hidden; width:85px; height:21px;' allowTransparency='true'></iframe></span>     <span class='pull-right' style='margin-top:8px;'><a href='https://twitter.com/share' class='twitter-share-button' data-url='http://www.tailwindapp.com' data-text='Just created my free @TailwindApp Pinterest Analytics Dashboard!  Get yours at'>Tweet</a></span>   </div>",
                    position: 'c',
                    offset: {x:0,y:100},
                    expose: true,
                    overlayOpacity: 0.9,
                    onBeforeShow: function(){

                        //load twitter snippet for follow/tweet button
                        !function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');

                        //load pinterest js snippet for follow button
                        (function(d){
                            var f = d.getElementsByTagName('SCRIPT')[0], p = d.createElement('SCRIPT');
                            p.type = 'text/javascript';
                            p.async = true;
                            p.src = '//assets.pinterest.com/js/pinit.js';
                            f.parentNode.insertBefore(p, f);
                        }(document));
                    }
                },{
                    html: "<a href='#' class='tour_close close' onclick='firstTour.stop();return false;'>&times;</a><div class='tour_top'>Tailwind Tour  </div><div class='tour_middle'><span class='muted'>You can use your keyboard to control the tour: </span><br><span class='text-success'>[→] Next</span> , <span class='text-info'>[←] Previous</span> , <span class='muted'> [ESC] Exit </span></div><div class='tour_bottom'><a href='#' class='tour_btn_right' onclick='firstTour.next();return false;'><button class='btn btn-success'>Get Started</button></a></div>",
                    position: 'c',
                    expose: true,
                    overlayOpacity: 0.9
                },
            <?php } else { ?>
                {
                    html: "<a href='#' class='tour_close close' onclick='firstTour.stop();return false;'>&times;</a><div class='tour_top'>Let's take a quick tour..  </div><div class='tour_middle'><span class='muted'>You can use your keyboard to control the tour: </span><br><span class='text-success'>[→] Next</span> , <span class='text-info'>[←] Previous</span> , <span class='muted'> [ESC] Exit </span></div><div class='tour_bottom'><a href='#' class='tour_btn_right' onclick='firstTour.next();return false;'><button class='btn btn-success'>Get Started</button></a></div>",
                    position: 'c',
                    offset: {x:0,y:100},
                    expose: true,
                    overlayOpacity: 0.9
                },
            <?php } ?>
                {
                    html: "<a href='#' class='tour_close close' onclick='firstTour.stop();return false;'>&times;</a><div class='tour_top'>Your Navigation Panel</div><div class='tour_middle'>All of your reports are neatly organized in one place, making it easy to navigate to your most relevant data, fast.<br><br> <span style='font-weight:bold;'>NOTE:</span> Some reports look greyed out because they are only available on a premium plan.</div><div class='tour_bottom'><a href='#' class='tour_btn_left' onclick='firstTour.prev();return false;'><button class='btn'>Back</button></a><a href='#' class='tour_btn_right' onclick='firstTour.next();return false;'><button class='btn btn-success'>Next</button></a></div>",
                    element: $('div#menu, div#menu-content-scroll'),
                    position: 'e',
                    offset: {x:0,y:300},
                    expose: true,
                    overlayOpacity: 0.7
                },
            <?php if($cust_domain==""){ ?>
                {
                    html: "<a href='#' class='tour_close close' onclick='firstTour.stop();return false;'>&times;</a><div class='tour_top'>Add Your Domain</div><div class='tour_middle'>One of the first things you'll want to do is <span style='font-weight:bold;'>add your domain</span> (if you have one) so you can track anything people may be pinning from there.<br><br> Go to your \"Domain Insights\" report to add a domain and see what content is being pinned, as well as who is pinning it, in real-time!</div><div class='tour_bottom'><a href='#' class='tour_btn_left' onclick='firstTour.prev();return false;'><button class='btn'>Back</button></a><a href='#' class='tour_btn_right' onclick='firstTour.next();return false;'><button class='btn btn-success'>Next</button></a></div>",
                    element: $('div#menu, div#menu-content-scroll'),
                    position: 'e',
                    expose: true,
                    overlayOpacity: 0.7,
                    onBeforeShow: function(){

                        $("[data-target='#nav_menu_domain']").click();

                        $('#insights-nav-label').css({
                            'box-shadow': 'inset 0px -11px 39px -1px rgba(0, 0, 0, 0.5), inset 0px 0px 5px -1px rgba(0, 0, 0, 0.5);',
                            'background': 'url(\'/img/bgnoise_lg.png\');'
                        });
                        $('#insights-nav-label a').css({
                             'background-color': 'rgba(24, 172, 71, 0.498039);'
                        });
                    },
                    onBeforeHide: function(){

                        $("[data-target='#nav_menu_domain']").click();

                        $('#insights-nav-label').css({
                            'box-shadow': 'none',
                            'background': 'transparent'
                        });
                        $('#insights-nav-label a').css({
                            'background-color': 'transparent'
                        });
                    }
                },
            <?php } ?>
                {
                    html: "<a href='#' class='tour_close close' onclick='firstTour.stop();return false;'>&times;</a><div class='tour_top'>Dashboard Reports </div><div class='tour_middle'>Thanks to all the feedback from customers like you, we've recently rebuilt our dashboard reports from the ground up to make them:<br><br><ul><li><strong>Clearer,</strong></li> <li><strong>More Informative, and</strong></li><li><strong> More Actionable</strong></li></ul><br><span style='font-weight:bold;'>NOTE:</span> Some of your charts may not be available right away as it takes at least one day's worth of data to make them meaningful.  <br><br><span class='alert alert-success'><span style='font-weight:bold;'>Before you know it</span>, you'll be able to <span style='font-weight:bold;'>see trends</span>, <span style='font-weight:bold;'>benchmark your performance</span> and <span style='font-weight:bold;'>optimize your content strategy</span> as more and more of your historical data is archived each day.</div><div class='tour_bottom'><a href='#' class='tour_btn_left' onclick='firstTour.prev();return false;'><button class='btn'>Back</button></a><a href='#' class='tour_btn_right' onclick='firstTour.next();return false;'><button class='btn btn-success'>Next</button></a></div>",
                    element: $('div#accordion3'),
                    position: 'c',
                    offset: {x:-200,y:-300},
                    expose: true,
                    overlayOpacity: 0.8
                },{
                    html: "<a href='#' class='tour_close close' onclick='firstTour.stop();return false;'>&times;</a><div class='tour_top'>Your Settings</div><div class='tour_middle'>This is your Settings Menu.  <br><br>Just click on the <span><i class='icon-cog'></i></span> icon in the top-right to display it any time.  </div><div class='tour_bottom'><a href='#' class='tour_btn_left' onclick='firstTour.prev();return false;'><button class='btn'>Back</button></a><a href='#' class='tour_btn_right' onclick='firstTour.next();return false;'><button class='btn btn-success'>Next</button></a></div>",
                    element: $('#drop-settings'),
                    position: 'se',
                    offset: {x:0,y:250},
                    overlayOpacity: 0.2,
                    onBeforeShow: function(){
                        $('#drop-settings').css({
                            'background-color': 'rgba(24, 172, 71, 0.498039);'
                        });
                        $('#drop-settings').dropdown('toggle');
                    },
                    onBeforeHide: function(){
                        $('#drop-settings').css({
                            'background-color': 'transparent'
                        });
                        $('#drop-settings').dropdown('toggle');
                    }
                },{
                    html: "<a href='#' class='tour_close close' onclick='firstTour.stop();return false;'>&times;</a><div class='tour_top'>Help is always a click away! </div><div class='tour_middle'> If you have any questions at all, we're always here to help.  <br><br><ul><li>Find answers to the most commonly asked questions</li><li>Message us directly anytime, and </li><li>Make sure to signup for our onboarding webinar to learn how to make the most of Tailwind</li></ul><br>Alright, enough talking already.. <strong>time to explore your new dashboard!</strong></div><div class='tour_bottom'><a href='#' class='tour_btn_left' onclick='firstTour.restart();return false;'><button class='btn'>Restart Tour</button></a><a href='#' class='tour_btn_right' onclick='firstTour.stop();return false;'><button class='btn btn-success'>Lets Go!</button></a></div>",
                    element: $('#drop-help'),
                    position: 'se',
                    offset: {x:0,y:150},
                    overlayOpacity: 0.2,
                    onBeforeShow: function(){
                        $('#drop-help').css({
                            'background-color': 'rgba(24, 172, 71, 0.498039);'
                        });
                        $('#drop-help').dropdown('toggle');
                    },
                    onBeforeHide: function(){
                        $('#drop-help').css({
                            'background-color': 'transparent'
                        });
                        $('#drop-help').dropdown('toggle');
                    }
                }

            ];

            var socialTourData = [
                {
                    html: "<a href='#' class='tour_close close' onclick='socialTour.stop();return false;'>&times;</a><div class='tour_top'>Connect With Us</div><div class='tour_middle'>Stay in the loop and get in touch with the Tailwind team!<br><br> <a href='https://twitter.com/tailwindapp' class='twitter-follow-button' data-show-count='true'>Follow @TailwindApp</a> <br><br><a data-pin-do='buttonFollow' href='http://pinterest.com/tailwind'>Follow Tailwind</a> </div><div class='tour_bottom'> <span class='pull-right'><a href='#' class='tour_btn_left' style='margin-top:10px;color:#bbb;' onclick='socialTour.stop();return false;'>skip</a></span>     <span class='pull-right' style='margin-top:8px; margin-right:40px'><iframe src='//www.facebook.com/plugins/like.php?href=http%3A%2F%2Fwww.facebook.com%2FTailwind&amp;width=85&amp;height=21&amp;colorscheme=light&amp;layout=button_count&amp;action=like&amp;show_faces=false&amp;send=false&amp;appId=506257189449413' scrolling='no' frameborder='0' style='border:none; overflow:hidden; width:85px; height:21px;' allowTransparency='true'></iframe></span>     <span class='pull-right' style='margin-top:8px;'><a href='https://twitter.com/share' class='twitter-share-button' data-url='http://www.tailwindapp.com' data-text='Just created my free @TailwindApp Pinterest Analytics Dashboard!  Get yours at'>Tweet</a></span>   </div>",
                    position: 'c',
                    expose: true,
                    overlayOpacity: 0.9,
                    onBeforeShow: function(){

                        //load twitter snippet for follow/tweet button
                        !function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');

                        //load pinterest js snippet for follow button
                        (function(d){
                            var f = d.getElementsByTagName('SCRIPT')[0], p = d.createElement('SCRIPT');
                            p.type = 'text/javascript';
                            p.async = true;
                            p.src = '//assets.pinterest.com/js/pinit.js';
                            f.parentNode.insertBefore(p, f);
                        }(document));
                    }
                }

            ];



            //create tour for this page
            window.firstTour = jTour(firstTourData, options);
            window.socialTour = jTour(socialTourData, options2);

            if(!getCookie('first_tour')) {
                setTimeout(function(){
                    firstTour.start();
                }, 2000);
            } else if(!getCookie('social_share')) {
                setTimeout(function(){
                    socialTour.start();
                }, 2000);
            }

            $('#start_tour').click(function(){
                firstTour.restart();
                return false;
            });

        });

    </script>

<?php } ?>
