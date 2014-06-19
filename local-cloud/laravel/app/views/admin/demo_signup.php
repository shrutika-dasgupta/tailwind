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
        <h3>Create a New Demo Pro Account</h3>
    </div>
    <div class="span4 pull-right">
    </div>
</div>
<hr style='margin-top:0px'/>

<div class="row margin-fix">

<div class='span4 left-signup-pane'>
    <div class='check-username'></div>
</div>

<div class='span7 offset1'>
<form action='/demo/new/create' method='POST'
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

        <input type="hidden" name="email_address">
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


</div>
<div>&nbsp;</div>
</div>