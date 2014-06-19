<fieldset>

    <legend>Activity Alerts</legend>

    <div class="row">
        <div class="span5">
            <label for="<?= $username; ?>-alerts">Profile Alerts</label>

            <p class="muted">
                <small>
                    Profile alerts are sent when <?= $username; ?>'s activity on Pinterest spikes.
                    This can range from repins to follower counts.
                </small>
            </p>
        </div>

        <div class="span2 pull-right align-right">
            <div class="make-switch switch-small">
                <input type="checkbox"
                       name="<?= $username;?>-alerts"
                       id="<?= $username;?>-alerts"
                       value="on"
                       <?= $alerts_report_checked; ?>
                />
            </div>
        </div>
    </div>

    <div class="row">
        <div class="span5">
            <label for="<?= $username; ?>-domain_alerts">Domain Alerts</label>

            <p class="muted">
                <small>
                    Domain alerts are sent when pins from your domain (for this account) are pinned.
                </small>
            </p>
        </div>

        <div class="span2 pull-right align-right">
            <div class="make-switch switch-small">
                <input type="checkbox"
                       name="<?= $username;?>-domain_alerts"
                       id="<?= $username;?>-domain_alerts"
                       value="on"
                       <?= $domain_alerts_report_checked; ?>
                />
            </div>
        </div>
    </div>

</fieldset>