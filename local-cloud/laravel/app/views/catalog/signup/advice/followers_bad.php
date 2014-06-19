<!--BAD: Less than 10 followers per week -->

<span class="headline">
    <span class="primary emphasis">Not too shabby..</span>
    <strong><?= $followers; ?> Followers!</strong>
</span>
<br><br>You're gaining about
<span class="secondary emphasis"><u><?= $followers_per_week; ?> New Followers per week</u></span>!

<?php
    $pre_cta = "Sign up to meet your Most Influential Followers";

    echo "<span class='pre-cta'>$pre_cta</span>";
?>