Hi <?= $first_name ?>,

<?= $sender_full_name ?> has invited you to collaborate on their Tailwind Pinterest Analytics Dashboard!


You've been given access to:
 - Measure their Pinterest profile (http://pinterest.com/<?= $username ?>) performance
<?php if (!empty($domain)): ?>
 - Discover Trending Pins from <?= $domain ?>
<?php endif ?>
- Find Top Repinners and Influencers
<?php if (empty($domain)): ?>
 - Compare against Competitors
<?php endif ?>
- And much more!


Login now to set your password and explore the dashboard:

<?= $accept_invite_url ?>

If you don't want to accept the invitation, just ignore this email and your account will not be created.

If you have any questions, feel free to reply directly to this email or give us a shout at help@tailwindapp.com.


Cheers,

The Tailwind Team
