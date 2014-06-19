<?php
/**
 * @author Alex
 * Date: 10/2/13 11:54 AM
 * 
 */

?>

<script>
    $(document).ready(function(){

        $("#main").css("left","60px");

        if(!$('#main').hasClass('slid')) {

            $('#menu-content-scroll, #menu-top-toolbar, #menu-bottom, #menu-inner').hover(
                function(){
                    $('#main').css("left","270px");
                },
                function(){
                    if (!$('#main').hasClass('slid')) {
                        $('#main').css("left","60px");
                    }
                }
            );
        } else {

        }

    });

</script>

