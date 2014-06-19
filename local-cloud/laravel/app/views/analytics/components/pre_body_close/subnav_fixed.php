<script type="text/javascript">

    $(document).ready(function(){

        if ($('.navbar-report-subnav').length) {
            var win = $(window);
            var nav = $('.navbar-report-subnav');
            var navTop = $('.navbar-report-subnav').length && $('.navbar-fixed-top').offset().top -10;
            var main = $('#main');
            var isFixed = 0;
            console.log(navTop);

            processScroller();


            win.on('scroll', processScroller);

            function processScroller() {


                var scrollTop = win.scrollTop();
                if (scrollTop >= navTop && !isFixed) {
                    isFixed = 1;
                    nav.addClass('fixed-subnav');
                    main.addClass('offset');

                }
                else if (scrollTop <= navTop && isFixed) {
                    isFixed = 0;
                    nav.removeClass('fixed-subnav');
                    main.removeClass('offset');
                }

            }
        }
    });
</script>