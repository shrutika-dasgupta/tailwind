<?php

/*
 * Add collaborator logic
 *
 * TODO: move to controller
 */

if($cust_is_admin!="V"){ ?>
    <li id="add-collaborator-button"
        data-toggle='popover' data-container='body'
        data-placement='bottom'
        data-content='Invite your colleagues, co-workers or clients
        to access your dashboard with their own personal login.'>
        <a
            <?=$nav_add_user_link;?>
            class="btn <?=$nav_add_user_show;?>"
            style="margin-right:10px;">
            <i class='icon-plus'></i> Add a Collaborator
        </a>
    </li>
<?php }	?>