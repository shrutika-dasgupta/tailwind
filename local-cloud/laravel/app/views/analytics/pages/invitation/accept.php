<script language='javascript' type='text/javascript'>
    function checkPassword() {
        if (document.getElementById('password').value != document.getElementById('confirm').value) {
            alert ('The passwords do not match!');
            return false;
        }
    }
</script>

<div class="row-fluid login-wrapper">
    <a>
    </a>
    <div class="span4 box">
        <div class="content-wrap">

            <form action='/password-reset/process' method='POST' id='pw1' class="form-horizontal">
                <fieldset>

                    <legend>Hi <?=$first_name;?>, <br>Please Create a Password to Get Started</legend>
                    <br>
                    <br>
                    <label><strong>Enter your New Password:</strong></label>

                    <input class='' type='password' name='password' id='password'
                           placeholder='password' pattern='.{6,}'
                           title='Minimum length of 7 letters or numbers.' required />
                    <br><small class='muted'>Minimum length of 7 characters.</small>

                    <br>
                    <br>
                    <label><strong>Confirm Password:</strong></label>
                    <input class='' type='password' name='confirm' id='confirm'
                           pattern='.{6,}' placeholder='confirm password'
                           title='Minimum length of 7 letters or numbers.' required />

                    <input type='hidden' name='check' id='check' value='reset'>
                    <input type='hidden' name='uid' id='uid' value='<?=$cust_id;?>'>
                    <input type='hidden' name='rid' id='rid' value='<?=$rid;?>'>

                    <div class="form-actions" style='text-align:center; padding-left:0px; padding-right:0px;'>
                        <br>

                        <button type='submit' class='btn btn-success btn-large'
                                onClick='return checkPassword();'>
                            Create Your New Password
                        </button>
                    </div>
                    <br>
                </fieldset>
            </form>

        </div>
    </div>
</div>