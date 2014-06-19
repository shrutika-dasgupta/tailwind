<?php
/**
 * @author Alex
 * Date: 8/1/13 1:54 AM
 * 
 */

?>

<script>
    $(document).ready(function(){
        $('.clickable').click(function(){
            $('.add-user-form', this).show();
            $('.add-user-row', this).hide();
            $('.add-user-alert', this).show();
        });

        <?=$action_alert;?>
    })
</script>