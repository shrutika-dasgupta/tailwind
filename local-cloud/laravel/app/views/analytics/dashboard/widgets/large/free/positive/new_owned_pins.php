
<? /*
    <h3>
        +
    </h3>
    <?php foreach ($owned_pins as $pin) { ?>
        <div>
            <img src="<?= $pin->image_square_url; ?>"/>
            <?php // if(!$pin->is_repin) { ?>

            <p><?=$pin->repin_count;?> repins</p>
            <?php // } ?>
        </div>
    <?php } ?>
    <p>
        Top category pinned to: <?= $top_category; ?>
    </p>
 */ ?>

    <div class="accordion widget ">
        <div class="accordion-heading">
            <div class="title">
                New Owned Pins
            </div>
        </div>
        <div class="accordion-body collapse in">
            <div class="accordion-inner">
                <div class="row no-margin">
                    <div class="feature-stat">
                        <i class="icon-arrow-up"></i> <?= $pins_this_week; ?> owned pins
                    </div>

                    <div class="clearfix"></div>
                    <div class="pins">

                        <?php foreach ($owned_pins as $pin) { ?>
                            <img src="<?= $pin->image_square_url; ?>"/>
                        <?php } ?>

                    </div>
                </div>
                <a href="/influencers/followers" class="btn btn-info pull-right">See trending pins</a>
            </div>
        </div>
    </div>

    <div class="clearfix"></div>
