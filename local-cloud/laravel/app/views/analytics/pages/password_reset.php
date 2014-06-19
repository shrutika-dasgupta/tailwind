<?php if (isset($message)): ?>
    <?php if ($message == 1): ?>
        <div class="row-fluid login-wrapper">
            <span></span>
            <div class="span4 box">
                <div class="content-wrap">
                    <div class="alert alert-success">
                        <strong class='lead'>Check Your Inbox!</strong>
                        <br /><br />
                        You should receive an email shortly with a secure link to reset your password.
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($message == 2): ?>
        <div class="row-fluid login-wrapper">
            <span></span>
            <div class="span4 box">
                <div class="content-wrap">
                    <div class="alert alert-error" style='text-align: left'>
                        <strong class='lead'>Oh Snap!</strong>
                        <br><br>The email you entered does not appear to be in our system.  Did you sign up with a different one?  Please try again.
                    </div>
                </div>
            </div>
        </div>
    <?php endif ?>
<?php endif ?>

<?php if (isset($reset)): ?>
    <?php if ($reset == 1): ?>
        <div class="row-fluid login-wrapper">
            <span></span>
            <div class="span4 box">
                <div class="content-wrap">
                    <form action='/password-reset/check' method='POST' class="form-horizontal">
                        <fieldset>
                            <legend>Reset Your Password</legend>
                            <br><br>
                            <label>Enter your email address to reset your password:</label><br>

                            <input class='inline' type='email' name='email' id='email' placeholder='Email' required autofocus />
                            <input type='hidden' name='check' id='check' value='yes'>

                            <button type='submit' class='btn btn-info'>Submit</button>
                            <br><br><small class='muted'>You'll receive an email containing a secure link to reset it.</small>
                        </fieldset>
                    </form>
                </div>
            </div>
        </div>
    <?php endif ?>
<?php endif ?>

<?php if (isset($uid) && isset($rid)): ?>
    <?php if (md5($uid.date("Y-m-d")) == $rid): ?>
        <?php
            $reset_user = User::find($uid);
            $first_name = $reset_user->first_name;
        ?>
        <div class="row-fluid login-wrapper">
            <span></span>
            <div class="span4 box">
                <div class="content-wrap">
                    <form action='/password-reset/process' method='POST' id='pw1' class="form-horizontal">
                        <fieldset>
                        <script language='javascript' type='text/javascript'>
                        function checkPassword() {
                            if (document.getElementById('password').value != document.getElementById('confirm').value) {
                                alert ('The passwords do not match!');
                                return false;
                            }
                        }
                        </script>

                        <legend>Hi <?=$first_name;?>, <br>Please Create a New Password</legend>
                        <br><br>
                        <label><strong>Enter your New Password:</strong></label>

                        <input class='' type='password' name='password' id='password' placeholder='password' pattern='.{6,}' title='Minimum length of 7 letters or numbers.' required />
                        <br><small class='muted'>Minimum length of 7 characters.</small>

                        <br><br><label><strong>Confirm Password:</strong></label>
                        <input class='' type='password' name='confirm' id='confirm' pattern='.{6,}' placeholder='confirm passowrd' title='Minimum length of 7 letters or numbers.' required />

                        <input type='hidden' name='check' id='check' value='reset'>
                        <input type='hidden' name='uid' id='uid' value='<?=$uid;?>'>
                        <input type='hidden' name='rid' id='rid' value='<?=md5($uid.date('Y-m-d'));?>'>
                        <div class="form-actions" style='text-align:center; padding-left:0px; padding-right:0px;'>
                            <br><button type='submit' class='btn btn-success btn-large' onClick='return checkPassword();'>Create Your New Password</button>
                        </div>
                        <br>
                        </fieldset>
                    </form>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row-fluid login-wrapper">
            <span></span>
            <div class="span4 box">
                <div class="content-wrap">
                    <div class="alert alert-error" style='text-align: left'>
                        <strong class='lead'>Oh oh! </strong>
                        <br><br>The page you’re looking for does not exist or may have expired.
                        <br><hr><br><a href='/login'>Login → </a>
                        <br><br><a href='/password-reset'>Reset your Password → </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif ?>
<?php endif ?>
