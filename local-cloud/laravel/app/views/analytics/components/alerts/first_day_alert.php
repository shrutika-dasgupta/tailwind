<?php
/**
 * @author Alex
 * Date: 8/28/13 11:03 PM
 *
 *  TODO: $show_first_day_alert: blank if its their first day, otherwise should be "no-show"
 *  TODO:                        to make it disappear
 *
 *  TODO: $cust_account_age_print: number of hours (or days) old the account is.  This should be
 *  TODO:                          a method in the User model to return the correct string
 *
 */
?>

<script type="text/javascript">
    var url = window.location.href;
    if (url.indexOf('?') > -1){
        url += '&refresh=1'
    }else{
        url += '?refresh=1'
    }

    $("#refresh-button").on('click', function(){
        window.location = url;
    });
</script>



<div class="alert <?= $show_first_day_alert;?>" style="margin-top:-25px;">
    <button class="close" data-dismiss="alert"
            style="border: 0; background-color: transparent;">×</button>
    <i class="icon-chef"></i> Our chefs are still in the kitchen cooking up your data.

    <a href='#' data-toggle='popover-click'
       data-trigger='click'
       data-container='body'
       data-original-title='<strong>Your data may still be processing</strong>'
       data-content='Since your account is only <?php echo $cust_account_age_print; ?>, some
           charts may appear empty until multiple days of data can be recorded to show you a trend.
           Historical data is not available from Pinterest at this time.  <br><br>If you find
           certain boards or pins missing, you can refresh your data by clicking here
           (may take a few minutes): <button id="refresh-button" class="btn"
           onClick="window.location.href = url;">Refresh Data</button>'
       data-placement='bottom'>
        <i class='icon-info-2'></i> Learn more
    </a>

</div>