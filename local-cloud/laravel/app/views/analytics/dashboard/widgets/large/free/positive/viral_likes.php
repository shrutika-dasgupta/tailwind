<div class="accordion widget ">
    <div class="accordion-body collapse in">
        <div class="accordion-inner">
            <div class="row no-margin">
                <?php foreach ($pins as $key => $pin) { ?>

                    <div class="span2">
                        <h5><i class="icon-arrow-up"></i> <?php $hash = explode('@', $key, 2);
                            echo $hash[0]; ?> likes</h5>
                        <a href="/influencers/top-repinners/?cf=dashboard-follower">
                            <img src="<?= $pin->image_url; ?>"/>
                        </a>

                    </div>
                <?php } ?>


            </div>
        </div>
    </div>

    <div class="clearfix"></div>
</div>
