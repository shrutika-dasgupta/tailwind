<div class="accordion widget divided-large">
    <div class="accordion-body collapse in">
        <div class="accordion-inner">
            <div class="row-fluid">
                <div class="span12">

                    <a class="feature-stat <?= sentiment($new_follower_count); ?>" href="<?= URL::route('profile',array('ref'=>'dashboard')); ?>">
                        <div class="row-fluid">
                            <?= arrow($new_follower_count); ?> <?= $new_follower_count; ?>
                            followers
                        <span class="action pull-right">
                            <i class="icon-graph"></i>
                        </span>
                        </div>
                        <div class="metric-change">
                            <div class="span4">
                                <?php if ($change_in_growth == 0) { ?> No Change <?php } else { ?>
                                    <?= arrow($change_in_growth); ?> <?= number_format(abs($change_in_growth)); ?> vs Avg.
                                <?php } ?>
                            </div>
                            <div class="span4">
                                <?= $average_growth; ?> last week
                            </div>
                            <div class="span4">
                                <?= $total; ?> total
                            </div>
                        </div>
                    </a>
                    <div class="row-fluid"><hr></div>

                    <div class="clearfix"></div>
                    <p class="blurb"><?= $blurb; ?></p>

                    <div class="row-fluid">
                        <div class="span12 ">

                            <div class="followers">

                                <?php foreach ($followers as $follower) { ?>
                                    <a href="<?= URL::route('influential-followers',array('ref'=>'dashboard')); ?>">

                                        <img
                                            src="<?= $follower->getImageUrl(); ?>"/>
                                    </a>
                                <?php } ?>

                            </div>
                            <a href="<?= URL::route('newest-followers',array('ref'=>'dashboard')); ?>"
                               class="btn btn-info btn-small pull-right">
                                Engage Newest Followers
                                </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="clearfix"></div>
    </div>
</div>

