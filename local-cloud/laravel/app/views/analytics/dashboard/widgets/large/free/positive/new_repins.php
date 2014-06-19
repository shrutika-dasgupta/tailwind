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
                                <?php if ($change_in_growth ==0) { ?> No Change <? } else {  ?>
                                    <?= arrow($change_in_growth); ?> <?= abs($change_in_growth);?> vs Avg.
                                <? } ?>
                            </div>
                            <div class="span4">
                                <?= $average_growth;?> last week
                            </div>
                            <div class="span4">
                                <?= $total;?> total
                            </div>
                        </div>
                    </a>
                    <div class="row-fluid"><hr></div>
                    <div class="clearfix"></div>
                    <p class="blurb" ><?= $repins_blurb; ?></p>

                    <a href="<?= URL::route('owned-pins',array('ref'=>'dashboard')); ?>" class="btn btn-info btn-small">See Most Repinned Pins</a>

                </div>
                <div class="span5 ">
                    <div class="top-people-wrap">

                        <div class="top-people">
                            <h4>Top Repinners This Week</h4>
                            <div class="row-fluid ">
                                <p class="sad-pandas muted text-center">You need to upgrade to see this report.
                            </div>
                            <div class="row-fluid">
                                <a class="small-text pull-right text-right" data-target="#modal" data-toggle="modal"  href="/upgrade/modal/repinners"><i class="icon-lock"></i> Upgrade to Unlock &#8594;</a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="clearfix"></div>
</div>
