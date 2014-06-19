<?php ini_set('display_errors', 'off');
error_reporting(0);
$page = "Boards";

?>

<div class='clearfix'></div>
<div class=''>


<div class='accordion' id='accordion3' style='margin-bottom:25px'>
    <div class='accordion-group' style='margin-bottom:0px; border-bottom:none;'>
        <div class='section-sub-header'>
        </div>

        <div class='clearfix section-header'>
        </div>

        <div class='row no-margin boards-sub-header'>

            <div class='pull-left' style="margin: 8px 0 8px 11px;">
                <span class="btn-group" data-toggle="buttons-radio">
    <!--                <a href="/boards?metric=Repin">-->
                        <button type="button" class="btn board-repin-toggle active">
                            Repins
                        </button>
    <!--                </a>-->
    <!--                <a href="/boards?metric=Like">-->
                        <button type="button" class="btn board-like-toggle">
                            Likes
                        </button>
    <!--                </a>-->
                        <button type="button" class="btn board-comment-toggle">
                            Comments
                        </button>
                </span>
            </div>


        </div>
    </div>

    <div class='clearfix'></div>
<?= $popover_custom_date; ?>
<?= $export_popover; ?>
<?php //echo $export_button; ?>

    <div id='collapseThree' class='accordion-body collapse in'>
        <div class='accordion-inner' style='padding-top:15px;'>

            <div class="row no-margin" style='margin-bottom:10px;'>

                <div class="" style='text-align:center;padding-bottom:6px;padding-top:3px'>


                    <div id='dynamic' style='float:left; margin-bottom:35px; width: 100%;'></div>


                </div>
            </div>


        </div>
    </div>
    </div>
    </div>
