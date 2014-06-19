<?= $navigation;?>
<?php
$account_tabs = array();
$account_tabs_content = array();
$account_counter = 0;

$industry_select = "";

foreach(Session::get('industries') as $ind){
$industry_select .= "
<option value='" . $ind['id'] . "'>" . $ind['name'] . "</option>";
}

foreach($cust_accounts as $ac){

if($ac['account_name']==""){
$ac['account_name'] = "[Account Name]";
$this_account_name_label = "[Account Name]";
$this_account_name_input = "";
$this_account_placeholder = "Enter an Account Name";
} else {
$this_account_name_label = $ac['account_name'];
$this_account_name_input = $ac['account_name'];
$this_account_placeholder = "";
}
/*
* Set tab labels
*
* @authors Alex
*/
if($account_counter==0){

$account_tabs[] = "
<li class='account-tab-label main-account span4 active'>
    <a href='#account_$account_counter' data-toggle='tab'>
        <span class='main-account-name'>$this_account_name_label</span>
        <span class='pull-right'><span class='label label-info'>Main Account</span></span>
    </a>
</li>";

$account_tabs_content[] = "
<div class='tab-pane active' id='account_$account_counter'>
    <h2>$this_account_name_label</h2>";
    } else {
    $account_tabs[$account_counter] = "
    <li class='account-tab-label span4'>
        <a href='#account_$account_counter' data-toggle='tab'>
            <span class='account-name'>$this_account_name_label</span>
            <span class='pull-right'><i class='icon-arrow-right'></i></span>
        </a>
    </li>";

    $account_tabs_content[$account_counter] = "
    <div class='tab-pane' id='account_$account_counter'>
        <h2>". $ac['account_name'] ."</h2>
        <form action='/settings/account/".$ac['account_id']."/remove' method='POST'>
            <input type='hidden' name='account_id' value='" . $ac['account_id'] . "'>
            <button class='btn remove-account btn-mini' type='submit'
                    onclick=\"confirm('Are you sure you want to delete this account?  All of your data and history will be lost');\">Delete</button>
        </form>";
        }



        /*
        * Adds form for each account to the tab content area
        *
        * @authors Alex
        */
        $account_tabs_content[$account_counter] .=

        "<form action='/settings/account/edit' method='POST' style='margin-left:20px'>
            <input type='hidden' name='account_id' value='" . $ac['account_id'] . "'>
            <fieldset>"

                ."
                <div class=\"control-group\">
                <label class=\"control-label\" for=\"account_name\">
                <strong>Account Name / Handle:</strong>
                </label>
                <div class=\"controls\">
                <input class=\"input-large\" value=\"$this_account_name_input\"
                id=\"account_name\" type=\"text\"
                name='account_name' placeholder='$this_account_placeholder'
                required>
    </div>
</div>"

."
<div class='control-group inline-block'>
    <label class='control-label' for='username'><strong>Pinterest Username:</strong></label>
    <div class='controls'>
        <div class='input-prepend pull-left' style='margin-bottom:0px'>
                                        <span class='add-on'>
                                            <i class='icon-user'></i> pinterest.com/
                                        </span>
            <input style='width:200px;margin-left: -4px;'
                   value='". $ac['username'] ."' id='username' type='text'
            name='username' placeholder='Username'
            pattern='^[a-zA-Z0-9-_]{1,20}$'
            title='Please include username only, which should
            consist of only letters and numbers (no special characters).
            Thanks!' required>
        </div>
        <div class='help-icon-form pull-left' style='margin:3px 0 0 5px;'>
            <a id='help-icon-link' class=''
               data-toggle='popover'
               data-container='body'
               data-original-title='Not sure how to find your username?'
               data-content='Your Pinterest Username is found in the URL
                                                of your Pinterest profile:
                                                <span class=\"muted\">http://pinterest.com/
                                                <strong style=\"color:#000\">username</strong>/</span>
                                                <br><img class=\"img-rounded\"
                                                src=\"/img/username-help.jpg\">'
               data-trigger='hover' data-placement='top'>
                <i id='header-icon' class='icon-help'></i>
            </a>
        </div>
    </div>
</div>

<div class='clearfix'></div>
".$oauth_button."

<div class='clearfix'></div>"

."
<div class=\"control-group inline-block\">
<label class=\"control-label\" for=\"domain\"><strong>Domain:</strong></label>
<div class=\"controls\" style='margin-bottom:0px'>
<div class='input-prepend pull-left' style='margin-bottom:0px'>
    <span class=\"add-on\">
    <i class=\"icon-earth\"></i> http://
    </span>
    <input class=\"input-large\" data-minlength='0' value=\"" . @$ac['domains'][0] . "\"
    id=\"domain\" type=\"text\" name='domain' placeholder='e.g. \"amazon.com\"'
    pattern='^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}$'>
</div>
<div class='help-icon-form pull-left' style='margin:3px 0 0 5px;'>
    <a id='help-icon-link-domain' class=''
       data-toggle='popover'
       data-container='body'
       data-original-title='What Domain would you like to track?'
       data-content='
                                            <strong>Instructions:</strong>
                                            <small>
                                            <ul>
                                                <li>Core domains only, no trailing slashes
                                                    <br>(e.g. ending in \".com\" or \".co.uk\")</li>
                                                <li>\"http://\" and \"www\" not required.</li>
                                                <li>Only domains / subdomains can be tracked.</li>
                                            </ul>
                                            </small>
                                            <strong>Examples:<br></strong>
                                            <small>
                                                <span class=\"text-success\"><strong>Trackable:</strong></span>
                                                etsy.com, macys.com, yoursite.tumblr.com
                                                <br><span class=\"text-error\"><strong>Not Trackable:</strong></span>
                                                etsy.com/shop/mystore, macys.com/mens-clothing
                                            </small>'
       data-trigger='hover' data-placement='top'>
        <i id='header-icon' class='icon-help'></i>
    </a>
</div>
</div>
</div>"

."<div class=\"control-group inline-block\">
<label class=\"control-label\" for=\"account_type\">
<strong>Account Type:</strong>
</label>
<div class='controls'>
    <select class='input-xlarge' name='account_type'>
        <option selected='selected' value='" . $ac['account_type'] . "'>
        " . ucwords($ac['account_type']) . "</option>
        <option value='brand'>Brand</option>
        <option value='sub-brand'>Sub-Brand</option>
        <option value='agency client'>Agency Client</option>
        <option value='non-profit'>Non-Profit</option>
        <option value='personal'>Personal / Individual</option>
        <option value='other'>Other</option>
    </select>
</div>
</div>"

."<div class=\"control-group inline-block\">
<label class=\"control-label\" for=\"industry_id\">
<strong>Industry:</strong>
</label>
<div class='controls'>
    <select class='input-xlarge' name='industry_id'>
        <option selected='selected' value='". $ac['industry_id'] ."'>" . $ac['industry_name'] . "</option>
        " . $industry_select . "
    </select>
</div>
</div>"

."
<div class=\"form-actions\">
<input value='". $ac['username'] ."' id='username_check' type='hidden' name='username_check'>
<button type=\"submit\" class=\"btn btn-primary pull-right\"
onClick='return checkUsername();'>Save Changes</button>
</div>"

."
</fieldset>
</form>
</div>";

$account_counter++;
}



if(isset($_GET['add'])){
$simulate_click_add_account = "
$('.account-tab-label.add-account, .account-tab-label.add-account a').trigger('click');
setTimeout('$(\"input#account_name\").focus()', 500);

";
}



if(isset($_GET['e]'])) {

if ($_GET['e'] == 3) {

$incorrect_username = $_GET['iu'];
print "<div class=\"alert alert-error\">";
print "<strong>Whoops!</strong> The username you attempted to change to does not exist on Pinterest, or may have recently changed.  To make sure you enter the username correctly, go to <a target='_blank' href='http://pinterest.com/$incorrect_username/'>http://pinterest.com/<strong><u>[USERNAME]</u></strong>/</a> and make sure the page exists.";
print "</div>";
}

}
?>
<script>
    function checkUsername() {

        var new_username = $('input#username').not(':hidden').last().val();
        var curr_username = $('.form-actions').not(':hidden').last().find('input#username_check').val();

        if (new_username != curr_username) {
            var response = confirm('Are you sure you want to change your username?  You will lose access to your current data and a new dashboard will have to be created for you.  It may take several minutes to populate your new dashboard with preliminary metrics (and up to 24 hours before all reports are fully populated).');

            return response;
        }

    }

    function checkAddForm() {
        if(document.getElementById('account_name').value == ''
            || document.getElementById('username').value == ''){
            return alert('Our clairvoyance engine is still in development. For now, you\'ll have to fill in your Account Name and Username yourself :)')
        } else {
            $('#modal-confirm').modal();
            return false;
        }
    }

    $(document).ready(function() {

        $('.account-tab-label.add-account').click(function(){
            setTimeout('$(\'input#account_name\').focus()', 500);
        });

        <?=$simulate_click_add_account;?>

        $('#username').blur(function(){

            username = document.getElementById('username').value;

            $.ajax({
                type: 'GET',
                url: '/ajax/check-username/'+username,
                data: {'username': username},
                dataType: 'html',
                success: function(data){

                    if ($(data).filter('.status').text()==1){
                        $('.check-username').html($(data).filter('.data'));
                        $('#site_address').attr('value',$(data).filter('.domain').text());
                        $('#firstname').attr('value',$(data).filter('.first_name').text());
                        $('#lastname').attr('value',$(data).filter('.last_name').text());
                        $('#header-icon').removeClass('icon-help');
                        $('#header-icon').addClass('icon-checkmark');
                        $('#help-icon-link').removeAttr('data-toggle');
                        $('#help-icon-link').addClass('text-success');
                        var container = $('#main-content-scroll'),
                            scrollTo = $('.check-username');

                        container.animate({
                            scrollTop: scrollTo.offset().top - container.offset().top + container.scrollTop()
                        });

                        return true;
                    } else {
                        $('.check-username').html($(data).filter('.data'));
                        $('#header-icon').removeClass('icon-checkmark');
                        $('#header-icon').addClass('icon-help');
                        $('#help-icon-link').attr('data-toggle','popover');
                        $('#help-icon-link').removeClass('text-success');
                        return false;
                    }
                }
            });

        });
    });
</script>


<?php
if($cust_chargify_id <= 1 || $cust_chargify_id == "" || !$cust_chargify_id){

    $has_chargify                       = false;
    $cust_product                       = "Free Starter Account";
    $cust_subscription_state            = "active";
    $cust_next_assessment_at            = "N/A";
    $current_period_ends_at             = "N/A";
    $cust_masked_credit_card            = "";;
    $cust_components                    = "";
    $cust_component_quantity            = 0;
    $cust_update_billing_link           = "";
    $add_account_disable                = "deactivated";
    $base_price                         = 0;
    $comp_breakdown                     = array();
    $current_monthly_total              = 0;
    $add_next_component_price           = 0;
    $add_next_component_price_prorated  = 0;
    $component_total_price              = 0;
    $new_component_total_price          = 0;
    $current_monthly_total              = 0;
    $new_monthly_total                  = 0;
    $next_component_discount            = 0;
    $days_in_curr_bill_period           = 0;
    $days_left_curr_bill_period         = 0;
    $add_second_component_price         = 0;

    ?>

    <script>
        $(document).ready(function() {
            $('#add-account-tab .form-actions').remove();
            $('#add-account-tab input').remove();
            $('#add-account-tab select').remove();
            $('#modal-confirm').remove();
            $('#add-account-tab').css('opacity','0.7');

            <?php if ($customer->plan()->plan_id != 4): ?>
                $('.enterprise-accounts-disabled').removeClass('hidden');
            <?php else: ?>
                $('.chart-upgrade').removeClass('hidden');
            <?php endif ?>
        });
    </script>


<?php
} else {

    if($customer->plan()->plan_id == 1){

        $add_account_disable        = "deactivated";
        ?>

        <script>
            $(document).ready(function() {
                $('#add-account-tab .form-actions').remove();
                $('#add-account-tab input').remove();
                $('#add-account-tab select').remove();
                $('#modal-confirm').remove();
                $('.chart-upgrade').removeClass('hidden');
                $('#add-account-tab').css('opacity','0.7');
            });
        </script>

    <?php       } else {
        $add_account_disable        = "";
    }



    $has_chargify               = true;

    $product_family = 297652;

    $cust_obj = new ChargifyCustomer(NULL, false);
    try {
        $cust_obj->id = $cust_chargify_id;
        $this_cust = $cust_obj->getByID();
    } catch (ChargifyValidationException $cve) {
        //echo $cve->getMessage();
    }


    $subscription = new ChargifySubscription(NULL, false);

    try {
        $sub = $subscription->getByCustomerID($this_cust->id);
    } catch (ChargifyValidationException $cve) {
        //echo $cve->getMessage();
    }

    if($sub[0]->state == "trialing"){
        $state_style = "label-warning";
    } else if ($sub[0]->state == "active"){
        $state_style = "label-success";
    } else if ($sub[0]->state == "past_due" || $sub[0]->state == "canceled"){
        $state_style = "label-important";
    }

    $this_sub = $sub[0];

    $base_price = number_format($this_sub->product_price_in_cents/100,0);

    $components = new ChargifyQuantityBasedComponent();
    //$components->allocated_quantity = 1;
    //$components->update($subscription->id,20537);
    $comps      = $components->getAll($this_sub->id, 20537);


    $connector = new ChargifyConnector();
    //$comps = json_decode($connector->retrieveAllMeteredComponentsByProductFamily(297652,".json"));


    /*
     * get appropriate component
     * details and pricing
     */
    switch ($cust_plan) {
        case 1:
            //20540
            break;
        case 2:
            $comp_number    = 20540;
            break;
        case 3:
            $comp_number    = 20537;
            break;
        case 4:
            $comp_number    = 20537;
            break;
        default:
            //
    }

    $cust_product               = $sub[0]->product->name;
    $cust_subscription_state    = $sub[0]->state;
    $cust_next_assessment_at    = date('m/d/Y', strtotime($sub[0]->next_assessment_at));
    $cust_masked_credit_card    = $sub[0]->credit_card->masked_card_number;
    $cust_components            = $components->getAll($this_sub->id, $comp_number);
    $cust_component_quantity    = $cust_components->allocated_quantity;
    $comp_details               = json_decode($connector
                                              ->retrieveComponentByComponentId($product_family, $comp_number,".json"));

    function addOrdinalNumberSuffix($num) {
        if (!in_array(($num % 100),array(11,12,13))){
            switch ($num % 10) {
                // Handle 1st, 2nd, 3rd
                case 1:  return $num.'st';
                case 2:  return $num.'nd';
                case 3:  return $num.'rd';
            }
        }
        return $num.'th';
    }

    function getNextComponentPrice($price_ranges = array(), $quantity){

        $comp_price = false;

        foreach($price_ranges as $range){

            if($quantity < $range->ending_quantity
                && $quantity >= $range->starting_quantity-1){
                $comp_price = $range->unit_price;
            }
        }

        return $comp_price;
    }

    function getComponentsRecurringPrice($price_ranges = array(), $quantity){

        $comp_total_price = 0;

        foreach($price_ranges as $range){

            if($quantity > $range->ending_quantity){
                $comp_total_price += $range->unit_price * ($range->ending_quantity - $range->starting_quantity+1);
            } else if($quantity >= $range->starting_quantity-1){
                $comp_total_price += $range->unit_price * ($quantity - $range->starting_quantity+1);
            }

        }

        return $comp_total_price;
    }

    function getComponentPriceBreakdown($price_ranges = array(), $quantity){

        $comp_total_price = 0;
        $counter = 0;
        $breakdown = array();

        foreach($price_ranges as $range){

            if($quantity > $range->ending_quantity){
                $breakdown[$counter]['price'] = $range->unit_price * ($range->ending_quantity - $range->starting_quantity+1);
                $breakdown[$counter]['print'] = "(".($range->ending_quantity - $range->starting_quantity+1)." × $".number_format($range->unit_price,0).")";
            } else if($quantity > $range->starting_quantity-1){
                $breakdown[$counter]['price'] = $range->unit_price * ($quantity - $range->starting_quantity+1);
                $breakdown[$counter]['print'] = "(".($quantity - $range->starting_quantity+1)." × $".number_format($range->unit_price,0).")";
            }

            if($range->starting_quantity == $range->ending_quantity){
                $breakdown[$counter]['grid'] = addOrdinalNumberSuffix($range->starting_quantity)." Extra Account: $".number_format($range->unit_price,0);
            } else {
                $breakdown[$counter]['grid'] = addOrdinalNumberSuffix($range->starting_quantity)." - ". addOrdinalNumberSuffix($range->ending_quantity)." Extra Accounts: $".number_format($range->unit_price,0);
            }

            $counter++;
        }

        return $breakdown;
    }


    $first_component_price      = number_format(getNextComponentPrice($comp_details->component->prices, 0),0);
    $add_next_component_price   = getNextComponentPrice($comp_details->component->prices, $cust_component_quantity);
    $add_second_component_price = getNextComponentPrice($comp_details->component->prices, $cust_component_quantity+1);
    $component_total_price      = getComponentsRecurringPrice($comp_details->component->prices, $cust_component_quantity);
    $new_component_total_price  = getComponentsRecurringPrice($comp_details->component->prices, $cust_component_quantity+1);


    $current_monthly_total      = number_format($component_total_price + $base_price,0);
    $new_monthly_total          = number_format($new_component_total_price + $base_price,0);


    $comp_breakdown             = getComponentPriceBreakdown($comp_details->component->prices, $cust_component_quantity);

    if(isset($comp_breakdown[0]['print'])){
        $next_component_discount    = "(Save $". number_format($comp_breakdown[0]['price'] - $add_next_component_price,0) . ")";
    } else {
        $next_component_discount    = "";
    }



    /*
     * Calculate prorated charge for adding account
     */
    $days_left_curr_bill_period = floor((strtotime($this_sub->current_period_ends_at)
        - time())/60/60/24);
    $days_in_curr_bill_period   = number_format((strtotime($this_sub->current_period_ends_at)
        - strtotime($this_sub->current_period_started_at))/60/60/24, 2);
    $current_period_ends_at     = date('m/d/Y', strtotime($this_sub->current_period_ends_at));

    if($days_in_curr_bill_period < 28){
        $days_in_curr_bill_period = 31;
    };

    $add_next_component_price_prorated = number_format(($days_left_curr_bill_period
        / $days_in_curr_bill_period
        * $add_next_component_price),2);


}
?>

<div class="clearfix"></div>

<div class="row account-management">
<div class="span10">

<div class="tabbable tabs-left">
<ul class="nav nav-tabs account-tabs">

    <?php
    /*
     * print out each account tab
     */
    foreach($account_tabs as $act) {
        echo $act;
    }
    ?>

    <li class='account-tab-label add-account text-center'>
        <a href='#add-account-tab'
           data-toggle='tab'><i class="icon-plus"></i>&nbsp; Add A New Account</a>
    </li>

</ul>

<div class="tab-content account-tab-content">
<div class='tab-pane' id='add-account-tab'>
<h2>Add a New Account</h2>

<div class="chart-upgrade well hidden">
    <h4>Upgrade to Add Extra Accounts</h4>
    <ul>
        <li><strong>Get multi-account</strong> features
        <li><strong>Use one login</strong> to access <br>all accounts</li>
        <li><strong>Seamlessly switch</strong> in a <br>unified dashboard</li>
    </ul>
    <a class="btn-link" href="/upgrade?ref=settings_add_account&plan=<?= $customer->plan()->plan_id; ?>">
        <button class="btn btn-success btn-block">
            <i class="icon-arrow-right"></i> Learn More
        </button>
    </a>
</div>

<div class="enterprise-accounts-disabled well hidden">
    <h4>Please contact us to Add Extra Accounts</h4>
    <a class="btn-link">
        <button class="btn btn-success btn-block" id="Intercom">
            <i class="icon-arrow-right"></i> Contact Us
        </button>
    </a>
</div>


<form action='/settings/account/add'
      method='POST' style='margin-left:20px'>
<input type='hidden' name='account_id' value=" <?= $ac['account_id'];?>  ">
<fieldset>


<div class="control-group">
    <label class="control-label" for="account_name">
        <strong>New Account Name / Handle:</strong>
    </label>
    <div class="controls">
        <input class="input-large" value=""
               id="account_name" type="text"
               name='account_name' placeholder='e.g. "Walmart"'
               required>
    </div>
</div>


<div class='control-group inline-block'>
    <label class='control-label' for='username'><strong>Pinterest Username:</strong></label>
    <div class='controls'>
        <div class='input-prepend pull-left' style='margin-bottom:0px'>
                                                            <span class='add-on'>
                                                                <i class='icon-user'></i> pinterest.com/
                                                            </span>
            <input style='width:200px;margin-left: -4px;'
                   value='' id='username' type='text'
                   name='username' placeholder='Username'
                   pattern='^[a-zA-Z0-9-_]{1,20}$'
                   title='Please include username only, which should
                                                                   consist of only letters and numbers (no special characters).
                                                                   Thanks!' required>
        </div>
        <div class='help-icon-form pull-left' style='margin:3px 0 0 5px;'>
            <a id='help-icon-link' class=''
               data-toggle='popover'
               data-container='body'
               data-original-title='Not sure how to find your username?'
               data-content='Your Pinterest Username is found in the URL
                                                                of your Pinterest profile:
                                                                <span class="muted">http://pinterest.com/
                                                                <strong style="color:#000">username</strong>/</span>
                                                                <br><img class="img-rounded"
                                                                src="/img/username-help.jpg">'
               data-trigger='hover' data-placement='top'>
                <i id='header-icon' class='icon-help'></i>
            </a>
        </div>
    </div>
</div>

<div class='clearfix'></div>


<div class="control-group inline-block">
    <label class="control-label" for="domain">
        <strong>Domain:</strong>
    </label>
    <div class="controls" style='margin-bottom:0px'>
        <div class='input-prepend pull-left' style='margin-bottom:0px'>
                                                            <span class="add-on">
                                                                <i class="icon-earth"></i> http://
                                                            </span>
            <input class="input-large" data-minlength='0' value=""
                   id="domain" type="text" name='domain' placeholder='e.g. "amazon.com"'
                   pattern='^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}$'>
        </div>
        <div class='help-icon-form pull-left' style='margin:3px 0 0 5px;'>
            <a id='help-icon-link-domain' class=''
               data-toggle='popover'
               data-container='body'
               data-original-title='What Domain would you like to track?'
               data-content='
                                                                <strong>Instructions:</strong>
                                                                <ul>
                                                                <small>
                                                                    <li>Core domains only, no trailing slashes
                                                                    <br>(e.g. ending in ".com" or ".co.uk")</li>
                                                                    <li>"http://" and "www" not required.</li>
                                                                    <li>Only domains / subdomains can be tracked.</li>
                                                                </small>
                                                                </ul>
                                                                <strong>Examples:</strong><br>
                                                                <small>
                                                                    <span class="text-success"><strong>Trackable:</strong></span>
                                                                     etsy.com, macys.com, yoursite.tumblr.com
                                                                    <br><span class="text-error"><strong>Not Trackable:</strong></span>
                                                                     etsy.com/shop/mystore, macys.com/mens-clothing
                                                                 </small>'
               data-trigger='hover' data-placement='top'>
                <i id='header-icon' class='icon-help'></i>
            </a>
        </div>
    </div>
</div>

<div class="control-group inline-block">
    <label class="control-label" for="account_type">
        <strong>Account Type:</strong>
    </label>
    <div class='controls'>
        <select class='input-xlarge' name='account_type'>
            <option selected='selected' value=''></option>
            <option value='brand'>Brand</option>
            <option value='sub-brand'>Sub-Brand</option>
            <option value='agency client'>Agency Client</option>
            <option value='non-profit'>Non-Profit</option>
            <option value='personal'>Personal / Individual</option>
            <option value='other'>Other</option>
        </select>
    </div>
</div>

<div class="control-group inline-block">
    <label class="control-label" for="industry_id">
        <strong>Industry:</strong>
    </label>
    <div class="controls">
        <select class='input-xlarge' name='industry_id'>
            <option selected='selected' value='<?=$cust_industry_id;?>'>
                <?=$cust_industry;?></option>
            <?=$industry_select;?>
        </select>
    </div>
</div>

<div class="form-actions">

    <?php if($has_chargify){ ?>
        <div class="span pull-left">
            <div class='help-icon-add-account pull-left' style='margin:3px 0 0 5px;'>
                <a id='help-icon-link' class=''
                   data-toggle='popover'
                   data-container='body'
                   data-original-title='Extra Account Pricing'
                   data-content='You currently have
                               <strong><?=$cust_component_quantity;?></strong>
                               extra accounts.
                               <br><br>
                               <strong>Add an extra account for only
                               $<?=$first_component_price;?></strong>/mo'
                   data-trigger='hover' data-placement='top'>
                    Pricing Info <i id='header-icon' class='icon-help'></i>
                </a>
            </div>
        </div>
    <?php } ?>

    <div class="span pull-right">
        <button class="btn btn-success"
                onclick="return checkAddForm();">
            Add To Dashboard
        </button>
    </div>
</div>

<!-- MODAL WINDOW to CONFIRM ORDER and SUBMIT FORM -->
<!-- MUST be inside <form> tags in order to work -->
<div id="modal-confirm"
     class="modal hide fade" tabindex="-1" role="dialog"
     aria-labelledby="modal-confirm-label" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h3 id="modal-confirm-label">Confirmation</h3>
    </div>
    <div class="modal-body">
        <div class="current-details">

            <div class="row no-border">
                <div class="span pull-left">
                    <h4>Current Subscription Details:</h4>
                </div>
            </div>

            <div class="row">
                <div class="span pull-left">
                    <?= $cust_product; ?>
                </div>
                <div class="span pull-right">
                    $<?=$base_price; ?>
                </div>
            </div>

            <div class="row no-border">
                <div class="span pull-left">
                    Additional Accounts:
                </div>
            </div>

            <?php
            if(count($comp_breakdown)!=0){
                foreach($comp_breakdown as $cp){
                    if(isset($cp['print'])){
                        print "<div class='row'>";
                        print "<div class='span pull-left muted'>";
                        print "<div class='span account-breakdown'>";
                        echo $cp['print'];
                        print "</div>";
                        print "</div>";
                        print "<div class='span pull-right account-breakdown'>";
                        echo "$".$cp['price'];
                        print "</div>";
                        print "</div>";
                    }
                }
            } else {
                print "<div class='row'>";
                print "<div class='span pull-left muted'>";
                print "<div class='span account-breakdown'>";
                print "None";
                print "</div>";
                print "</div>";
                print "<div class='span pull-right account-breakdown'>";
                print "$0";
                print "</div>";
                print "</div>";
            }
            ?>


            <div class="row total-cost">
                <div class="span pull-left">
                    <strong>Current Monthly Total</strong>
                </div>
                <div class="span pull-right">
                    <strong>$<?=$current_monthly_total; ?></strong>
                </div>
            </div>
        </div>

        <br>

        <div class="new-details">
            <div class="row no-border">
                <div class="span pull-left">
                    <h4>Additions to Dashboard:</h4>
                </div>
            </div>
            <div class="row">
                <div class="span pull-left muted">
                                                                <span class="text-success">
                                                                    <strong>New Account:</strong>
                                                                </span>
                </div>
                <div class="span pull-right">
                    $<?= number_format($add_next_component_price,0);?>/month
                                                                <span class="text-success">
                                                                    <?=$next_component_discount;?>
                                                                    <span>
                </div>
            </div>

            <br>


            <div class="row total-cost">
                <div class="span pull-left muted">
                    <strong>New Monthly Total:</strong>
                </div>
                <div class="span pull-right">
                    <strong>$<?= $new_monthly_total;?>/month</strong>
                </div>
            </div>


        </div>
    </div>
    <div class="modal-footer">
        <div class="pull-left text-left additional-info">

            - New Monthly Total will go into effect on
            <?= $current_period_ends_at;?>.
            <br>- There will be a prorated charge of
            $<?= $add_next_component_price_prorated;?>
            for the remaining <?= $days_left_curr_bill_period;?> days left
            in your current billing period.

        </div>
        <div class="pull-right">
            <button type="submit"
                    class="btn btn-success btn-large">Confirm!</button>
        </div>
    </div>
</div>


</fieldset>
</form>
<div class='<?=$add_account_disable;?>'></div>
</div>


<?php
/*
 * Print out form for each account for the user
 * to be able to edit
 *
 * @authors Alex
 */
foreach($account_tabs_content as $act) {
    echo $act;
}
?>



</div>
</div>
</div>
<div class="span2">
    <div class="check-username"></div>
</div>
</div>