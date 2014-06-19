<div class="accordion widget double-with-faces">
    <div class="accordion-body collapse in">
        <div class="accordion-inner">
            <div class="row-fluid" >
                <div class="span7">

                    <div class="feature-stat">
                        <i class="icon-arrow-up"></i> <?= $new_likes_count; ?> repins
                    </div>

                    <div class="clearfix"></div>
                    <p><?= $repins_blurb;?></p>

                    <a href="/pins/owned?ref=dashboard" class="btn btn-info">See All Repins</a>
                    <a href="/influencers/top-repinners/?ref=dashboard" class="btn">Meet Repinners</a>

                </div>
                <div class="span4 pull-right">
                    <div class="repinners">

                        <?php foreach ($repinners as $repinner) { ?>
                        <div class="row ">
                            <div class="span3 ">
                                <a href="/influencers/top-repinners/?cf=dashboard-follower">
                                    <img src="<?= $repinner->image; ?>"/>
                                </a>
                            </div>

                            <div class="span8 pull-right">
                                <h5><?= $repinner->username;?> </h5>
                                <?= $repinner->overall_engagement; ?> repins
                            </div>

                        </div>
                        <?php } ?>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <div class="clearfix"></div>
</div>
