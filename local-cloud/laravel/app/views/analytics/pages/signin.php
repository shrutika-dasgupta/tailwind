<div class="row-fluid login-wrapper">
    <a>
    </a>

    <div class="span4 box">
        <div class="content-wrap" style="width:82%;">
            <a href='http://www.tailwindapp.com' alt='Tailwind Pinterest Analytics and Marketing Tools'><img class="logo" src="/img/tailwind-logo.png" style="margin-bottom:30px;"></a>
            <?= $alert; ?>
            <form action="/login/check" method="post">
                <input type="hidden" name="redirect_to" value="<?= $redirect_to;?>" />
                <input type="hidden" name="attempts" value="<?= $attempts;?>" />

                <input class="span12" type="text" value="<?= $email; ?>" placeholder="Email" name="email" autofocus />
                <input class="span12" type="password" placeholder="Password" name="password">
                <a href="/password-reset/" class="forgot">Forgot password?</a>
                <div class="remember">
                    <input id="remember-me" type="checkbox">
                    <label for="remember-me">Remember me</label>
                </div>
                <input type="submit" class="btn btn-primary" value="Login">

            </form>

        </div>
    </div>

    <div class="span4 no-account">
        <p>Don't have an account?</p>
        <a href="//www.tailwindapp.com/">Sign up</a>
    </div>
</div>