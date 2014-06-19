<?= $topic_bar ?>

<div class="row-fluid">
    <div class="listening-dashboard span12">
        <div class="accordion">
            <div class="accordion-body collapse in">
                <div class="accordion-inner">
                    <div class="row no-margin">


                        <?php if ($has_domain): ?>
                            <div class="alert alert-error">
                                You're not currently tracking "<?= $topic ?>".
                                <br /><br />
<!--                                <a href="--><?//= URL::route('domain-add-topic', array('topic=' . urlencode($topic))) ?><!--" class="btn btn-primary">-->
<!--                                    Start Following "--><?//= $topic ?><!--"-->
<!--                                </a>-->
                                <a href="<?= $link ?>" class="btn btn-primary">
                                    <?= $page_name ?> for <?= $cust_domain ?> →
                                </a>
                            </div>
                        <?php endif ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
