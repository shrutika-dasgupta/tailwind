<?php ini_set('display_errors', 'off');
ini_set('memory_limit', '5000M');
error_reporting(0);

$page = "Profile Pins";
?>

<?php if($report_url == "pins/owned"){ ?>
    <div class="row margin-fix">
        <?=$pins_upgrade_alert;?>

    </div>
<?php } ?>



<div class=''>
    <div class='accordion' id='accordion4' style='margin-bottom:25px'>
	    <div class='accordion-group' style='margin-bottom:25px'>
	        <div class='accordion-heading pins'>
	    	    <div class='row no-margin subheader'>
                    <div class='pull-left'> <!--1-->
                        <div class='pull-left' id='new-datatable-filter'>
                            <span class='add-on' style='font-size:25px;'><i class='icon-magic'></i></span>
                        </div>
                    </div>
                    <div class='pull-left'>
                    <?php if($report_url == "pins/owned/trending"){ ?>
                        <div class='pull-left' id='new-column-filters'>
                        </div>
                    <?php } ?>
                    </div>

                    <div class='pull-right' id='new-datatable-length'><!-- 3 -->
                    </div>

                    <div class='pull-right'> <!-- 4 -->
                        <div id='export'></div>
                    </div>

                    <div class='pull-right'> <!-- 2 -->
    <!--	            <div id='new-datatable-length'>-->
    <!--	      		</div> -->
                        <div id='new-datatable-info'>
                        </div>
                    </div>
                </div>
                <div class='row no-margin'>

                <?php if($report_url == "pins/owned"){ ?>
                    <div class='pull-left' id='new-column-filters'>
                    </div>
                <?php } ?>

                <?php if($report_url == "pins/owned"){ ?>
<!--                    <div class='pull-left'>-->
<!--                        <div class='more-filters'>-->
<!--                            <a class='more-filters-btn'>-->
<!--                                <i class='icon-filter' style='font-size:23px; position: absolute; margin-left: -25px;'></i>	 More Filters...-->
<!--                            </a>-->
<!--                        </div>-->
<!--                    </div>-->
                <?php } ?>

                    <?= $date_filters; ?>


                </div>
                <div class='row no-margin'>
                    <div class='column-filter-tags'>
                    </div>
                </div>

            </div>
            <div class='row no-margin pull-right all-range-sliders hidden'>
                <div class='range-slider-wrap repin-slider-wrap pull-left'>
                    <div class='slider-label'>Repins</div>
                    <div id='repin-slider-range'></div>
                    <div id='amount-repin' class='slider-label' style='text-align:center; border: 0; color: #555; font-weight: bold;'></div>
                </div>
                <div class='range-slider-wrap like-slider-wrap pull-left'>
                    <div class='slider-label'>Likes</div>
                    <div id='like-slider-range'></div>
                    <div id='amount-like' class='slider-label' style='text-align:center; border: 0; color: #555; font-weight: bold;'></div>
                </div>
                <div class='range-slider-wrap comment-slider-wrap pull-left'>
                    <div class='slider-label'>Comments</div>
                    <div id='comment-slider-range'></div>
                    <div id='amount-comment' class='slider-label' style='text-align:center; border: 0; color: #555; font-weight: bold;'></div>
                </div>
            </div>
            <div class='clearfix'></div>
            <div id='collapseThree' class='accordion-body collapse in'>
                <div class='accordion-inner'>

                    <div class="row no-margin" style='margin-bottom:10px;'>

                        <div class="" style='text-align:center;padding-bottom:6px;padding-top:3px'>

                        <!--//----------------// MORE FILTER OPTIONS //---------------//-->

                        <div class='clearfix'></div>

                        <div class='clearfix'></div>
                            <span class='pull-right'>
                                <input class='input-small' type='text' id='repin_min' name='min' style='height:1px; width: 1px; position:absolute; margin-left:-9999px'>
                                <input class='input-small' type='text' id='repin_max' name='max' style='height:1px; width: 1px; position:absolute; margin-left:-9999px'>
                                <input class='input-small' type='text' id='like_min' name='min' style='height:1px; width: 1px; position:absolute; margin-left:-9999px'>
                                <input class='input-small' type='text' id='like_max' name='max' style='height:1px; width: 1px; position:absolute; margin-left:-9999px'>
                                <input class='input-small' type='text' id='comment_min' name='min' style='height:1px; width: 1px; position:absolute; margin-left:-9999px'>
                                <input class='input-small' type='text' id='comment_max' name='max' style='height:1px; width: 1px; position:absolute; margin-left:-9999px'>
                            </span>
                        <div class='clearfix'></div>


                        <div id='dynamic' style='float:left; margin-bottom:35px; width: 100%;'></div>





                        </div>
                    </div>


                </div>
            </div>
        </div>
    </div>

    <div id="pinHistoryModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h3 id="myModalLabel" style="margin-bottom:5px;">Pin Engagement History</h3>
            <span style="color:#8DC58D;margin-left:5px;">&#9679;</span> Repins
            <span style="color:#D77E81;margin-left:5px;">&#9679;</span> Likes
            <span style="color:#FFC267;margin-left:5px;">&#9679;</span> Comments
        </div>
        <div class="modal-body">
            <p>&nbsp;</p>
        </div>
        <div class="modal-footer">
            <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
        </div>
    </div>

<?php


?>