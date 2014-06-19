<?php
/**
 * @author Alex
 * Date: 10/2/13 11:54 AM
 * 
 */

?>

<script>
    $(function(){

        var $win = $('#main-content-scroll');
        var $buttonWrapper = $('.full-feature-button-wrapper');
        var $buttonInner = $('.full-feature-button');
        var buttonTop = $('.full-feature-button-wrapper').length && $('.full-feature-button-wrapper').offset().top + 118;
        var button = $('#feature-toggle');
        var isFixed = 1;
        processScroll();

        $win.on('scroll', processScroll);

        function processScroll() {
            var i, scrollBottom = $win.scrollTop() + $win.height();
            if (scrollBottom >= buttonTop && isFixed) {
                isFixed = 0;
                $buttonWrapper.removeClass('fixed');
                $buttonInner.removeClass('fixed');
                button.removeClass('fixed');
//                upper.addClass('nav-top-offset');
//                logo.animate({'margin-left':'0px', 'opacity':'1'}, 250);


            }
            else if (scrollBottom <= buttonTop && !isFixed) {
                isFixed = 1;
                $buttonWrapper.addClass('fixed');
                $buttonInner.addClass('fixed');
                button.addClass('fixed')
//                logo.animate({'margin-left':'-40px', 'opacity':'0'}, 250);
//                button.animate({'opacity':'0'}, 100);



            }
        }
    })
</script>

<script>
    $(document).ready(function(){

        $("#main").css("left","60px");

        $('#menu-content-scroll ul').hover(
            function(){
                $('#main').css("left","270px");
            },
            function(){
                $('#main').css("left","60px");
            }
        );

        $(".price-badge").hover(
            function(){
                $(this).find("i.icon-help").css("visibility","visible");
            },
            function(){
                $(this).find("i.icon-help").css("visibility","hidden");
            }
        );
    });

    /**
     * Create Google Analytics Cross-Domain Links right before they are clicked,
     * so that the linker id does not go stale (it only lasts 2 minutes)
     *
     * Reference: https://developers.google.com/analytics/devguides/collection/analyticsjs/cross-domain#decoratelinks
     */
    var linker;

    var chargifyLiteLink = document.getElementById('chargify_lite_link');             // Add event listeners to link.
    var chargifyProLink = document.getElementById('chargify_pro_link');

    addListener(chargifyLiteLink, 'mousedown', decorateMe);
    addListener(chargifyLiteLink, 'keydown', decorateMe);
    addListener(chargifyProLink, 'mousedown', decorateMe);
    addListener(chargifyProLink, 'keydown', decorateMe);

    function decorateMe(event) {
        event = event || window.event;                            // Cross browser hoops.
        var target = event.target || event.srcElement;

        if (target && target.href) {                              // Ensure this is a link.
            ga('linker:decorate', target);
        }
    }

    // Cross browser way to listen for events.
    function addListener(element, type, callback) {
        if (element.addEventListener) element.addEventListener(type, callback);
        else if (element.attachEvent) element.attachEvent('on' + type, callback);
    }

</script>

