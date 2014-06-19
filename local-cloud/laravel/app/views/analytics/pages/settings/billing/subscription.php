<?= $settings_navigation; ?>

<?php
$customer = User::find($cust_id);
/*
             * Create the token necessary to create the
             * secure "update billing info" link
             * for each customer
             */
$token = sha1('update_payment--' . $sub[0]->id . '--QRx_0FzJtvqT30dAQFd9');

$expiration_class = "";
$expiration_style = "";
$billing_update_label_class = "label-info";
$billing_update_button_class = "btn-info";
$expired_status = "";
$billing_tab_icon = "";
$billing_tab_class = "";
$billing_tooltip = "";
$past_due_amount = "";

if ($sub[0]->state == "past_due") {
    $billing_tab_icon            = "";
    $billing_tab_class           = "expired-card";
    $billing_tooltip             = "data-toggle='tooltip' data-container='body' data-placement='bottom' data-original-title='There was be a problem processing your payment.  Please verify your billing details!'";
    $billing_update_label_class  = "label-important";
    $billing_update_button_class = "btn-danger";
    $past_due_class              = "text-error";
    $past_due_label              = "<span class='label label-important pull-right'>Past Due</span>";
    $past_due_amount             = "<div class='row'>
    <div class='span2'>
        <strong>Current Balance:</strong>
    </div>
    <div class='span3'>
        <strong class='text-error'>$" . number_format(($sub[0]->balance_in_cents / 100), 2) . "</strong>
        $past_due_label
    </div>
</div>";
}

if (time() > $expiration_timestamp) {
    $expiration_class            = "text-error";
    $expiration_style            = "font-weight:bold;";
    $billing_update_label_class  = "label-important";
    $billing_update_button_class = "btn-danger";
    $expired_status              = "<span class='label label-important'>Expired Credit Card</span>";
    $billing_tab_icon            = "";
    $billing_tab_class           = "expired-card";
    $billing_tooltip             = "data-toggle='tooltip' data-container='body' data-placement='bottom' data-original-title='Your credit card may have expired.  Please verify your billing details!'";
}

$cust_update_billing_link = "<a target='_blank' class='label $billing_update_label_class pull-right'
                                  href='https://tailwind.chargify.com/update_payment/" . $sub[0]->id . "/" . substr($token, 0, 12) . "'>
    Update Billing Info →
</a>";

?>

<?= $billing_navigation; ?>

<div class="row margin-fix">
    <div class="span5" style="padding-right: 30px;margin-right: 20px;border-right: 1px solid rgba(0, 0, 0, 0.1);">
        <h3>Subscription</h3>

        <div class="row subscription-info">
            <div class="pull-left">
                <strong>Status:</strong>
                <span class="label <?= $state_style; ?>"><?= $sub[0]->state; ?></span>
                <?= $expired_status; ?>
            </div>

            <div class="pull-right">
                <strong>Next Bill:</strong>
                <span><?= date('m/d/Y', strtotime($sub[0]->next_assessment_at)); ?></span>
            </div>
        </div>

        <hr>

        <div class="current-details">
            <div class="row">
                <div class="span pull-left">
                    <?= $cust_product; ?>
                </div>
                <div class="span pull-right">
                    $<?= $base_price; ?>
                </div>
            </div>

            <div class="row no-border">
                <div class="span pull-left">
                    Additional Accounts:
                </div>
            </div>

            <?php
            if (count($comp_breakdown) != 0) {
                foreach ($comp_breakdown as $cp) {
                    if (isset($cp['print'])) {
                        print "<div class='row'>";
                        print "<div class='span pull-left muted'>";
                        print "<div class='span account-breakdown'>";
                        echo $cp['print'];
                        print "</div>";
                        print "</div>";
                        print "<div class='span pull-right account-breakdown'>";
                        echo "$" . $cp['price'];
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

            <?php if(!empty($cust_coupon)){ ?>
                <div class="row no-border">
                    <div class="span pull-left">
                        Coupons:
                    </div>
                </div>
                <div class="row">
                    <div class="span pull-left muted">
                        <div class='span account-breakdown'
                             data-toggle="popover"
                             data-container="body"
                             data-content="<?=$cust_coupon_description;?>"
                             data-placement="top">
                            <?= $cust_coupon; ?> <i class="icon-help"></i>
                        </div>
                    </div>
                    <div class="span pull-right muted">
                        <?php if($cust_coupon_amount != 0){ ?>
                            ($<?= $cust_coupon_amount; ?>)
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>

            <div class="row total-cost">
                <div class="span pull-left">
                    <strong>Current Monthly Total</strong>
                </div>
                <div class="span pull-right">
                    <strong>$<?= $current_monthly_total; ?></strong>
                </div>
            </div>


        </div>

        <br /><br />
        <a href="/upgrade?ref=billing&plan=<?= $customer->plan()->plan_id; ?>" class="btn">
            Change Your Plan
        </a>
        <a href="/upgrade?ref=billing_upgrade&plan=<?= $customer->plan()->plan_id; ?>" class="btn btn-success">
            Upgrade
        </a>
    </div>
    <div class="span5">
        <h3>Information</h3>
        <dl class='dl-horizontal'>
            <dt>Email:</dt>
            <dd><?= $sub[0]->customer->email; ?></dd>
            <dt>Name:</dt>
            <dd><?= $sub[0]->customer->first_name; ?> <?= $sub[0]->customer->last_name; ?></dd>
            <dt>Organization:</dt>
            <dd><?= $sub[0]->customer->organization; ?></dd>
            <dt>Signed Up:</dt>
            <dd><?= date('m/d/Y', strtotime($sub[0]->customer->created_at)); // g:ia ?></dd>
        </dl>

        <hr>

        <dl class='dl-horizontal'>
            <dt>Name on Card:</dt>
            <dd><?= $sub[0]->credit_card->first_name; ?> <?= $sub[0]->credit_card->last_name; ?></dd>
            <dt>Card Type:</dt>
            <dd><?= $sub[0]->credit_card->card_type; ?></dd>
            <dt>Card Number:</dt>
            <dd><?= $sub[0]->credit_card->masked_card_number; ?></dd>
            <dt>Expiration:</dt>
            <dd class="<?= $expiration_class; ?>"
                style='<?= $expiration_style; ?>'>
                <?= sprintf("%02s", $sub[0]->credit_card->expiration_month); ?>
                / <?= $sub[0]->credit_card->expiration_year; ?>
            </dd>
            <dt>Zip:</dt>
            <dd><?= $sub[0]->credit_card->billing_zip; ?></dd>
        </dl>
        <br>

        <a class="btn <?= $billing_update_button_class; ?>" target="_blank" href="https://tailwind.chargify.com/update_payment/<?= $sub[0]->id; ?>/<?= substr($token, 0, 12); ?>">
            Update Info
        </a>
    </div>
</div>
