<div id="demo-bar" class="top-notification">
    <div class="row-fluid">
        <div class="span3 text-left">
            <div style="padding: 0 25px">

                <?php if ($is_incomplete_signup) {?>
                    <a id="complete_signup" data-toggle="modal" data-target="#claimDashboard" class="btn muted">
                        <i class="icon-arrow-left"></i>
                        Create Free Dashboard for <?= $username;?>
                    </a>


                    <div id="claimDashboard" class="modal hide fade" style=" top:5%; bottom:5%;width: 700px; margin-left: -350px;">
                        <div class="modal-body" style="max-height:100%; height:85%;">

                            <div class="upgrade-modal">

                                <div class="row-fluid">
                                    <div class="span12" style="border-bottom:1px solid rgba(0,0,0,0.1); margin-bottom:15px;">

                                        <h2 style="color:#000; text-shadow:none;">Create a Free Dashboard for <?=$username;?></h2>

                                    </div>
                                </div>
                                <div class="row-fluid"  style="border-bottom:1px solid rgba(0,0,0,0.1); margin-bottom:25px;">
                                    <p style=" color: #333333;
      text-shadow: none; font-size: 13px">
                                        <h5 style="color:#000;text-shadow:none;">Free Starter Plan includes:</h5>
                                        <ul style="color:#000;text-shadow:none;">
                                            <li>Basic Profile and Board Metrics</li>
                                            <li>Basic Domain Metrics </li>
                                            <li>Tools to Engage your Newest Followers.</li>
                                        </ul>
                                    </p>
                                </div>

                            </div>
                            <form method="get" action="/demo/claim-dashboard" style="color: #333333;
      text-shadow: none;">
                                <div class="row-fluid">
                                    <div class="span2">
                                        <label>Email</label>
                                    </div>
                                    <div class="span6">
                                        <input style=" box-sizing: border-box;
                                                        height: 30px;
                                                        position: relative;
                                                        width: 100%"
                                               type="text"
                                               name="email"
                                               value="<?= $email;?> " disabled/>
                                    </div>
                                </div>
                                <div class="row-fluid">
                                    <div class="span2">
                                        <label for="password">Password</label>
                                    </div>
                                    <div class="span6">
                                        <input style=" box-sizing: border-box;
                                                        height: 30px;
                                                        position: relative;
                                                        width: 100%"
                                               type="password"
                                               name="password"
                                               id="password_input"
                                               pattern='.{6,}'
                                               placeholder="Enter a Password"
                                               title='Minimum length of 7 letters or numbers.' required>
                                    </div>
                                </div>
                                <div class="row-fluid">
                                    <div class="span8">
                                        <button type="submit" class="btn btn-large pull-right btn-success">Create Free Dashboard</button>
                                    </div>
                                </div>
                            </form>

<!--                            <div class="row-fluid">-->
<!--                                <div class="span2">-->
<!--                                    <img class="pull-right" src="--><?//=$profile_image;?><!--" />-->
<!--                                </div>-->
<!--                                --><?php //foreach ($pins as $pin) {?>
<!--                                    <div class="span1">-->
<!--                                        <img src="--><?//=$pin->image_square_url;?><!-- "/>-->
<!--                                    </div>-->
<!--                                --><?php //} ?>
<!--                            </div>-->

                        </div>

                        <div class="modal-footer">
                            &nbsp;
                        </div>
                    </div>

                    <div id="finishSignup" class="modal hide fade" style=" top:5%; bottom:5%;width: 700px; margin-left: -350px;">
                        <div class="modal-body" style="max-height:100%; height:85%;">

                            <div class="upgrade-modal">

                                <div class="row-fluid">
                                    <div class="span12" style="border-bottom:1px solid rgba(0,0,0,0.1); margin-bottom:15px;">

                                        <h2 style="color:#000; text-shadow:none;">Complete Your Registration</h2>

                                    </div>
                                </div>
                                <div class="row-fluid"  style="border-bottom:1px solid rgba(0,0,0,0.1); margin-bottom:25px;">
                                    <p style=" color: #333333;
      text-shadow: none; font-size: 13px">
                                    <h5 style="color:#000;text-shadow:none;">Before selecting a plan, lets set your password first:</h5>
                                    </p>
                                </div>

                            </div>
                            <form method="get" action="/demo/finish-signup" style="color: #333333;
      text-shadow: none;">
                                <div class="row-fluid">
                                    <div class="span2">
                                        <label>Email</label>
                                    </div>
                                    <div class="span6">
                                        <input style=" box-sizing: border-box;
                                                        height: 30px;
                                                        position: relative;
                                                        width: 100%"
                                               type="text"
                                               name="email"
                                               value="<?= $email;?> " disabled/>
                                    </div>
                                </div>
                                <div class="row-fluid">
                                    <div class="span2">
                                        <label for="password">Password</label>
                                    </div>
                                    <div class="span6">
                                        <input style=" box-sizing: border-box;
                                                        height: 30px;
                                                        position: relative;
                                                        width: 100%"
                                               type="password"
                                               name="password"
                                               id="password_input"
                                               pattern='.{6,}'
                                               placeholder="Enter a Password"
                                               title='Minimum length of 7 letters or numbers.' required>
                                    </div>
                                </div>
                                <div class="row-fluid">
                                    <div class="span8">
                                        <button type="submit" class="btn btn-large pull-right btn-success">Complete Registration & Select Plan →</button>
                                    </div>
                                </div>
                            </form>


                        </div>
                        <div class="modal-footer">
                            &nbsp;
                        </div>
                    </div>



                <?php } else { ?>
                    <a href="/demo/off" class="btn muted">
                        <i class="icon-arrow-left"></i>
                        Back to my account
                    </a>
                <?php } ?>

            </div>
        </div>
        <div class="span6 text-center">
            <div style="padding: 0 25px">
                This is a demo of a
                <div class="btn-group" data-toggle="buttons-radio">
                    <a href="/demo/lite?follow=back" class="btn <?=$lite_toggle_class;?>">Lite</a>
                    <a href="/demo/pro?follow=back" class="btn <?=$pro_toggle_class;?>">Pro</a>
                </div>
                account.
            </div>

        </div>
        <div class="span3 text-right">
            <div style="padding: 0 25px">

                <?php if ($is_incomplete_signup) {?>
                    <a id=select_plan" data-toggle="modal" data-target="#finishSignup" class="btn btn-success">Select a Plan</a>
                    <a href="mailto:bd@tailwindapp.com" class="btn">Contact Sales</a>
                <?php } else { ?>
                    <a href="/demo/upgrade" class="btn btn-success">Upgrade Your Account</a>
                <?php } ?>

            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function(){
        $('.wrapper').css('top','50px');

        $('#complete_signup').on('click',function(){
            setTimeout(function(){
                $('#password_input').show().blur();
                $('#password_input').focus();
            },1000);
        });
    })
</script>
