<li active class="dropdown" style="font-size: 20px;">
    <a href="#"
       id="drop-settings"
       role="button"
       class="dropdown-toggle"
       data-toggle="dropdown">

        <i class="icon-cog"></i>
        <b class="caret"></b>
    </a>

    <ul class="dropdown-menu" role="menu" aria-labelledby="drop-settings">
        <li role="presentation">
            <a role="menuitem" tabindex="-1"
               href="/settings/profile">
                Your Profile
            </a>
        </li>

        <li role="presentation">
            <a role="menuitem" tabindex="-1"
               href="/settings/accounts">
                Account Settings
            </a>
        </li>

        <li role="presentation">
            <a role="menuitem" tabindex="-1"
               href="/settings/notifications">
                Email Preferences
            </a>
        </li>

        <li role="presentation" class="divider"></li>

        <li role="presentation">
            <a role="menuitem" tabindex="-1"
               href="/upgrade?ref=main_menu__<?= $page; ?>">
                <?php if ($cust_plan > 1) {
                    echo "Change Plan";
                } else {
                    echo "Go <span class='label'>PRO</span>";
                } ?>
            </a>
        </li>

    <?php if ($has_credit_card_on_file) { ?>
        <li role="presentation">
            <a role="menuitem" tabindex="-1"
               href='/settings/billing'>
                Billing
            </a>
        </li>
    <?php } ?>

        <li role="presentation" class="divider"></li>

        <li role="presentation">
            <a role="menuitem" tabindex="-1"
               href="/settings/google-analytics">
                Sync Google Analytics
            </a>
        </li>

        <?php
        if ($nav_competitors_enabled){
            if ($has_competitors) { ?>
            <li role="presentation">
                <a role="menuitem" tabindex="-1"
                   href="/settings/competitors">
                    Manage Competitors
                </a>
            </li>
        <?php } else { ?>
            <li role="presentation">
                <a role="menuitem" tabindex="-1"
                   href="/settings/competitors">
                    Add a Competitor
                </a>
            </li>
        <?php }
        } ?>
    </ul>
</li>
