<!-- bad: less than 1 pin/day -->

<span class="headline">Found <strong><?= $pin_count; ?> pins!</strong></span>
<br><br>
You're pinning about <strong class="primary emphasis"><?= $pins_per_day ?> times per day</strong>.
<br><br><strong class='secondary emphasis'>Tip: </strong>The most successful brands usually pin more than 3 times per day.

<?php
    $pre_cta = "Sign up to see your trending pins for more pinning ideas";

    echo "<span class='pre-cta'>$pre_cta</span>";
?>
