<!-- Great: More than 50 followers per week -->

<span class="headline">
    Found <strong><?= $followers; ?> Followers... </strong>
    <span class="primary emphasis">You're on a roll!</span>
</span>
<br><br>You're gaining about
<span class="secondary emphasis"><u><?= $followers_per_week; ?> New Followers per week</u></span>!

<?php
    $pre_cta = "Sign up to meet your Most Influential Followers";

    echo "<span class='pre-cta'>$pre_cta</span>";
?>