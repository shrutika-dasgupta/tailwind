<div class="post-image-wrapper <?php if (!empty($post->pin_id)): ?>pinned-post<?php endif ?>"
     id="image-<?= $post->id ?>"
     <?php if (!empty($post->pin_id)): ?>
         data-pin-url="<?= URL::route('pinterest-pin', array($post->pin_id)) ?>"
     <?php endif ?>
>

    <img class="post-image"
         src="<?= $post->image_url ?>"
         title="<?= $post->description ?>"
         alt="<?= $post->description ?>"
         nopin="nopin"
    >

    <div class="post-image-meta"><?= $post->description ?></div>

    <div class="post-domain-meta <?= !empty($post->parent_pin) ? 'post-repin-meta' : '' ?>">
        <?php if (!empty($post->parent_pin)): ?>
            <i class="icon-repin"></i>
            Repinned from
        <?php endif ?>

        <a href="<?= $post->link ?>" target="_blank" title="Source">
            <img class="domain-icon" src="http://www.google.com/s2/favicons?domain=<?= $post->domain ?>">
            <span class="domain-text"><?= $post->domain ?></span>
        </a>
    </div>

    <div class="post-board-meta">
        <?php if ($post->sent_at): ?>
            Added to <strong><?= $post->getBoardName() ?></strong>
            on <?= $post->getPrettyPublishedTime($user_timezone) ?>
        <?php else: ?>
            Add to <strong><?= $post->getBoardName() ?></strong>
            on <?= $post->getPrettyScheduledTime($user_timezone) ?>
        <?php endif ?>
    </div>

</div>