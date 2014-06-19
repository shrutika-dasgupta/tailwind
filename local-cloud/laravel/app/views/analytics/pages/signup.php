<script language='javascript' type='text/javascript'>
    function checkPassword() {
        if (document.getElementById('password').value != document.getElementById('confirm_password').value) {
            alert('The passwords do not match!');
            return false;
        }

    }
    $(document).ready(function () {
        $('#username').blur(function () {

            username = document.getElementById('username').value;
            $.ajax({
                type: 'GET',
                url: '/ajax/check-username/' + username,
                dataType: 'html',
                success: function (data) {

                    if ($(data).filter('.status').text() == 1) {
                        $('.check-username').html($(data).filter('.data'));
                        $('#site_address').attr('value', $(data).filter('.domain').text());
                        $('#firstname').attr('value', $(data).filter('.first_name').text());
                        $('#lastname').attr('value', $(data).filter('.last_name').text());
                        $('#header-icon').removeClass('icon-help');
                        $('#header-icon').addClass('icon-checkmark');
                        $('#help-icon-link').removeAttr('data-toggle');
                        $('#help-icon-link').addClass('text-success');
                        return true;
                    } else {
                        $('.check-username').html($(data).filter('.data'));
                        $('#header-icon').removeClass('icon-checkmark');
                        $('#header-icon').addClass('icon-help');
                        $('#help-icon-link').attr('data-toggle', 'popover');
                        $('#help-icon-link').removeClass('text-success');
                        return false;
                    }
                }
            });

        });
    });

</script>
<?= $alert; ?>


<div class="row-fluid login-wrapper" style="margin-bottom: 10px;">
<div></div>
<div class="span10 box" style="text-align:left;width:80%;padding-top:10px;">
    <div style="position: absolute;margin-top: -95px;">
        <img src="/img/tailwind-logo-white.png">
    </div>
<div class="content-wrap">
<div class='row-fluid signup-title'>
    <div class='span8'>
        <h3>Welcome to Tailwind!</h3>
        <h4 style='color:#555;margin-bottom:0px;'> Let's Setup Your Profile...</h4>
    </div>
    <div class="span4 pull-right">
        <div class='pull-right' style='margin-top:20px'>
            <h5>Already have a profile?</h5>

            <p class='pull-right'>
                <a href='/login'>
                    <button class='btn'>Login Now »</button>
                </a>
            </p>
        </div>
    </div>
</div>
<hr style='margin-top:0px'/>

<div class="row margin-fix">

<div class='span4 left-signup-pane'>
    <div class='check-username'></div>
</div>

<div class='span7 offset1'>
<form action='/signup/process' method='POST'
      style="padding: 15px; border-radius: 5px;background: #fff;">
<fieldset>
<div class="control-group">
    <label class="control-label" for="email">
        <strong>Email:</strong>
    </label>

    <div class="controls">
        <input class="input-xlarge"
               value="<?= $email; ?>"
               id="email"
               type="email"
               name='email'
               placeholder='Email'
               required
            >
    </div>
</div>

<div class="control-group pull-left" style='margin-right:15px'>
    <label class="control-label" for="password">
        <strong>Password:</strong>
    </label>

    <div class="controls">
        <input class="span"
               style='margin-bottom:0px'
               id="password"
               type="password"
               name='password'
               placeholder='Create a Password'
               pattern='.{6,}'
               title='Minimum length of 7 letters or numbers.'
               required
            >

        <p class='muted'>
            <small>Minimum length of 7 characters.</small>
        </p>
    </div>

</div>

<div class="control-group pull-left">
    <label class="control-label" for="confirm_password">
        <strong>Confirm Password:</strong>
    </label>

    <div class="controls">
        <input class="span"
               id="confirm_password"
               type="password"
               name='confirm_password'
               placeholder='Confirm Password'
               pattern='.{6,}'
               title='Minimum length of 7 letters or numbers.'
               required
            >
    </div>
</div>

<div class='clearfix'></div>

<div class="control-group">
    <label class="control-label" for="username">
        <strong>Pinterest Username:</strong>
    </label>

    <div class="controls">
        <div class="input-prepend pull-left" style='margin-bottom:0px'>
							    <span class="add-on">
                                    <i class="icon-user"></i> pinterest.com/
                                </span>
            <input style='width:200px;margin-left: -4px;'
                   value="<?= $username; ?>"
                   id="username"
                   type="text"
                   name='username'
                   placeholder='Username'
                   pattern='^[a-zA-Z0-9-_]{1,20}$'
                   title='Please include only your username, which should consist of only letters and numbers (no special characters). Thanks!'
                   required
                >
        </div>
        <div class='help-icon-form pull-left' style='margin:3px 0 0 5px;'>
            <a id='help-icon-link'
               data-toggle='popover'
               data-container='body'
               data-original-title='Not sure how to find your username?'
               data-content='Your Pinterest Username is found in the URL of your Pinterest profile: <span class="muted">http://pinterest.com/<strong style="color:#000">username</strong>/</span> <br><img class="img-rounded" src="/img/username-help.jpg">'
               data-trigger='hover'
               data-placement='top'>
                <i id='header-icon' class='icon-help'></i>
            </a>
        </div>
        <div class='clearfix'></div>
        <p class='muted'>
            <small>Please include <strong>only</strong> your username, not the full URL of your
                profile.
            </small>
        </p>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="site_address">
        <strong>Your Website URL
            <small>(that you'd like to track):</small>
        </strong>
    </label>

    <div class="controls">
        <div class="input-prepend" style='margin-bottom:0px'>
							    <span class="add-on">
                                    <i class="icon-earth"></i> http://
                                </span>
            <input class="input-large"
                   style='margin-left: -4px;'
                   data-minlength='0'
                   value="<?= $site_address; ?>"
                   id="site_address"
                   type="text"
                   name='site_address'
                   placeholder='e.g. "mysite.com"'
                   pattern='^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}$'
                >
        </div>
        <p class='muted'>
            <small class='muted'>If you do not have a website, please leave this field blank.
            </small>
        </p>
    </div>
</div>


<div class="control-group">
    <label class="control-label" for="org_name">
        <strong>Company / Organization:</strong>
    </label>

    <div class="controls">
        <input class="input-large"
               value="<?= $org_name; ?>"
               id="org_name"
               type="text"
               name='org_name'
               placeholder='e.g. "My Company"'>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="org_type">
        <strong>Which best describes this profile?</strong>
    </label>

    <div class="controls">
        <div class='row no-margin'>
            <div class='span5'>
                <label class='radio'>
                    <input type='radio'
                           name='org_type'
                           id='brand'
                           value='brand'
                        <?= $brand_checked; ?>
                        >
                    Brand/Business
                </label>
                <label class='radio'>
                    <input type='radio'
                           name='org_type'
                           id='agency'
                           value='agency'
                        <?= $agency_checked; ?>
                        >
                    Agency
                </label>
                <label class='radio'>
                    <input type='radio'
                           name='org_type'
                           id='agency_client'
                           value='agency_client'
                        <?= $agency_on_behalf_of_client_checked; ?>
                        >
                    Agency on behalf of Client
                </label>
            </div>
            <div class='span5'>
                <label class='radio'>
                    <input type='radio'
                           name='org_type'
                           id='non-profit'
                           value='non-profit'
                        <?= $non_profit_checked; ?>
                        >
                    Non-Profit
                </label>
                <label class='radio'>
                    <input type='radio'
                           name='org_type'
                           id='personal'
                           value='personal'
                        <?= $personal_checked; ?>
                        >
                    Personal/Individual
                </label>
                <label class='radio'>
                    <input type='radio'
                           name='org_type'
                           id='other'
                           value='other'
                        <?= $other_checked; ?>
                        >
                    Other
                </label>
            </div>
        </div>
    </div>
</div>


<div class="control-group pull-left" style='margin-right:15px'>
    <label class="control-label" for="first_name">
        <strong>First Name:</strong>
    </label>

    <div class="controls">
        <input class="span"
               value="<?= $firstname; ?>"
               id="firstname"
               type="text"
               name='first_name'
               placeholder='First Name'
            >
    </div>
</div>

<div class="control-group pull-left">
    <label class="control-label" for="last_name">
        <strong>Last Name:</strong>
    </label>

    <div class="controls">
        <input class="span"
               value="<?= $lastname; ?>"
               id="lastname"
               type="text"
               name='last_name'
               placeholder='Last Name'
            >
        <input value='<?= $chargify_id; ?>' id='chargify_id' type='hidden' name='chargify_id'>
        <input value='<?= $chargify_customer_id; ?>' id='chargify_customer_id' type='hidden'
               name='chargify_customer_id'>
        <input value='<?= $product_id; ?>' id='product' type='hidden' name='product'>
        <input value='' id='timezone' type='hidden' name='timezone'>
    </div>
</div>

<div class='clearfix'></div>

<div class="form-actions span6 margin-fix">
    <button type="submit"
            class="btn btn-success btn-large"
            onClick='return checkPassword();'>
        Create Account
    </button>
</div>
</fieldset>
</form>
</div>
</div>
</div>

<?php if ($include_mbsy) { ?>
    <script type="text/javascript">
        <!--
        var mbsy_username = 'pinleague'; // Required
        var mbsy_campaign_uid = 1441; // Required
        var mbsy_email = '<?= $email; ?>'; // Required - must be replaced with your customer's email
        var mbsy_revenue = '0.00'; // Optional - must be replaced with your revenue
        //-->
    </script>
    <script type="text/javascript" src="https://mbsy.co/embed/v2/tracker.js"></script>
<?php } ?>
</div>
<div>&nbsp;</div>
</div>
<?php
/*
$page="Signup";


$no_navigation = true;
$signup_form = true;





if(isset($_GET['firstname'])){
    $firstName = formatTextUserInput($_GET['firstname']);
}
if(isset($_GET['lastname'])){
    $lastName = formatTextUserInput($_GET['lastname']);
}
if(isset($_GET['email'])){
    $email = formatTextUserInput($_GET['email']);
}
if(isset($_GET['org_name'])){
    $org_name = formatTextUserInput($_GET['org_name']);
}
if(isset($_GET['org_type'])){
    $org_type = $_GET['org_type'];
}
if(isset($_GET['site_address'])){
    $site_address = str_replace('http://','',formatTextUserInput($_GET['site_address']));
}
if(isset($_GET['username'])){
    $username = formatTextUserInput($_GET['username']);
}
if(!isset($customer_id)){
    if(isset($_GET['c_id'])){
        $customer_id = formatTextUserInput($_GET['c_id']);
    } else {
        $customer_id = "";
    }
}


if (isset($_GET['e'])) {
    $e = $_GET['e'];
    if($e == 0){
        $error = "Whoops! Looks like there was an error creating your account.  Please try again
                    or contact us if the issue persists";
    }  else if ($e == 1) {
        $error = "Please fix the invalid email address.";
    } else if ($e == 3) {
        $error = "Password is an insufficient length.";
    } else if ($e == 4) {
        $error = "Passwords did not match.";
    } else if ($e == 5) {
        $error = "Email address has already been taken by another account.";
    } else if ($e == 6) {
        $error = "Please fill out all required fields.";
    } else if ($e == 7) {
        $er_username = $_GET['username'];
        $error = "<strong>Whoops!</strong> <a href='http://pinterest.com/$er_username' target='_blank'>http://pinterest.com/$er_username</a> does not seem to exist on Pinterest!  Please check the URL of the Pinterest profile you'd like to track. <span class='help-icon-form' style='margin:3px 0 0 5px;'>
				<a class='' data-toggle='popover' data-container='body' data-original-title='Not sure how to find your username?' data-content='Your Pinterest Username is found in the URL of your Pinterest profile: <span class=\"muted\">http://pinterest.com/<strong style=\"color:#000\">username</strong>/</span> <br><img class=\"img-rounded\" src=\"/v2/html/images/username-help.jpg\">' data-trigger='hover' data-placement='bottom'>
					 More info <i class='icon-info-2'></i>
				</a>
			</span>";
    }

    if($e=="a"){
        print "
			<div class=\"alert alert-success\">
				Thanks for trying our Audience Engine!  Please fill in any missing pieces to start tracking your profile with PinLeague Analytics!
			</div>";
    } else if($e=="b"){
        print "
			<div class=\"alert alert-success\">
				Thanks for signing up!  Please complete your account preferences to start tracking your profile with PinLeague Analytics!
			</div>";
    } else {
        print "
			<div class=\"alert alert-error\">
				$error
			</div>";
    }

    $firstName = formatTextUserInput($_GET['firstname']);
    $lastName = formatTextUserInput($_GET['lastname']);
    $email = formatTextUserInput($_GET['email']);
    $org_name = formatTextUserInput($_GET['org_name']);
    $org_type = $_GET['org_type'];
    $site_address = str_replace('http://','',formatTextUserInput($_GET['site_address']));
    $username = formatTextUserInput($_GET['username']);
    $customer_id = formatTextUserInput($_GET['c_id']);
} else {

    if(!isset($firstName)){
        if(isset($cust_first_name)){
            $firstName = $cust_first_name . "";
        } else {
            $firstName = "";
        }
    }
    if(!isset($lastName)){
        if(isset($cust_last_name)){
            $lastName = $cust_last_name . "";
        } else {
            $lastName = "";
        }
    }
    if(!isset($email)){
        if(isset($cust_email)){
            $email = $cust_email . "";
        } else {
            $email = "";
        }
    }
    if(!isset($org_name)){
        if(isset($cust_org)){
            $org_name = $cust_org . "";
        } else {
            $org_name = "";
        }
    }
    if(!isset($site_address)){
        $site_address = "";
    }
    if(!isset($username)){
        $username = "";
    }
    if(!isset($cust_product)){
        $cust_product = "";
    }
}

?>






<?php

		print "<div class='span4 left-signup-pane'>
					<div class='check-username'></div>
				</div>";

		print "<div class='span7 offset1'>";
			print "
			<form action='/signup/process' method='POST'>
				<fieldset>
					<div class=\"control-group\">
						<label class=\"control-label\" for=\"email\"><strong>Email:</strong></label>
						<div class=\"controls\">
							<input class=\"input-xlarge\" value=\"$email\" id=\"email\" type=\"email\" name='email' placeholder='Email' required>
						</div>
					</div>

					<div class=\"control-group pull-left\" style='margin-right:15px'>
						<label class=\"control-label\" for=\"password\"><strong>Password:</strong></label>
						<div class=\"controls\">
							<input class=\"span\" style='margin-bottom:0px' id=\"password\" type=\"password\" name='password' placeholder='Create a Password' pattern='.{6,}' title='Minimum length of 7 letters or numbers.' required>
							<p class='muted'>
								<small>Minimum length of 7 characters.</small>
							</p>
						</div>

					</div>

					<div class=\"control-group pull-left\">
						<label class=\"control-label\" for=\"confirm_password\"><strong>Confirm Password:</strong></label>
						<div class=\"controls\">
							<input class=\"span\" id=\"confirm_password\" type=\"password\" name='confirm_password' placeholder='Confirm Password' pattern='.{6,}' title='Minimum length of 7 letters or numbers.' required>
						</div>
					</div>

					<div class='clearfix'></div>

					<div class=\"control-group\">
						<label class=\"control-label\" for=\"username\"><strong>Pinterest Username:</strong></label>
						<div class=\"controls\">
							<div class=\"input-prepend pull-left\" style='margin-bottom:0px'>
							    <span class=\"add-on\"><i class=\"icon-user\"></i> pinterest.com/ </span>
								<input style='width:200px;margin-left: -4px;' value=\"$username\" id=\"username\" type=\"text\" name='username' placeholder='Username' pattern='^[a-zA-Z0-9-_]{1,20}$' title='Please include only your username, which should consist of only letters and numbers (no special characters). Thanks!' required>
							</div>
							<div class='help-icon-form pull-left' style='margin:3px 0 0 5px;'>
								<a id='help-icon-link' class='' data-toggle='popover' data-container='body' data-original-title='Not sure how to find your username?' data-content='Your Pinterest Username is found in the URL of your Pinterest profile: <span class=\"muted\">http://pinterest.com/<strong style=\"color:#000\">username</strong>/</span> <br><img class=\"img-rounded\" src=\"/v2/html/images/username-help.jpg\">' data-trigger='hover' data-placement='top'>
									<i id='header-icon' class='icon-help'></i>
								</a>
							</div>
							<div class='clearfix'></div>
							<p class='muted'>
								<small>Please include <strong>only</strong> your username, not the full URL of your profile.</small>
							</p>
						</div>
					</div>

					<div class=\"control-group\">
						<label class=\"control-label\" for=\"site_address\"><strong>Your Website URL <small>(that you'd like to track):</small></strong></label>
						<div class=\"controls\">
							<div class=\"input-prepend\" style='margin-bottom:0px'>
							    <span class=\"add-on\"><i class=\"icon-earth\"></i> http:// </span>
								<input class=\"input-large\" style='margin-left: -4px;' data-minlength='0' value=\"$site_address\" id=\"site_address\" type=\"text\" name='site_address' placeholder='e.g. \"mysite.com\"' pattern='^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}$'>
							</div>
							<p class='muted'>
								<small class='muted'>If you do not have a website, please leave this field blank.</small>
							</p>
						</div>
					</div>


					<div class=\"control-group\">
						<label class=\"control-label\" for=\"org_name\"><strong>Company / Organization:</strong></label>
						<div class=\"controls\">
							<input class=\"input-large\" value=\"$org_name\" id=\"org_name\" type=\"text\" name='org_name' placeholder='e.g. \"My Company\"'>
						</div>
					</div>";


                    $org_select_brand = "";
                    $org_select_agency = "";
                    $org_select_agency_client = "";
                    $org_select_non_profit = "";
                    $org_select_personal = "";
                    $org_select_other = "";


                    if(isset($org_type)){
                        if($org_type=="brand"){
                            $org_select_brand = "checked";
                        } elseif ($org_type=="agency") {
                            $org_select_agency = "checked";
                        } elseif ($org_type=="agency_client") {
                            $org_select_agency_client = "checked";
                        } elseif ($org_type=="non-profit") {
                            $org_select_non_profit = "checked";
                        } elseif ($org_type=="personal") {
                            $org_select_personal = "checked";
                        } elseif ($org_type=="other") {
                            $org_select_other = "checked";
                        }
                    }



			print "	<div class=\"control-group\">
						<label class=\"control-label\" for=\"org_type\"><strong>Which best describes this profile?</strong></label>
						<div class=\"controls\">
						<div class='row no-margin'>
							<div class='span5'>
								<label class='radio'>
								  <input type='radio' name='org_type' id='brand' value='brand' $org_select_brand>
								  Brand/Business
								</label>
								<label class='radio'>
								  <input type='radio' name='org_type' id='agency' value='agency' $org_select_agency>
								  Agency
								</label>
								<label class='radio'>
								  <input type='radio' name='org_type' id='agency_client' value='agency_client' $org_select_agency_client>
								  Agency on behalf of Client
								</label>
							</div>
							<div class='span5'>
								<label class='radio'>
								  <input type='radio' name='org_type' id='non-profit' value='non-profit' $org_select_non_profit>
								  Non-Profit
								</label>
								<label class='radio'>
								  <input type='radio' name='org_type' id='personal' value='personal' $org_select_personal>
								  Personal/Individual
								</label>
								<label class='radio'>
								  <input type='radio' name='org_type' id='other' value='other' $org_select_other>
								  Other
								</label>
							</div>
						</div>
						</div>
					</div>


					<div class=\"control-group pull-left\" style='margin-right:15px'>
						<label class=\"control-label\" for=\"first_name\"><strong>First Name:</strong></label>
						<div class=\"controls\">
							<input class=\"span\" value=\"$firstName\" id=\"firstname\" type=\"text\" name='first_name' placeholder='First Name'>
						</div>
					</div>

					<div class=\"control-group pull-left\">
						<label class=\"control-label\" for=\"last_name\"><strong>Last Name:</strong></label>
						<div class=\"controls\">
							<input class=\"span\" value=\"$lastName\" id=\"lastname\" type=\"text\" name='last_name' placeholder='Last Name'>
							<input class='' value='$customer_id' id='chargify_id' type='hidden' name='chargify_id'>
							<input class='' value='$cust_product' id='product' type='hidden' name='product'>
							<input class='' value='' id='timezone' type='hidden' name='timezone'>
						</div>
					</div>

                    <div class='clearfix'></div>

					<div class=\"form-actions span6 margin-fix\">
						<button type=\"submit\" class=\"btn btn-success btn-large\" onClick='return checkPassword();'>Create Account</button>
					</div>
				</fieldset>
			</form>";

		print "</div>";

	print "
	</div>";

	print "
		<script language='javascript' type='text/javascript'>

			//$('#timezone').val(offsetFormat);

		</script>";


	if(isset($_GET['id'])){
        ?>

        <script type="text/javascript">
            <!--
            var mbsy_username='pinleague'; // Required
            var mbsy_campaign_uid=1441; // Required
            var mbsy_email='<?php echo $cust_email; ?>'; // Required - must be replaced with your customer's email
            var mbsy_revenue='0.00'; // Optional - must be replaced with your revenue
            //-->
        </script>
        <script type="text/javascript" src="https://mbsy.co/embed/v2/tracker.js"></script>


    <?php
    }
