<?php

?>

<li active class="dropdown" style="font-size: 20px;">
    <a href="#"
       id="drop-add-items"
       role="button"
       class="dropdown-toggle"
       data-toggle="dropdown">

        <i class="icon-plus"></i>
        <b class="caret"></b>
    </a>

    <ul class="dropdown-menu" role="menu" aria-labelledby="drop-add-items">

        <li class="<?=$nav_show_add_account;?>"
            role="presentation"
            data-toggle='popover' data-container='body'
            data-placement='left'
            data-delay='{"show":500, "hide":0}'
            data-content='Use one login to access multiple accounts and
                seamlessly switch in a unified dashboard.'>
            <a role="menuitem" tabindex="-1"
                <?=$nav_add_account_link;?>
               class="">
                <?= $nav_add_account_locked; ?> Add New Account
            </a>
        </li>
        <li class="<?=$nav_add_user_show;?>"
            role="presentation"
            data-toggle='popover' data-container='body'
            data-placement='left'
            data-delay='{"show":500, "hide":0}'
            data-content='Invite your colleagues, co-workers or clients
                to access your dashboard with their own personal login.'>
            <a role="menuitem" tabindex="-1"
                <?=$nav_add_user_link;?>
               class="">
                <?= $nav_add_user_locked; ?> Add a Collaborator
            </a>
        </li>
        <li class="<?= $nav_show_add_competitors; ?>"
            role="presentation"
            data-toggle='popover'
            data-container='body'
            data-placement='left'
            data-content='Benchmark your progress with others in your
                industry and see how you stack up!'
            data-delay='{"show":500, "hide":0}'>
            <a role="menuitem" tabindex="-1"
               <?= $nav_add_competitor_link; ?>
               class="">
                <?= $nav_competitors_locked; ?> Add a Competitor
            </a>
        </li>

    </ul>
</li>


