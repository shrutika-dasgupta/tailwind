<fieldset>

    <legend>Profile Summaries</legend>

    <div class="row">
        <div class="span5">
            <label for="<?= $username; ?>-time">
                Delivery time  <small>(Central Standard Time)</small>
            </label>
            <p class="muted">
                <small>
                    Please select a time for your email to be sent. Due to the nature of email, we
                    cannot guarantee that the email will arrive at this exact time.
                </small>
            </p>
        </div>

        <div class="span2 pull-right">
            <select name="<?= $username; ?>-time" id="<?= $username; ?>-time" class="span2">
                <option value="<?= $time_value; ?>"><?= $time; ?></option>
                <option value="0000">Midnight</option>
                <option value="0100">1:00AM</option>
                <option value="0200">2:00AM</option>
                <option value="0300">3:00AM</option>
                <option value="0400">4:00AM</option>
                <option value="0500">5:00AM</option>
                <option value="0600">6:00AM</option>
                <option value="0700">7:00AM</option>
                <option value="0800">8:00AM</option>
                <option value="0900">9:00AM</option>
                <option value="1000">10:00AM</option>
                <option value="1100">11:00AM</option>
                <option value="1200">Noon</option>
                <option value="1300">1:00PM</option>
                <option value="1400">2:00PM</option>
                <option value="1500">3:00PM</option>
                <option value="1600">4:00PM</option>
                <option value="1700">5:00PM</option>
                <option value="1800">6:00PM</option>
                <option value="1900">7:00PM</option>
                <option value="2000">8:00PM</option>
                <option value="2100">9:00PM</option>
                <option value="2200">10:00PM</option>
                <option value="2300">11:00PM</option>
            </select>
        </div>
    </div>

    <div class="row">
        <div class="span5">
            <label for="<?= $username; ?>-profile-daily">Daily Summary Email</label>

            <p class="muted">
                <small>
                    The daily summary email will give you stats about <?= $username; ?>'s follower,
                    repins and organic pin counts from the last 24 hours.
                </small>
            </p>
        </div>


        <div class="span2 pull-right align-right">
            <div class="make-switch switch-small">
                <input type="checkbox"
                       name="<?= $username; ?>-daily_stats_report"
                       id="<?= $username; ?>-profile-daily"
                       value="daily"
                       <?= $daily_report_checked; ?>
                />
            </div>
        </div>
    </div>

    <div class="row">
        <div class="span5">
            <label for="<?= $username; ?>-profile-weekly">Weekly Summary Email</label>

            <p class="muted">
                <small>
                    The weekly summary email is delivered on monday every week.
                </small>
            </p>
        </div>

        <div class="span2 pull-right align-right">
            <div class="make-switch switch-small">
                <input type="checkbox"
                       name="<?= $username; ?>-weekly_stats_report"
                       id="<?= $username; ?>-profile-weekly"
                       value="daily"
                       <?= $weekly_report_checked; ?>
                />
            </div>
        </div>
    </div>

    <div class="row">
        <div class="span5">
            <label for="<?= $username; ?>-profile-monthly">Monthly Summary Email</label>

            <p class="muted">
                <small>
                    The monthly summary email is sent on the first monday of the month.
                </small>
            </p>
        </div>

        <div class="span2 pull-right align-right">
            <div class="make-switch switch-small">
                <input type="checkbox"
                       name="<?= $username; ?>-monthly_stats_report"
                       id="<?= $username; ?>-profile-monthly"
                       value="daily"
                       <?= $monthly_report_checked; ?>
                />
            </div>
        </div>
    </div>

</fieldset>

<?php /*
  *
  *
  *
  *            <div class="span2">
                <label for="<?= $username; ?>-profile-weekly">Weekly</label>

                <div class="make-switch switch-small">
                    <input type="checkbox"
                           name="<?= $username; ?>-weekly_stats_report"
                           id="<?= $username; ?>-profile-weekly"
                           value="weekly"
                        <?= $weekly_report_checked; ?>/>
                </div>
            </div>

            <div class="span2">
                <label for="<?= $username; ?>-profile-monthly">Monthly</label>

                <div class="make-switch switch-small">
                    <input type="checkbox"
                           name="<?= $username; ?>-monthly_stats_report"
                           id="<?= $username; ?>-profile-monthly"
                           value="monthly"
                        <?= $monthly_report_checked; ?>/>
                </div>
            </div>

        </div>

    </div>

</div>
  *
  *
<h4>
    Attached reports
</h4>
<p> The following reports can be attached in Microsoft excel compatible .csv files or in a pdf.</p>
<div class="alert-info alert">
    <i class="icon-info"></i> These reports are currently unavailable via email. If you like to
    enable them
    for your account, please contact us.
</div>
<? foreach ($attachments as $attachment) { ?>
    <div class="span3">
        <label for="<?= $username; ?>-<?= $attachment->name; ?>-csv">
            <?= $attachment->title; ?>
        </label>


        <div class="make-switch switch-mini"
             data-text-label="csv">
            <input type="checkbox"
                   id="<?= $username; ?>-<?= $attachment->name; ?>-csv"
                   name="<?= $username; ?>-<?= $attachment->name; ?>-csv"
                   value="1"
                <?= $attachment->csv_checked; ?>
                >
        </div>
        <br/>

        <div class="make-switch switch-mini"
             data-text-label="pdf"
            >
            <input type="checkbox"
                   id="<?= $username; ?>-<?= $attachment->name; ?>-pdf"
                   name="<?= $username; ?>-<?= $attachment->name; ?>-pdf"
                   value="1"
                <?= $attachment->pdf_checked; ?>
                >
        </div>
    </div>
<? } ?>
 */
?>
