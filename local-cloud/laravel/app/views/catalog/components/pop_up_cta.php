<div id="popUpCTA">
    <div class="background"
         onclick="jQuery('#popUpCTA').hide(); return false;"></div>

    <div class="complicated-wrap">
        <div class="row popup-box">
            <div class="small-12 columns">
                <div class="row">
                    <div class="small-12 columns">
                        <a class="cta-close" href="#"
                           onclick="jQuery('#popUpCTA').hide(); return false">×</a>
                        <br>&nbsp;
                    </div>
                </div>
                <div class="row">
                    <div class="small-12 columns">
                        <h2>Get a <strong>Free</strong> account</h2>
                    </div>
                </div>
                <?= $call_to_action; ?>
                <div class="row">
                    <div class="small-12 columns">
                        <h3>No credit card required.</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="demoSignup">
    <div class="background"
         onclick="jQuery('#demoSignup').hide(); return false;"></div>

    <script language='javascript' type='text/javascript'>
        function checkForm() {
            if (document.getElementById('name_2').value == '') {
                alert('Please enter your name');
                return false;
            }
            if (document.getElementById('enter-email').value == ''
                || document.getElementById('enter-email').value.indexOf('@') == -1
                || document.getElementById('enter-email').value.indexOf('.') == -1) {
                alert('Please enter a valid email!');
                return false;
            }
            if (document.getElementById('domain').value == ''
                || document.getElementById('domain').value.indexOf('.') == -1
                || document.getElementById('domain').value.indexOf('/') > -1) {
                alert('Please enter a valid website! (no "http://" or "www" necessary).');
                return false;
            }
            if (document.getElementById('domain').value == ''
                || document.getElementById('domain').value.indexOf('.') == -1
                || document.getElementById('domain').value.indexOf('/') > -1) {
                alert('Please enter a valid website! (no "http://" or "www" necessary).');
                return false;
            }
            if (document.getElementById('username-input').value == ''
                || document.getElementById('username-input').value.length < 2) {
                alert('Please enter a valid Pinterest username!.');
                return false;
            }
        }
    </script>

    <div class="complicated-wrap">
        <div class="row popup-box">
            <div class="small-12 columns">
                <div class="row">
                    <div class="small-12 columns">
                        <a class="cta-close" href="#"
                           onclick="jQuery('#demoSignup').hide(); return false">×</a>
                        <br>&nbsp;
                    </div>
                </div>
                <div class="small-12 large-8 large-centered columns">
                <form action="//<?= ANALYTICS_URL; ?>/signup/demo/create" method="post">
                    <input type="hidden" name="source" value="<?= Cookie::get('source',''); ?>" />

                    <div class="row" style="border-bottom:1px solid rgba(0,0,0,0.1); margin-bottom:20px;">

                        <div class="small-12 columns" style="text-align:center">
                            <h1 style="margin:10px;">Get a Live Demo of Tailwind!</h1>
                            <input placeholder='Email' type="email" name="email_address" style="left:-9000px;position:absolute;">
                        </div>
                    </div>

                    <div class="row">
                        <div class="small-6 columns">
                            Name
                        </div>

                        <div class="small-6 columns">
                            <input type="text" name="name" style="display:none;" value=""/>
                            <input id="name_2" class="enter-name" type="text" placeholder="Your Name" name="name_2" required>
                        </div>

                    </div>

                    <div class="row">
                        <div class="small-6 columns">
                            Email
                        </div>

                        <div class="small-6 columns">
                            <input id="enter-email" placeholder='Email' type="email" name="email" required>
                        </div>

                    </div>
                    <div class="row">
                        <div class="small-6 columns">
                            Pinterest Username
                        </div>

                        <div class="small-6 columns">
                            <input id="username-input" class="enter-username" name="username" type="text"
                                   value="<?= $username; ?>"
                                   placeholder="Pinterest Username"
                                   pattern='^[a-zA-Z0-9-_]{1,20}$'
                                   title='Your username should only have letters
                               and numbers (no spaces!)'
                                   oninvalid="setCustomValidity('Please include only your username')"
                                   onchange="try{setCustomValidity('')}catch(e){}"
                                   required>
                        </div>

                    </div>

                    <div class="row">
                        <div class="small-6 columns">
                            Company Name
                        </div>

                        <div class="small-6 columns">
                            <input id="company"
                                   type="text"
                                   name='company'
                                   placeholder='Company Name'

                                >
                        </div>
                    </div>

                    <div class="row">
                        <div class="small-6 columns">
                            Website
                        </div>

                        <div class="small-6 columns">
                            <input id="domain"
                                   type="text"
                                   name='domain'
                                   placeholder='e.g. "mysite.com"'
                                   pattern='^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}$'
                                >
                        </div>
                    </div>

                    <div class="row">
                        <div class="small-6 columns">
                            Are you a brand or agency?
                        </div>

                        <div class="small-6 columns">
                            <select name="type">
                                <option value="brand">Brand</option>
                                <option value="agency">Agency</option>
                                <option value="non-profit">Non-profit</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        &nbsp;
                    </div>

<!--                    <div class="row">-->
<!--                        <div class="small-6 columns">-->
<!--                            Why are you Interested in Tailwind-->
<!--                        </div>-->
<!---->
<!--                        <div class="small-6 columns">-->
<!--                            <input type="checkbox" name="interest" value="Clients Deserve the Best" style="width: 25px;">Our Clients Deserve the Best-->
<!--                            <br><input type="checkbox" name="interest" value="Expand Product Offering" style="width: 25px;">Expand our Product Offering-->
<!--                            <br><input type="checkbox" name="interest" value="Save Time" style="width: 25px;">Save Time, Increase Efficiency-->
<!--                            <br><input type="checkbox" name="interest" value="Replace Existing Provider" style="width: 25px;">Replace and Existing Provider-->
<!--                            <br><input type="checkbox" name="interest" value="Other" style="width: 25px;">Other-->
<!--                        </div>-->
<!---->
<!--                    </div>-->

                    <div class="row">
                        <div class="small-12 columns">
                            <button type="submit"
                                    class="button btn btn-warning btn-cta"
                                    onClick='return checkForm();'
                                >View Demo</button>
                        </div>
                    </div>

                </div>
                </form>

            </div>
        </div>
    </div>
</div>