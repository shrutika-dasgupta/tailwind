<div class="module widget">

    <a href="/domain/most-commented/" class="row-fluid out-of-face">

        <span class="pull-right"><i class="icon-users"></i></span>
        <h4 class="title">
           Recent Comments
        </h4>
    </a>

    <?php foreach($comments as $comment) { ?>
    <div class="row-fluid">

        <div class="span4">
            <img src="<?= $comment->pin_image;?>" />
        </div>

        <div class="span7 offset1">
            <div class="row-fluid">
                <div class="span6">
                    <b> <?= $comment->username;?> </b>
                </div>

                <div class="span6">
                    <?= $comment->time; ?>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span12">
                    <p><?= $comment->comment_text; ?></p>
                    <a class="btn btn-mini pull-right" target="_blank" href="http://www.pinterest.com/pin/<?= $comment->pin_id;?>">Respond</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row-fluid">
        <hr style="margin:10px 0 10px">
    </div>

    <div class="clearfix"></div>

    <?php } ?>

    <?php if ($comment_count == 0) { ?>
        <p class="blurb"> You have had no new comments in the last 7 days.
            <br><br><a target="_blank" href="http://blog.tailwindapp.com/get-comments-on-pinterest/"><strong>Check out these great tips from our blog</strong></a>
            for ideas on how to get more comments and increase engagement on your pins!
        </p>
<!--        <a href="/domain/most-commented/" class="btn btn-small btn-info">Monitor Comments from your Domain</a>-->
    <?php } ?>

</div>
