<!-- GOOD: more than 10 boards and more than 2 categories-->

<!--  ****  make sure to exclude the "none" and [empty] categories from the total count of different categories -->

<span class="headline">
    <span class="primary emphasis">Nice! </span>
    You've got <strong><?= $num_boards; ?> Boards</strong> in
    <strong><?= $num_categories; ?> different categories</strong>!</span>
<br><br>You're pretty active in the
<br><span class="secondary emphasis"><u><?= $top_category; ?></u></span> category, huh?

<?php
    $pre_cta = "Sign Up to see your Most Viral Boards";

    echo "<span class='pre-cta'>$pre_cta</span>";
?>
