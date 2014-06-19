<?= $profile_navigation; ?>
<div class='span5'>
    <div class="row account-info">
        <div class="">
            <form action='/settings/profile/edit'
                  method='POST'
                  class="form-horizontal">
                <fieldset>

                    <div class="row account-form-header">
                        <div class="span">
                            <h3>Contact Info</h3>
                        </div>
                    </div>

                    <div class="row">
                        <div class="span account-form-label">
                            <strong>Name:</strong>
                        </div>
                        <div class="span2">
                            <input type='text' name="first_name" class="input span2"
                                   value="<?= $cust_first_name; ?>"
                                   placeholder="First Name">
                        </div>
                        <div class="span2">
                            <input type='text' name="last_name" class="input span2"
                                   value="<?= $cust_last_name; ?>"
                                   placeholder="Last Name">
                        </div>
                    </div>

                    <div class="row user-form">
                        <div class="span account-form-label">
                            <strong>Email:</strong>
                        </div>
                        <div class="span4">
                            <span type='text'
                                  class="input-xlarge uneditable-input"
                                  id="email">
                                <?= $cust_email; ?>
                            </span>
                        </div>
                    </div>

                    <div class="row account-form-header">
                        <div class="span">
                            <h3>
                                Your Company / Organization
                            </h3>
                        </div>
                    </div>

                    <div class="row">
                        <div class="span account-form-label">
                            <strong>Name:</strong>
                        </div>
                        <div class="span3">
                            <input type="text"
                                   name="org_name"
                                   class="input-xlarge"
                                   id="org_name"
                                   value="<?= $cust_org_name; ?>"
                                >
                        </div>
                    </div>

                    <div class="row" style="margin-top:20px;">
                        <div class="span account-form-label">
                            <strong>Type:</strong>
                        </div>
                        <div class="span3">
                            <select class='input-xlarge' name='org_type'>
                                <option selected='selected' value='<?= $cust_org_type; ?>'>
                                    <?= $cust_org_type_display; ?>
                                </option>
                                <option value='brand'>
                                    Brand / Business
                                </option>
                                <option value='agency'>
                                    Agency
                                </option>
                                <option value='non-profit'>
                                    Non-Profit
                                </option>
                                <option value='personal'>
                                    Personal / Individual
                                </option>
                                <option value='other'>
                                    Other
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="row form-actions" style="padding-left:20px;">

                        <a href="/password-reset">
                            Change your password
                        </a>
                        <button type="submit" class="btn pull-right">
                            Save changes
                        </button>
                    </div>

                </fieldset>
            </form>
        </div>
    </div>
</div>


<div class='span6 billing-info'>

    <div class="row account-form-header">
        <div class="span">
            <h3>
                Your Subscription
            </h3>
        </div>
    </div>

    <div class="row">
        <div class="span2">
            <strong>Product:</strong>
        </div>
        <div class="span3">
            <?= $cust_product; ?>
        </div>
    </div>
    <div class="row">
        <div class="span2">
            <strong>Status:</strong>
        </div>
        <div class="span3">
            <span class="label <?= $state_style; ?>">
                <?= $cust_subscription_state; ?>
            </span>
            <?= $expired_status; ?>
        </div>
    </div>
    <div class="row">
        <div class="span2">
            <strong>Extra Accounts:</strong>
        </div>
        <div class="span3">
            <?= $cust_component_quantity; ?>
        </div>
    </div>
    <?php if ($has_chargify) { ?>
        <div class="row">
            <div class="span2">
                <strong>Next Billing Date:</strong>
            </div>
            <div class="span3">
                <?= $cust_next_assessment_at; ?>
            </div>
        </div>
        <div class="row">
            <div class="span2">
                <strong>Billing Credit Card:</strong>
            </div>
            <div class="span3">
                <?= $cust_masked_credit_card; ?>
            </div>
        </div>
        <div class="row">
            <div class="span2">
                <strong>Expiration Date:</strong>
            </div>
            <div class="span3">
                <span class="<?= $expiration_class; ?>"
                      style='<?= $expiration_style; ?>'>
                    <?= $expiration_month; ?> / <?= $expiration_year; ?>
                    <? //$sub[0]->credit_card->expiration_month; ?>
                    <? //$sub[0]->credit_card->expiration_year; ?>
                </span>
                <?= $cust_update_billing_link; ?>
            </div>
        </div>
        <?= $past_due_amount; ?>
    <?php } ?>
    <div class="row form-actions">
        <a href="/upgrade?ref=billing_upgrade">
            <button class="btn btn-success pull-right">Upgrade</button>
        </a>
        <a href="/upgrade?ref=billing">
            <button class="btn pull-right" style="margin-right:20px;">Change Your Plan</button>
        </a>
    </div>
</div>

<div class="clearfix"></div>
