<?php
/*
* Logic on if/how to show account selector and "add account" button
* TODO: @Will move to controller
*/
?>
<li id="account-dropdown" class="dropdown">
    <div class="header-user-section dropdown-toggle <?= $multi_account_active_class;?>" data-toggle="dropdown">
        <div class="pull-left user-image-wrapper-beta"
             style="background: url('<?= $cust_image; ?>');background-size:41px,41px;">
        </div>
        <div class="pull-left name-text">
            <div>
                <?php echo $cust_first_name . " " . $cust_last_name; ?>
            </div>
            <div>
                <span class="label label-success"><?= $plan_badge; ?></span>
            </div>

        </div>
        <div class="pull-left">
            <?= $account_action; ?>
        </div>
    </div>
<!--</li>-->

<!--<div id="nav-account-select" class="">-->
    <?= $multi_account_section; ?>
<!--</div>-->
</li>

<div class="clearfix"></div>

<?= $multi_account_js; ?>