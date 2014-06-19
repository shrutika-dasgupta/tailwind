<?php
/**
 * @author Alex
 * Date: 5/6/14 3:21 PM
 * 
 */
?>

<script>
    $(document).ready(function() {

        $('#site-pins-toggle-dash').on('click', function(){
            $('#domain-daily-chart-div').removeClass('chart-hide-complete');
            $('.chart-upgrade').addClass('hidden');
        });

        $('#pinners-toggle-dash').on('click', function(){
            $('#domain-daily-chart-div').removeClass('chart-hide-complete');
            $('.chart-upgrade').addClass('hidden');
        });

        $('#reach-toggle-dash').on('click', function(){
            $('#domain-daily-chart-div').addClass('chart-hide-complete');
            $('.chart-upgrade').removeClass('hidden');
        });

    });
</script>