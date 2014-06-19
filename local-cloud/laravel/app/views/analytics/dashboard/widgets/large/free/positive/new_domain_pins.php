<div class="accordion widget divided-large">
    <div class="accordion-body collapse in">
        <div class="accordion-inner">
            <div class="row-fluid">
                <div class="span7">

                    <a class="feature-stat" href="<?= URL::route('domain-insights-default', array($domains->first()->domain)); ?>">
                        <div class="row-fluid">
                            <?= arrow($new_organic_pins); ?> <?= $new_organic_pins; ?> domain pins
                        <span class="action pull-right">
                            <i class="icon-resistor"></i>
                        </span>
                        </div>
                        <div class="metric-change">
                            <div class="span4">
                                <?php if ($change_in_growth ==0) { ?> No Change <? } else {  ?>
                                    <?= arrow($change_in_growth); ?> <?= abs($change_in_growth);?> vs Avg.
                                <? } ?>
                            </div>
                            <div class="span8">
                                <?= number_format($last_week);?> pins last week
                            </div>
                        </div>
                    </a>
                    <div class="row-fluid"><hr></div>
                    <div class="clearfix"></div>
                    <p class="blurb">from <?= $domains; ?></p>
                    <p><?= $organic_blurb;?></p>

                    <div class="pins" style="padding-bottom: 5px;">

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
                            <div class="row-fluid ">
                                <p class="sad-pandas muted text-center">You need to upgrade to see this report.
                            </div>
                            <div class="row-fluid">
                                <a class="small-text pull-right text-right" data-target="#modal" data-toggle="modal"  href="/upgrade/modal/brand_advocates"><i class="icon-lock"></i> Upgrade to Unlock &#8594;</a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="clearfix"></div>
</div>

