
<div class="accordion widget divided-large">
    <div class="accordion-body collapse in">
        <div class="accordion-inner">
            <div class="row-fluid">
                <div class="span7">

                    <a class="feature-stat" href="<?= URL::route('domain-insights-default', array($domains->first()->domain)); ?>">
                        <div class="row-fluid">
                            <span class="action pull-right">
                            <i class="icon-resistor"></i>
                        </span>
                            <?= arrow($new_organic_pins); ?> <?= $new_organic_pins; ?> domain pins

                        </div>
                        <div class="metric-change">
                            <div class="span6">
                                <?php if ($change_in_growth ==0) { ?> No Change <? } else {  ?>
                                    <?= arrow($change_in_growth); ?> <?= number_format(abs($change_in_growth));?> vs Avg.
                                <?php } ?>
                            </div>
                            <div class="span6">
                                <?= number_format($last_week);?> last week
                            </div>
                        </div>
                    </a>
                    <div class="row-fluid"><hr></div>
                    <div class="clearfix"></div>
                    <p class="blurb">from <?= $domains; ?></p>
                    <p><?= $organic_blurb;?></p>

                    <div class="pins" style="padding-bottom:5px;">

                        <?php foreach ($domain_pins as $pin) { ?>
                            <div class="domain-pin-wrapper">
                                <a class="domain-pin-link" href="<?= URL::route('domain-feed', array('latest', $domains->first()->domain)); ?>">
                                    <img class="domain-pin-preview" src="<?= $pin->image_url; ?>"/>

                                </a>
                            </div>
                        <?php } ?>

                    </div>

                    <a href="<?= URL::route('domain-feed', array('latest', $domains->first()->domain)); ?>" class="btn btn-info btn-small">See Latest Pins</a>

                </div>
                <div class="span5 ">
                    <div class="top-people-wrap">

                        <div class="top-people">
                            <h4>Top Brand Pinners This Week</h4>
                            <?php if ($domain_pinners->count()==0) { ?>
                                <div class="row-fluid ">
                                    <p class="sad-pandas muted text-center">You had no top brand pinners this week.</p>
                                </div>
                                <div class="row-fluid">
                                    <a class="small-text pull-right text-right" href="<?= URL::route('domain-pinners',array('ref'=>'dashboard')); ?>">View All-time Brand Pinners&#8594;</a>
                                </div>

                            <?php } else {  ?>
                                <?php foreach ($domain_pinners as $pinner) { ?>
                                    <div class="row-fluid ">


                                        <h5>
                                            <a href="<?= URL::route('domain-pinners',array('ref'=>'dashboard')); ?>">
                                                <img src="<?= $pinner->image; ?>"/>
                                            </a>
                                            <?= $pinner->username; ?>  <span
                                                class="engagement"><?= $pinner->domain_mentions; ?>

                                                mentions </span>
                                        </h5>

                                    </div>
                                <?php } ?>
                                <div class="row-fluid">
                                    <a class="small-text pull-right text-right" href="<?= URL::route('domain-pinners',array('ref'=>'dashboard')); ?>">See More Brand Pinners&#8594;</a>
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

