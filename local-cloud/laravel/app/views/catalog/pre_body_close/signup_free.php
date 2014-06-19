<script src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
<script src="/js/vendor/cycle.js"></script>
<script type="text/javascript">

    var showTab = function (tab) {
        $('.tabable').hide();
        $('.' + tab).show();
        $('.advice-navigation a').removeClass('active');
        $('.' + tab + '-nav').addClass('active');


    }

    $(document).ready(function () {

        var pins = [];

        for (var i = 0; i < 154; i++) {
            pins.push('#pin' + i);
        }

        setTimeout(function () {
            $('#tour').fadeIn('slow');
            $('.skip').hide();
        }, 10);

        var pinRain = setInterval(function () {
            if (pins.length != 0) {
                var index = Math.floor(Math.random() * pins.length);
                $(pins[index]).css('visibility', 'visible').hide().fadeTo("slow", 0.43);
                pins.splice(index, 1);
                console.log(index);
            }
        }, 200);

        $('.cycle-slideshow').eq(0).on( 'cycle-finished', function() {
            //$(".loading").css('display','none').hide();
            //$(".loading-done").css('visibility', 'visible').show().fadeTo("slow", 1);
            $("input").eq(2).focus();
            $("i.icon-angle-right").css('visibility','visible').fadeTo("slow",1)
        });

        $('.skip').on('click',function(){
            $('#tour').fadeIn('slow');
            $('.skip').hide();
            $("input").eq(2).focus();
        })

    });

</script>
