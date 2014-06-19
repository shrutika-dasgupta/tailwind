<div class="module widget">

    <a href="/pins/owned/trending" class="row-fluid out-of-face">

        <span class="pull-right"><i class="icon-fire"></i></span>
        <h4 class="title">
            Most Viral Pins This Week
        </h4>
    </a>

    <div class="row-fluid">

    <?php /** @var $pin Pin */ foreach($pins as $pin) { ?>
        <a class="span4 out-of-face board" href="/pins/owned/trending">
            <div>
            <span class="<?= sentiment($pin->repin_count_change);?>">
                <?= arrow($pin->repin_count_change);?> <?= number_format($pin->repin_count_change); ?> repins
                </span>
                <div class="metric-change"><?= number_format($pin->repin_count);?> repins (total)</div>
            </div>
            <div style="max-height: 150px; overflow: hidden">

                <img src="<?= $pin->image_url;?>"
                     title="<?= $pin->board()->name;?>" />
            </div>

        </a>
    <?php } ?>
    </div>


    <div class="row-fluid">
        <a href="/pins/owned/trending?ref=dashboard" class="btn btn-info btn-small">
            See More Viral Pins
        </a>
    </div>

</div>

