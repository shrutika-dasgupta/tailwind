<div class="calendar-timeslots">

    <?php foreach ($posts as $post): ?>
        <div class="calendar-post calendar-<?= $post->time_slot_type ?>-post">
            <div class="post-image-preview-wrapper pull-left">
                <img src="<?= $post->image_url ?>" alt="<?= $post->description ?>">
            </div>
            <div class="post-details pull-left">
                <span class="post-time">
                    <?= \Carbon\Carbon::createFromTimestamp($post->time_slot_timestamp, $user_timezone)->format('g:i A') ?>
                </span>

                <span class="post-board-name"><?= $post->getBoardName() ?></span>
            </div>
            <div class="clearfix"></div>
        </div>
    <?php endforeach ?>

</div>