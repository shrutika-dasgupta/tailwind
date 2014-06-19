<?php



/*
 * Show error notification if there was an issue with the invitation
 */
if(isset($action)){
    if ($action=="invite_not_found" || $action=="token_error" || $action=="parameters_not_set") {
        ?>


    <?php
    } else if($action=="accept" && isset($authorized) && $authorized){ ?>

    <?php
    }  else { ?>



<?php
    }
} else {
?>



<?php
}