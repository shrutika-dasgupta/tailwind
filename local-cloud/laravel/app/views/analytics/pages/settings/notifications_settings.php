<link rel="stylesheet" href="/css/switch.css">
<script src="/js/switch.js"></script>

<?= $navigation; ?>

<h3>Email Notifications</h3>
<p class="muted">Emails will be sent to <?= $email; ?> </p>

<div class="row notifications-settings">

    <div class="span3">
        <ul id="myTab" class="nav nav-pills nav-stacked">
            <?php $xx = 0; ?>
            <?php foreach ($accounts as $account): ?>
                <li class="<?= ($xx == 0) ? 'active' : '' ?>">
                    <a href="#<?= $account['username']; ?>" data-toggle="tab">
                        <i class="icon-arrow-right-3 pull-right"></i>
                        <?= $account['username']; ?>
                    </a>
                </li>
                <?php $xx++; ?>
            <?php endforeach ?>
        </ul>

        <div class="well">
            <h5>Automated Reports Available!</h5>
            <p>
                Please contact us if you'd like to have CSV or PDF reports included with your daily,
                weekly or monthly summary email.
            </p>
            <a class="btn btn-info"
               href="mailto:will@tailwindapp.com?subject=I want CSV or PDF reports!&body=I <3 Tailwind and I think Will is awesome. I'd also like to get CSV reports with my emails :) - <?= $customer_name; ?>"
            >
                Yes please!
            </a>
        </div>
    </div>

    <div class="span9">
        <form method="POST" action="/settings/notifications/update">
            <div class="well">
                <div id="myTabContent" class="tab-content">

                    <?php $xx = 0; ?>
                    <?php foreach ($accounts as $account): ?>
                        <div class="tab-pane fade <?= ($xx == 0) ? 'active' : '' ?> in"
                             id="<?= $account['username']; ?>"
                        >
                            <?= $account['summary email settings']; ?>
                            <?= $account['statement settings']; ?>
                            <?= $account['profile alert settings']; ?>
                        </div>
                        <?php $xx++; ?>
                    <?php endforeach ?>
                </div>
            </div>

            <div class="well">
                <?= $subscription_settings; ?>
            </div>

            <div class="text-right">
                <input type="submit" class="btn btn-primary" value="Update preferences" />
            </div>
        </form>
    </div>
</div>
<div class="clearfix"></div>
