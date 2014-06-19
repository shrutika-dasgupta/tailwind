<?= $topic_bar ?>

<div class="row-fluid">
    <div class="listening-dashboard span12">
        <div class="accordion">
            <div class="accordion-body collapse in">
                <div class="accordion-inner">
                    <div class="row no-margin">
                        <div class="alert alert-error">
                            You're not currently following "<?= $topic ?>".
                            <br /><br />
                            <a href="<?= URL::route('discover-add-topic', array('topic=' . urlencode($topic))) ?>" class="btn btn-primary">
                                Start Following "<?= $topic ?>"
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
