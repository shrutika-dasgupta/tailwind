<div class="accordion widget divided-large">
    <div class="accordion-body collapse in">
        <div class="accordion-inner">
            <div class="row-fluid">
                <div class="span7">

                    <a class="feature-stat" href="<?= URL::route('profile',array('ref'=>'dashboard')); ?>">
                        <div class="row-fluid">
                            <?= arrow($new_repins_count); ?> <?= $new_repins_count; ?> repins
                        <span class="action pull-right">
                            <i class="icon-graph"></i>
                        </span>
                        </div>
                        <div class="metric-change">
                            <div class="span4">
                                <?php if ($change_in_growth ==0) { ?> No Change <?php } else {  ?>
                                <?= arrow($change_in_growth); ?> <?= abs($change_in_growth);?> vs Avg.
                                <?php } ?>
                            </div>
                            <div class="span4">
                                <?= $average_growth;?> last week
                            </div>
                            <div class="span4">
                                <?= $total;?> total
                            </div>
                        </div>
                    </a>
                    <hr />

                    <div class="clearfix"></div>
                    <p class="blurb" ><?= $repins_blurb; ?></p>

                    <a href="<?= URL::route('owned-pins',array('ref'=>'dashboard')); ?>" class="btn btn-info btn-small">See Most Repinned Pins</a>

                </div>
                <div class="span5 ">
                    <div class="top-people-wrap">

                        <div class="top-people">
                            <h4>Top Repinners This Week</h4>
                            <?php if ($repinners->count()==0) { ?>
                            <div class="row-fluid ">
                                <p class="sad-pandas muted text-center">You had no top repinners this week.</p>
                                </div>
                                <div class="row-fluid">
                                    <a class="small-text pull-right text-right" href="<?= URL::route('top-repinners',array('ref'=>'dashboard')); ?>">View All-time Repinners&#8594;</a>
                                </div>

                            <?php } else {  ?>
                            <?php foreach ($repinners as $repinner) { ?>
                                <div class="row-fluid ">


                                    <h5>
                                        <a href="<?= URL::route('top-repinners',array('ref'=>'dashboard')); ?>">
                                            <img src="<?= $repinner->image; ?>"/>
                                        </a>
                                        <?= $repinner->username; ?>  <span
                                            class="engagement"><?= $repinner->overall_engagement; ?>
                                            repins </span>
                                    </h5>

                                </div>
                            <?php } ?>
                            <div class="row-fluid">
                                <a class="small-text pull-right text-right" href="<?= URL::route('top-repinners',array('ref'=>'dashboard')); ?>">See More Repinners&#8594;</a>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="clearfix"></div>
</div>
