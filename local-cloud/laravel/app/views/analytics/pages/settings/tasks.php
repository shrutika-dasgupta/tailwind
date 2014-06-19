<div id="task-list">

    <a href="/settings/profiles/refresh" class="btn pull-right">
        Refresh
    </a>

    <h2>Complete Your Profile</h2>

    <p>
       The most successful brands on Pinterest take
        advantage of every opportunity the platform provides. We recommend you
        follow these steps to make the most of your account.
    </p>

    <div class="progress">
        <div class="bar"
             style="width: <?= $completeness_percentage; ?>%;"></div>
    </div>

    <ul class="block-grid-4">
        <?php foreach ($tasks as list($class, $content)) { ?>
            <li class="<?= $class; ?>">
                <div class="module widget">
                    <?= $content; ?>
                </div>
            </li>
        <?php } ?>
    </ul>
    <div style="clear: both"></div>
</div>
