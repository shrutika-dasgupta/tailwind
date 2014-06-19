<?= $navigation; ?>
<?php

if (isset($_GET['success'])) {

//check for successful action
    if ($_GET['success'] == 1) {
        print "<div class=\"alert alert-success\">";
        print "Competitor Added!";
        print "</div>";
    } else if ($_GET['success'] == 2) {
        print "<div class=\"alert alert-success\">";
        print "Competitor was Deleted.";
        print "</div>";
    }

} else {
//if not successful, get parameters and display appropriate error
    if (isset($_GET['a'])) {
        $comp_account_name = $_GET['a'];
    }
    if (isset($_GET['d'])) {
        $comp_domain = $_GET['d'];
    }
    if (isset($_GET['u'])) {
        $comp_username = $_GET['u'];
    }

    if (isset($_GET['e]'])) {

        if ($_GET['e'] == 1) {
            print "<div class=\"alert alert-error\">";
            print "<strong>Oops!</strong> The Username you entered does not exist on Pinterest, or may have changed.  Please make sure you have the correct username for the competitor you are trying to add.  To double-check, go to <a target='_blank' href='http://pinterest.com/$competitor_username/'>http://pinterest.com/<strong><u>[USERNAME]</u></strong>/</a> and make sure the page exists.";
            print "</div>";
            $comp_username .= "<-- FIX :)";
        } else if ($_GET['e'] == 2) {
            print "<div class=\"alert alert-error\">";
            print "<strong>Oops!</strong> You are already tracking this competitor!";
            print "</div>";
            $comp_username = '';
        }

    }
}


//messaging about how many competitors you're allowed and how many slots you have left

print "<div class='row'>";
print "<div class='span10'>";

echo $comp_message;

print "</div>";
print "</div>";


print "<div class='row competitor-admin $inactivate_competitors'>";
print "<div class='span5'>";
print "<h1>Current Competitors</h1><br/>";

$competitors = array();
$acc         = "select a.account_id as account_id
        , a.account_name as account_name
        , a.org_id as org_id
        , a.username as username
        , a.user_id as user_id
        , a.competitor_of as competitor_of
        , b.domain as domain
        from user_accounts a
        left join user_accounts_domains b on a.account_id = b.account_id


        where a.org_id = '$cust_org_id'
        AND competitor_of = '$cust_account_id'
        AND track_type != 'orphan'
        order by account_id asc";

$acc_res = mysql_query($acc, $conn) or die(mysql_error());
while ($a = mysql_fetch_array($acc_res)) {
    $comp                 = array();
    $comp['account_id']   = $a['account_id'];
    $comp['account_name'] = $a['account_name'];
    $comp['username']     = $a['username'];
    $comp['user_id']      = $a['user_id'];
    $comp['domain']       = $a['domain'];

    array_push($competitors, $comp);
}

if (count($competitors) == 0) {
    print "<div class=\"alert alert-error\">";
    print "You currently have no competitors associated with your account.";
    print "</div>";

    print "<div class=\"well\">";
    print "Adding competitors will help you track pins and monitor activity relevant to your customers and your industry.";
    print "</div>";
} else {

    foreach ($competitors as $c) {

        $competitor_id       = $c['account_id'];
        $competitor_name     = $c['account_name'];
        $competitor_domain   = $c['domain'];
        $competitor_username = $c['username'];
        $competitor_user_id  = $c['user_id'];

        print "
<div class=\"row\">";
        print "
<div class=\"span4\">";
        print "<h2>$competitor_name</h2>";
        print "$competitor_domain";
        if ($competitor_username) {
            print "<br/><a href='http://www.pinterest.com/$competitor_username' target=_blank>pinterest.com/$competitor_username</a>";
        }
        print "
</div>";

        print "
<div class=\"span1 margin-fix\" style='padding-top: 15px;'>";
        print "<a class='btn btn-danger' onclick=\"return confirm('Are you sure you want to remove this competitor profile?');\" href='/settings/competitor/$competitor_id/remove'>REMOVE</a>";
        print "
</div>";
        print "</div>
<hr/>";
    }

}


print "</div>";

print "<div class='span5 add-competitor-admin $inactivate_competitor_admin' style='padding-left:20px;margin-left:20px;border-left:1px solid rgba(0,0,0,0.1)'>";
print "<form action='/settings/competitor/add' method='POST' style='margin-left:20px'>
        <input type='hidden' name='tab' value='Competitors'>
        <fieldset>";
print "
            <div class=\"row\">";
print "<h1>Add a Competitor</h1><br/>";
print "<br/><br/>";

print "
            <div class=\"control-group\">
            <label class=\"control-label\" for=\"account_name\"><strong>Competitor Name:</strong></label>
            <div class=\"controls\">
            <input class=\"input-large\" value=\"$comp_name\" id=\"account_name\" type=\"text\" name='account_name' placeholder='e.g. \"Walmart\"' required>
</div>
</div>";

print "
<div class=\"control-group\">
<label class=\"control-label\" for=\"domain\"><strong>Website URL:</strong></label>
<div class=\"controls input-prepend\" style='margin-bottom:0px'>
<span class=\"add-on\"><i class=\"icon-earth\"></i> http:// </span>
<input class=\"input-large\" data-minlength='0' value=\"$comp_domain\" id=\"domain\" type=\"text\" name='domain' placeholder='e.g. \"amazon.com\"' pattern='^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}$' required>
</div>
<p class='muted'>
    <small>Core domains only, no trailing slashes (e.g. ending in \".com\" or \".co.uk\").</small>
</p>
</div>";

print "
<div class=\"control-group\">
<label class=\"control-label\" for=\"username\"><strong>Username:</strong></label>
<div class=\"controls input-prepend pull-left\" style='margin-bottom:0px'>
<span class=\"add-on\"><i class=\"icon-user\"></i> pinterest.com/ </span>
<input class=\"input-large\" value=\"$comp_username\" id=\"username\" type=\"text\" name='username' placeholder='e.g. \"" . $cust_accounts[$cust_account_num]['username'] . "\"' pattern='^[a-zA-Z0-9-_]{1,20}$' title='Please include only the username, which should only have letters and numbers, and no special characters. Thanks!' required>
</div>
<div class='help-icon-form pull-left' style='margin:3px 0 0 5px;'>
    <a class='' data-toggle='popover' data-container='body' data-original-title='Not sure how to find the right username?' data-content='The Pinterest Username is found in the URL of the Pinterest profile: <span class=\"muted\">http://pinterest.com/<strong style=\"color:#000\">username</strong>/</span> <br><img class=\"img-rounded\" src=\"/img/username-help.jpg\">' data-trigger='hover' data-placement='top'>
        <i id='header-icon' class='icon-help'></i>
    </a>
</div>
<div class='clearfix'></div>
<p class='muted'>
    <small>Please include <strong>only</strong> the username, not the full URL of the profile.</small>
</p>
</div>";

print "
<div class=\"row-fluid\">";
print "
<div class=\"span\">";
print "
<div class=\"form-actions\">
<button type=\"submit\" class=\"btn btn-primary btn-add-competitor\">Add Competitor</button><br>
<div class='alert' style='margin: 15px 0 0px;'>
    <button class=\"close\" data-dismiss=\"alert\" style='border: 0; background-color: transparent;'>×</button>
    Note: please allow up to 24 hours for new competitors to be fully added to your Competitors report
</div>
</div>";
print "
</div>";

print "</div><br/>
</fieldset>
</form>";


echo $comp_access_js;

print "</div>";
print "</div>";