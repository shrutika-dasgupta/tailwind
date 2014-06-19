<script type="text/javascript">


    $(document).ready(function () {

        $("tr").hover(function(){
            $(".progress-footprint .bar", this).toggleClass("active");
        });

        $('*[data-ajaxload]').bind('click',function(){

            $(this).text('');
            $(this).append('<img id="loaderImage" src="/img/loading-small.gif">');
            var e=$(this);
            var cat = $(this).data('category');
            var name = $(this).data('name');
//            e.unbind('click');
            $.get(e.data('ajaxload'),function(d){
                e.popover({
                    content: d,
                    html: true,
                    placement: 'bottom',
                    trigger: 'click',
                    title: name + '\'s Boards in the <strong>' + cat + ' </strong> category:',
                    container: '#main-content-scroll',
                    template: '<div class="popover awesome-popover-class"><div class="arrow"></div><div class="popover-inner"><h3 class="popover-title"></h3><div class="popover-content"><p></p></div></div></div>'
                }).popover('show');
                $('#loaderImage').remove();
                e.text(cat);
            });
            //$('.category-loading',this).hide();
        });

        $(document).click(function (e) {
            $('.awesome-popover-class').each(function () {
                if (!$(this).is(e.target) && $(this).has(e.target).length === 0) {
                    $(this).remove(); // this hides popover, but content remains
                    return;
                }
            });
        });

    });



</script>

<script type="text/javascript">
    (function(d){
        var f = d.getElementsByTagName('SCRIPT')[0], p = d.createElement('SCRIPT');
        p.type = 'text/javascript';
        p.async = true;
        p.src = '//assets.pinterest.com/js/pinit.js';
        f.parentNode.insertBefore(p, f);
    }(document));
</script>

<script>
    $(document).ready(function() {
        $('.dropdown.pinner-connect').click(function(){
            $('a.pinner-tw', this).addClass('twitter-follow-button');
            !function(d,s,id){
                var js,fjs=d.getElementsByTagName(s)[0];
                if(!d.getElementById(id)){
                    js=d.createElement(s);
                    js.id=id;js.src="//platform.twitter.com/widgets.js";
                    js.async = true;
                    fjs.parentNode.insertBefore(js,fjs);
                }
            }(document,"script","twitter-wjs");
        });
    });

</script>