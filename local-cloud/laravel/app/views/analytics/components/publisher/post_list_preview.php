<?php
if ($post->sent_at) {
    $date = \Carbon\Carbon::createFromTimestamp($post->sent_at, $user_timezone);
} else {
    $date = \Carbon\Carbon::createFromTimestamp($post->time_slot_timestamp, $user_timezone);
}
?>

<div class="post-wrapper  <?php if (!empty($post->pin_id)): ?>pinned-post<?php endif ?>"
    <?php if (!empty($post->pin_id)): ?>
        data-pin-url="<?= URL::route('pinterest-pin', array($post->pin_id)) ?>"
    <?php endif ?>
>

    <div class="post-image-preview-wrapper pull-left">
        <img class="post-image-preview"
             src="<?= object_get($post, 'image_url'); ?>"
             title="<?= object_get($post, 'description'); ?>"
             alt="<?= object_get($post, 'description'); ?>"
        >
    </div>

    <div class="post-board">
        <strong><?= $post->getBoardName() ?></strong>
    </div>

    <div class="post-description"><?= $post->description ?></div>

    <div class="post-domain <?= !empty($post->parent_pin) ? 'post-repin-meta' : '' ?>">
        <small>
            <?php if (!empty($post->parent_pin)): ?>
                <i class="icon-repin"></i>
                Repinned from
            <?php endif ?>

            <a href="<?= $post->link ?>" target="_blank" title="Source">
                <img class="domain-icon" src="http://www.google.com/s2/favicons?domain=<?= $post->domain ?>">
                <span class="domain-text"><?= $post->domain ?></span>
            </a>
        </small>
    </div>

    <div class="post-date-time">
        <small class="post-day <?= ($post->sent_at) ? 'post-sent-at-time' : 'post-send-time' ?>">
            <?= $date->format('l') ?>
        </small>

        <small class="post-date <?= ($post->sent_at) ? 'post-sent-at-time' : 'post-send-time' ?>">
            <?= $date->format('F j, Y') ?>
        </small>

        <small class="post-time <?= ($post->sent_at) ? 'post-sent-at-time' : 'post-send-time' ?>">
            <?= $date->format('g:i A (T)') ?>
        </small>
    </div>

    <div class="clearfix"></div>

</div>