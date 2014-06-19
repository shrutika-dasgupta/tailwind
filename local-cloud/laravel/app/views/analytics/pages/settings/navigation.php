<ul class="nav nav-tabs">
    <li class="<?= $profile_class; ?>">
        <a href="/settings/profile">
            Your Profile
        </a>
    </li>
    <li class="<?= $account_class; ?>">
        <a href="/settings/accounts">
            Account
        </a>
    </li>
    <li class="<?= $competitors_class; ?>">
        <a href="/settings/competitors">
            Competitors
        </a>
    </li>
    <li id='add-collaborator-tab'
        class="<?= $collaborators_class; ?>"
        data-toggle='popover'
        data-container='body'
        data-placement='bottom'
        data-content='Invite your colleagues, co-workers and clients to access your dashboard
                    with their own personal login'>
        <a href="/settings/collaborators" class="<?= $collaborators_link_class; ?>">
            Collaborators
        </a>
    </li>
    <li class='<?= $notifications_class; ?>'
        data-toggle='popover'
        data-container='body'
        data-placement='bottom'
        data-content='Get periodic summaries of your Pinterest activity, and other valuable
                    notifications sent straight to your inbox!'>
        <a href="/settings/notifications">
            Notifications
            <span class='label label-important'>New!</span>
        </a>
    </li>
    <li class='<?= $analytics_class; ?>'>
        <a href="/settings/google-analytics">
            Google Analytics
        </a>
    </li>

    <?php if ($has_chargify) { ?>
        <li class="<?= $billing_class; ?>">
            <a class="<?= $billing_link_class; ?>"
                <?= $billing_tooltip; ?>
               href='/settings/billing'>
                Billing <?= $billing_tab_icon; ?>
            </a>
        </li>
    <?php } ?>

    <?php if ($show_upgrade) { ?>
        <li>
            <a href='/upgrade?ref=settings_tab'
               style='background:rgba(72,170,127,0.2);'>
                Upgrade
            </a>
        </li>
    <?php } ?>
</ul>
