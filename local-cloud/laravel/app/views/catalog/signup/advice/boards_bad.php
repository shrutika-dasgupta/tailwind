<!-- BAD: less than 10 boards or less than 2 categories-->

<!--  ****  make sure to exclude the "none" and [empty] categories from the total count of different categories -->


<?php
    if($num_boards > 5){

        echo "<span class='headline'>You've got <strong>".$num_boards." Boards</strong>";

        if($num_categories > 1){
            echo " in <strong>".$num_categories." different categories</strong></span>.
            <br><br><strong class='secondary emphasis'>Tip: </strong>
            Your fans would love to see a few more boards about $top_category!";

            $pre_cta = "Sign up to see your Most Viral Boards";

            echo "<span class='pre-cta'>$pre_cta</span>";

        } else if($num_categories == 1){
            echo " mostly about ".$top_category."</span>.
            <br><br><strong class='secondary emphasis'>Tip: </strong>
            Pinning in more categories can really help grow your fanbase!";

            $pre_cta = "Sign up to see your Most Viral Boards";

            echo "<span class='pre-cta'>$pre_cta</span>";

        } else {
            echo "</span>.
            <br><br><strong class='secondary emphasis'>Tip: </strong>
            Make it easy for pinners find your boards by adding categories!";

            $pre_cta = "Sign up to see your Most Viral Boards";

            echo "<span class='pre-cta'>$pre_cta</span>";
        }
    } else {
        echo "
        <span class='headline'>
            <span class='primary emphasis'>Not bad!</span>
            Found <strong>".$num_boards." Boards</strong>
        </span>.
        <br><br><strong class='secondary emphasis'>Tip: </strong>
        Top brands on Pinterest usually have at least 15-20 boards.
        Don't worry, we can help you come up with more board ideas :)";

        $pre_cta = "Sign up to get more board ideas";

        echo "<span class='pre-cta'>$pre_cta</span>";
    }
?>

