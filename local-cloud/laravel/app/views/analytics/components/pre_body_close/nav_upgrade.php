<?php
/**
 * @author Alex
 * Date: 10/2/13 10:48 PM
 * 
 */

?>

<script>

    $(document).ready(function () {
        $("div#menu-content-scroll li.go-pro").hover(
            function () {
                $(this).fadeTo(200,1);
                $("a", this).css('background','rgba(24, 172, 71, 0.5)');
                $(".menu-icon-left", this).fadeTo(100,0.9);
                $(".menu-icon-left", this).css({
                    'color':'#fff',
                    'text-shadow': '0px 0px 7px rgba(0, 0, 0, 0.8)'
                });
                $(".menu-title", this).fadeTo(100,0.05);
                $(".menu-icon-right", this).fadeTo(100,0.05);
                $(".menu-overlay", this).fadeTo(100,0.9);
            },
            function () {
                $(this).fadeTo(100,0.4);
                $("a", this).css('background','transparent');
                $(".menu-icon-left", this).fadeTo(100,1);
                $(".menu-icon-left", this).css({
                    'color':'#000',
                    'text-shadow': '0px -5px 12px #eee, 0 0 0 #000, 1px 1px 1px rgba(255,255,255,0.4)'
                });
                $(".menu-title", this).fadeTo(100,1);
                $(".menu-icon-right", this).fadeTo(100,1);
                $(".menu-overlay", this).fadeTo(100,0);
            }
        );
        $("div.navbar-report-subnav li.go-pro a").append("<i class='icon-lock'></i>");
        $("#menu-content-scroll li.go-pro a span.menu-icon-right").append("<i class='icon-lock'></i>");
        $("#menu-content-scroll li.go-pro a span.menu-icon-right i").css('color','#555');
        $("div.section-header .btn.go-pro").append("<i class='icon-lock'></i>");
    });
</script>