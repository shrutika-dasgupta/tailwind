<script type="text/javascript">

    jQuery(document).ready(function($){

        $("[data-toggle='tooltip']").tooltip({html: true, trigger:'hover'});
        $("[data-toggle='popover']").popover({html: true, animation: false, trigger: 'hover'});
        $("[data-toggle='popover-click']").popover({html: true, animation: false, trigger: 'click'});


        $('body').on('click', function (e) {
            $('[data-toggle="popover-click"]').each(function () {
                //the 'is' for buttons that trigger popups
                //the 'has' for icons within a button that triggers a popup
                if (!$(this).is(e.target) && $(this).has(e.target).length === 0 && $('.popover').has(e.target).length === 0) {
                    $(this).popover('hide');
                }
            });
        });


        $("div.popover.right.in").addClass('in-front');

        $('a[data-toggle="tab"]').on('show', function () {
            $('span:regex(class,embed_board_bd$)').attr('style', function(i,s) { return s + 'overflow-y: scroll !important;box-shadow: inset 0px 0px 20px rgba(0, 0, 0, 0.1);' });
        });

//        $(".daterangepicker .ranges li.disabled").append("<i class='icon-lock'></i>");
    });

</script>