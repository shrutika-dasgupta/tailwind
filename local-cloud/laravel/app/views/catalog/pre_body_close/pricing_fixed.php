<script type="text/javascript">

    $(document).ready(function(){

        if ($('.feature-grid').length) {
            var win = $(window);
            var nav = $('thead');
            var navTop = $('.feature-grid').length && $('#social-proof.spacer').offset().top -10;
            var main = $('.feature-grid');
            var newHeader = $('.fixed-pricing-header');
            var isFixed = 0;

            processScroller();


            win.on('scroll', processScroller);

            function processScroller() {


                var scrollTop = win.scrollTop();
                if (scrollTop >= navTop && !isFixed) {
                    isFixed = 1;
                    nav.addClass('thead-fixed');
                    main.addClass('offset');
                    newHeader.show();

                }
                else if (scrollTop <= navTop && isFixed) {
                    isFixed = 0;
                    nav.removeClass('thead-fixed');
                    main.removeClass('offset');
                    newHeader.hide();
                }



            }
        }
    });

    $(function(){

        var $win = $(window);
        var $buttonWrapper = $('.full-feature-button-wrapper');
        var $buttonInner = $('.full-feature-button');
        var buttonTop = $('.full-feature-button-wrapper').length && $('.full-feature-button-wrapper').offset().top + 60;
        var isFixed = 1;
        processScroll();

        $win.on('scroll', processScroll);

        function processScroll() {
            var i, scrollBottom = $win.scrollTop()+ $win.height();
            if (scrollBottom >= buttonTop && isFixed) {
                isFixed = 0;
                $buttonWrapper.removeClass('fixed');
                $buttonInner.removeClass('fixed');
//                upper.addClass('nav-top-offset');
//                logo.animate({'margin-left':'0px', 'opacity':'1'}, 250);


            }
            else if (scrollBottom <= buttonTop && !isFixed) {
                isFixed = 1;
                $buttonWrapper.addClass('fixed');
                $buttonInner.addClass('fixed');
//                logo.animate({'margin-left':'-40px', 'opacity':'0'}, 250);
//                button.animate({'opacity':'0'}, 100);



            }
        }
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
    var chargifyLiteGridLink = document.getElementById('chargify_lite_grid_link');
    var chargifyProGridLink = document.getElementById('chargify_pro_grid_link');

    addListener(chargifyLiteLink, 'mousedown', decorateMe);
    addListener(chargifyLiteLink, 'keydown', decorateMe);
    addListener(chargifyProLink, 'mousedown', decorateMe);
    addListener(chargifyProLink, 'keydown', decorateMe);
    addListener(chargifyLiteGridLink, 'mousedown', decorateMe);
    addListener(chargifyLiteGridLink, 'keydown', decorateMe);
    addListener(chargifyProGridLink, 'mousedown', decorateMe);
    addListener(chargifyProGridLink, 'keydown', decorateMe);

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