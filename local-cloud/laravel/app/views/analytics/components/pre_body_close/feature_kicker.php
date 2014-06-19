<?php
/**
 * @author Alex
 * Date: 10/2/13 9:04 PM
 * 
 */
?>

<script>
    function fkVisitorData(){
        return {
            "email":"<?=$email;?>",
            "username":"<?=$username;?>",
            "name":"<?=$name;?>",
            "cust_id":"<?=$cust_id;?>",
            "account_id":"<?=$account_id;?>",
            "org_id":"<?=$org_id;?>",
            "plan":"<?=$plan;?>",
            "account_type":"<?=$account_type;?>",
            "website":"<?=$website;?>",
            "created_at":"<?=$created_at;?>",
            "organization":"<?=$organization;?>",
            "industry":"<?=$industry;?>",
            "followers":"<?=$followers;?>",
            "profile_pins":"<?=$profile_pins;?>",
            "trial_end_date":"<?=$trial_end_date;?>"
        }
    }
</script>

<script>
    (function() {
        var fks = document.createElement('script');
        fks.type = 'text/javascript';
        fks.async = true;
        fks.setAttribute("fk-userid","28");
        fks.setAttribute("fk-server","fkapp.herokuapp.com");
        fks.src = ('https:' == document.location.protocol ? 'https://':'http://') +
            'd1g3gvqfdsvkse.cloudfront.net/assets/featurekicker.js';
        var s = document.getElementsByTagName('script')[0];
        s.parentNode.insertBefore(fks, s);
    })();
</script>
