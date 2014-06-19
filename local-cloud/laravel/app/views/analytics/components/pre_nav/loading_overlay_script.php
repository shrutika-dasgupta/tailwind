<?php
/**
 * @author Alex
 * Date: 8/28/13 9:55 PM
 *
 * TODO: URL for AJAX call needs to be updated to whatever we set in the routes
 *
 * TODO: @Will Do we need to pass the cust_id via AJAX to get the right response?  Or will the ajax controller "just know"?
 */
?>

<script type='text/javascript'>

    function getData() {
        var urlRefresh = window.location.href;
        if (urlRefresh.indexOf('?') > -1){
            urlRefresh = urlRefresh.replace('&refresh=1', '');
            urlRefresh = urlRefresh.replace('?refresh=1', '');
        }

        $.ajax({
            type: 'GET',
            url: '/ajax/check-new-user-data',
            data: {'id':'<?php echo $cust_user_id;?>'},
            dataType: 'html',
            success: function(data){

                if ($(data).filter('.status').text()==1){
                    window.location = urlRefresh;
                } else {
                    setTimeout('getData()', 3000);
                }
            }
        });
    }

    $(document).ready(function () {
        getData();
    });

</script>