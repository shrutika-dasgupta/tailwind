<div class="call-to-action <?= $cta_help_class; ?>">

    <div class="row hide-for-medium-up" style="padding-top:30px;">

    </div>

    <div class="row">
        <div class="small-10 columns small-centered bg-wrap">

            <form action="/signup/leads/" method="get">
                <div class="row">
                    <div class="large-3 small-0 columns">
                        <div class="cell">
                            <i class="icon-angle-right hide-for-small"></i>
                            <h4>Get Started</h4>
                        </div>
                    </div>
                    <div class="large-5 small-12 columns">
                        <div class="cell control-group">
                            <input class="enter-username" name="username" type="text"
                                   value="<?= $username; ?>"
                                   placeholder="Enter your Pinterest username"
                                   pattern='^[a-zA-Z0-9-_]{1,20}$'
                                   title='Your username should only have letters
                               and numbers (no spaces!)'
                                   oninvalid="setCustomValidity('Please include only your username')"
                                   onchange="try{setCustomValidity('')}catch(e){}"/>
                        </div>
                    <span class="input-help">
                        <i data-dropdown="username-help" class="icon-help"></i>
                    </span>

                        <div class="issue-help">
                            <?= $message; ?>
                        </div>

                    </div>
                    <div class="large-4 small-12 columns">
                        <div class="cell">
                            <button type="submit" class="btn btn-gold btn-cta"
                                    onclick="analytics.page('/funnel/signupcta_<?= $page_name; ?>');">
                                Transfer Your Account
                            </button>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>
<ul id="username-help" class="f-dropdown content username-help" data-dropdown-content>
    <p>Your Pinterest Username is found in the URL of your Pinterest profile:</p>
    <span class="muted">http://pinterest.com/<strong style="color:#000">username</strong>/</span>
    <br><img src="http://d3iavkk36ob651.cloudfront.net/img/username-example.jpg">
</ul>
