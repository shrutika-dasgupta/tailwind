Hi <?= $first_name ?>,
<br /><br />
<strong><?= $sender_full_name ?> has invited you to collaborate</strong> on their Tailwind Pinterest Analytics Dashboard!
<br /><br />
You've been given access to:
<ul>
    <li>Measure their <strong><a href="http://pinterest.com/<?= $username ?>" target="_blank">Pinterest profile</a> performance</strong></li>

    <?php if (!empty($domain)): ?>
        <li><strong>Discover Trending Pins</strong> from <?= $domain ?></li>
    <?php endif ?>

    <li><strong>Find Top Repinners</strong> and Influencers</li>

    <?php if (empty($domain)): ?>
        <li>Compare against Competitors</li>
    <?php endif ?>

    <li>And much more!</li>
</ul>
<br />
<center>
    Login now to set your password and explore the dashboard:
    <br /><br /><br />
    <a href="<?= $accept_invite_url ?>" style="color:#FFFFFF; background-color:#0793CA; text-decoration:none; padding:10px; border-top-left-radius:5px; border-top-right-radius:5px; border-bottom-right-radius:5px; border-bottom-left-radius:5px;">
        Accept Your Invitation
    </a>
    <br /><br />
    <em style="font-size:10px">
        If you don't want to accept the invitation, just ignore this email and your account will not be created.
    </em>
</center>
<br /><br />
If you have any questions, feel free to reply directly to this email or give us a shout at <a href="mailto:help@tailwindapp.com?subject=Dashboard%20Invitation">help@tailwindapp.com</a>.
<br /><br /><br />
Cheers,
<br /><br />
The Tailwind Team
