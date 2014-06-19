<?= $analytics_navigation; ?>
<?= $alert; ?>
<?php
//check for google analytics profile already synced
if (hasAnalytics($cust_account_id, $conn)) {
    $analytics = new GoogleAnalytics(getAnalyticsToken($cust_account_id, $conn));
    $analytics->setProfile(getAnalyticsProfile($cust_account_id, $conn));
} else {
    $analytics = null;
}

if (isset($_GET['e']) == 5) {
    print "<div class=\"alert alert-error\">";
    print "<strong>Whoops!  Something went wrong.</strong> There was an error authenticating with Google.  Please try again.  If the problem persists, please contact us by clicking the <i class='icon-help'></i> in the upper-right corner of the screen.  Sorry about the inconvenience!";
    print "</div>";
}
if ($analytics == null) {
    print "
    <div class=\"row\">";
    print "<div class='span6'>";


        if (isset($_GET['success']) == 3) {
            print "<div class=\"alert alert-success\">";
            print "<strong>One more step!</strong> Click the button below to re-sync and try a different Google Analytics account.";
            print "</div>";
        } else if(isset($_GET['success']) == 5) {
            print "<div class=\"alert alert-success\">";
            print "<strong>Hooray!</strong> Your Google Analytics profile has been successfully updated!";
            print "</div>";
        } else {
            print "
            <div class=\"alert alert-error\">
            Your Google Analytics account has not yet been synced with Tailwind.
            </div>";

            print "Integrating Google Analytics takes less than 30 seconds! Get started by clicking the button below.<br/><br/>";
        }
    ?>
    <a target='_blank'
        href="https://www.google.com/accounts/AuthSubRequest?next=<?= $return_url; ?>&scope=https://www.google.com/analytics/feeds/&secure=0&session=1">
        <?
    if (isset($_GET['success']) == 3) {
    print "
    <button class=\"btn btn-large btn-primary\">Re-Sync Google Analytics</button>";
    } else {
    print "
    <button class=\"btn btn-large btn-primary\">Integrate Google Analytics</button>";
    }
    print "</a>";

    print "</div>";
    print "<div class='span4'>";
        print "<h3>Why Sync Google Analytics?</h3><br/>";
        print "<p>
            Syncing your Google Analytics account will add these additional features:</p>

        <ul>
            <li>Traffic Metrics</li>
            <li>Ecommerce Transactions</li>
            <li>Revenue attributed to Pins and Pinners</li>
            <li>Improved Data Accuracy</li>
        </ul>";

        print "<p>All information is kept strictly <u>private</u> and is only available directly to you.</p>";
        print "</div>";
    print "</div><br/>";
} else {
    $json = $analytics->call("https://www.googleapis.com/analytics/v3/management/accounts/~all/webproperties/~all/profiles");

    $data = json_decode($json);
    print "
    <div class=\"row\">";
    print "<div class='span6'>";
        if ($analytics->profile == null) {
            print "
            <div class=\"alert alert-info\">
            Select one of the profiles below to complete the Google Analytics syncing process.
            </div>";
        }
        if ($analytics->profile != null) {
        print "
        <div class=\"alert alert-success\">
        <strong>Hooray!</strong>  Your Google Analytics profile is synced!
        </div>";
        }
print "<h4>Profiles</h4>";
print "Which profile would you like to track for Pinterest traffic and revenue?<br/><br/>";

print "<form action='/settings/google-analytics/select-profile' method='POST'>
    <input type='hidden' name='tab' value='Analytics'>
    <input type='hidden' name='attribute' value='analytics_profile'>";

    $set_profile = getAnalyticsProfile($cust_account_id, $conn);

    foreach($data->items as $d) {
    $title = $d->websiteUrl;
    $accountName = $d->name;
    $tableId = "ga:" . $d->id;

    print "
    <div class=\"row-fluid\" style='margin-left: 10px;'>";
    print "<label class=\"radio\">";
    print "
    <div class=\"span1\" style='width:10px; text-align: center; padding-top: 9px;'>";
    if ($tableId == $set_profile) {
    $checked = "CHECKED";
    } else {
    $checked = "";
    }
    print "<input type=\"radio\" name=\"ga_profile\" value=\"$tableId\" $checked>";
    print "
    </div>";

    print "
    <div class=\"span3\">";
    print "
    <b>$accountName</b><br/>
    $title<br/>";
    print "
    </div>";
    print "</label>";
    print "</div><br/>";
    }

    if (count($data->items) == 0) {
    print "
    <div class=\"row\">";
    print "
    <div class=\"span6\">";
    print "<div class='alert alert-error'>No analytics profiles were found for this analytics account.  Please make sure your Google Analytics profile is fully setup first, and try syncing again. </div>";
    print "
    </div>";
    print "</div>";
    }

    print "
    <div class=\"row\">";
    print "
    <div class=\"span6\">";
    print "
    <div class=\"form-actions\">
    <button type=\"submit\" class=\"btn btn-primary\">Save changes</button>
    </div>";
    print "
    </div>";

    print "</div><br/>
</form>";

print "</div>";

print "<div class='span4'>";
    print "<h3>Help</h3><br/>";
    print "
    <p>
        Please select which profile you would like to use for tracking your Pinterest traffic and revenue.
    </p>";
    print "</div>";
print "</div>";

print "
<div class=\"row\">";
print "
<div class=\"span6\">";
print "<h3>Not the correct account?</h3>
Use the button below to re-sync your Google Analytics with new traffic and revenue data. You will not lose any historical data that has already been collected.<br/><br/>";

print "
<form action='/settings/google-analytics/resync' method='POST'>
    <input type='hidden' name='tab' value='Analytics'>
    <input type='hidden' name='resync' value='1'>
    <button type=\"submit\" class=\"btn btn-info\">Re-Sync Google Analytics</button>
</form>";
print "
</div>";
print "</div>";
}