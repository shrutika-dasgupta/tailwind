<div class='accordion-group' style='margin-bottom:0px; border-bottom:none'>
    <div class='section-header'>
        <div class='accordion-toggle section-header' style='cursor:default'>
        <?php if (in_array($type, array('latest'))): ?>
            <div class='help-icon-form pull-left' style='margin:8px 10px 0 5px;'>
                <a class='' data-toggle='popover' data-container='body'
                   data-original-title="What's in this report?"
                   data-content="Here, you will find the community's <?= $pretty_report_name ?> pins
                                        coming from <u><?=$query_string;?></u> as of a date you
                                        choose."
                   data-trigger='hover'
                   data-placement='right'>
                    <i id='header-icon' class='icon-help'></i>
                </a>
            </div>
        <?php endif; ?>
            <h2 style='float:left; font-weight:normal;font-size:22px'>
                <?=$pretty_report_name;?> from <?=$query_string;?>:
            </h2>

        <?php if (in_array($type, array('most-repinned','most-liked'))): ?>
            <div class='help-icon-form pull-right' style='margin:8px 5px 0 15px;'>
                <a class='' data-toggle='popover' data-container='body'
                   data-original-title="What's in this report?"
                            data-content="Here, you will find the community's <?= $pretty_report_name ?> pins
                                        from <u><?=$query_string;?></u> over a time period you
                                        choose."
                            data-trigger='hover'
                            data-placement='bottom'>
                    <i id='header-icon' class='icon-help'></i>
                </a>
            </div>
        <?php elseif (in_array($type, array('most-valuable'))): ?>

        <?php elseif (in_array($type, array('most-commented'))): ?>
            <div class='help-icon-form pull-right' style='margin:8px 5px 0 15px;'>
                <a class='' data-toggle='popover' data-container='body'
                   data-original-title="What's in this report?"
                   data-content="Here, you will find the community's <?= $pretty_report_name ?> pins
                                        from <u><?=$query_string;?></u> over a time period you
                                        choose.  See what users are saying about your brand and
                                        join the conversation!"
                   data-trigger='hover'
                   data-placement='bottom'>
                    <i id='header-icon' class='icon-help'></i>
                </a>
            </div>


        <?php endif; ?>
            <span class="btn-group pull-right" style="margin:5px 5px 0 0;">
            <?php if (in_array($type, array('most-repinned', 'most-liked'))): ?>


                <a href="<?= URL::route('domain-feed', array('most-repinned', $query_string, "date=$range")) ?>" type="button" class="btn <?= ($type == 'most-repinned') ? 'active' : '' ?>">
                    Most Repinned
                </a>
                <a href="<?= URL::route('domain-feed', array('most-liked', $query_string, "date=$range")) ?>" type="button" class="btn <?= ($type == 'most-liked') ? 'active' : '' ?>">
                    Most Liked
                </a>

            <?php elseif (in_array($type, array('latest'))): ?>
                <a <?= ($trending_images_enabled ? 'href="' . URL::route('domain-trending-images-default', array($query_string)) . '"' : '') ?>" type="button" class="btn <?= ($trending_images_enabled ? '' : 'disabled') ?>">
                    <i class="icon-pictures"></i> &nbsp;Group Pins by Image →
                </a>

            <?php elseif (in_array($type, array('most-visits','most-clicked','most-pageviews','most-transactions','most-revenue'))): ?>


                <a href="<?= URL::route('domain-feed', array('most-visits', $query_string, "date=$range")) ?>"
                   type="button"
                   id="most-clicked-pins-sort"
                   class="btn <?= (in_array($type,array('most-visits','most-clicked')) ? 'active' : '') ?>">
                    Visits
                </a>
<!--                <a href="--><?//= URL::route('domain-feed', array('most-pageviews', $query_string, "date=$range")) ?><!--" -->
<!--                   type="button"-->
<!--                   id="most-pageviews-pins-sort"-->
<!--                   class="btn --><?//= ($type == 'most-pageviews') ? 'active' : '' ?><!--">-->
<!--                    Pageviews-->
<!--                </a>-->
                <a <?= ($ecommerce_tracking ? 'href="' . (URL::route('domain-feed', array("most-transactions", $query_string, "date=$range"))) . '"' : '') ?>
                   type="button"
                   id="most-transactions-pins-sort"
                   class="btn <?= ($type == 'most-transactions') ? ' active' : '' ?>
                    <?php if ($ecommerce_tracking): ?>
                        "
                    <?php else: ?>
                       disabled"
                       data-toggle="popover-click"
                       data-placement="bottom"
                       data-title=""
                       data-content="You must enable eCommerce Tracking in your Google Analytics account to enable this report!"
                       data-container="body"
                    <?php endif ?>>
                    Conversions
                </a>
                <a <?= ($ecommerce_tracking ? 'href="' . (URL::route('domain-feed', array("most-revenue", $query_string, "date=$range"))) . '"' : '') ?>

                   type="button"
                   id="most-revenue-pins-sort"
                   class="btn <?= ($type == 'most-revenue') ? ' active' : '' ?>
                    <?php if ($ecommerce_tracking): ?>
                        "
                    <?php else: ?>
                        disabled"
                        data-toggle="popover-click"
                        data-placement="bottom"
                        data-title=""
                        data-content="You must enable eCommerce Tracking in your Google Analytics account to enable this report!"
                        data-container="body"
                    <?php endif ?>>
                    Revenue
                </a>
            <?php endif; ?>
            </span>
            <?php if (!$ecommerce_tracking && in_array($type, array('most-visits','most-clicked','most-pageviews','most-transactions','most-revenue'))): ?>
                <script>
                    $('#most-revenue-pins-sort, #most-transactions-pins-sort').append(
                        '<i class=\"icon-warning\" style=\"color: #CC2127;\"></i>'
                    );
                </script>
            <?php endif ?>
            <?php if (!in_array($type, array('latest', 'most-commented'))): ?>
            <span class="pull-right" style="margin:11px 0px 0 0;">
                Sort By: &nbsp;
            </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class='clearfix section-header'></div>