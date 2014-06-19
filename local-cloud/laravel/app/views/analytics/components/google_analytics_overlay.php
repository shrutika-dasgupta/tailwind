<?php
/**
 * @author Alex
 * Date: 4/29/14 8:50 PM
 * 
 */

if (!$has_analytics): ?>

    <div class='report-overlay'>
        <div class='report-loading' style='text-align:center'>
                <h1>Please Sync your Google Analytics to enable this report!</h1>
                <h3 class='muted'>Looks like there's no Google Analytics associated with this account.</h3>
                <br>
                <a class='btn btn-large btn-success' href='/settings/google-analytics'>Sync Google Analytics →</a>
                <br>
                <br>
                <a class="btn" href="javascript:history.back()">← Go Back</a>
                <hr>
        </div>
    </div>

<?php elseif (!$analytics_ready): ?>

    <div class='report-overlay'>
        <div class='report-loading' style='text-align:center'>
            <img src='/img/loading.gif'><br>
            <h1>Your Traffic & Revenue data is on its way!</h1>
            <br>
            <h3 class='muted'>If you just recently synced your Google Analytics profile, please give us at least a few hours to begin processing all of your data :)</h3>
            <br>
            <br>
            <a class="btn" href="javascript:history.back()">← Go Back</a>
            <hr>
        </div>
    </div>

<?php else: ?>
    <?php if (!$analytics_profile): ?>

        <div class='report-overlay'>
            <div class='report-loading' style='text-align:center'>
                    <h1>Your Google Analytics Account is Synced!</h1>
                    <h3 class='muted'>Please choose the appropriate Google Analytics Profile to complete your integration process.</h3>
                    <br>
                    <a class='btn btn-large btn-success' href='/settings/google-analytics'>Go Choose Google Analytics Profile →</a>
                    <br>
                    <hr>
            </div>
        </div>
    <?php else: ?>

    <?php endif ?>
<?php endif ?>