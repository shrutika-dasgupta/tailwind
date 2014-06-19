<?php if ($entry->curated < 0) return ?>

<div class="item" id="entry-<?= $entry->id ?>" data-popularity="<?= object_get($entry, 'social_score', 0) ?>" data-datetime="<?= $entry->published_at ?>">
    <?php $post_found = false; ?>
    <?php foreach ($posts as $post): ?>
        <?php if ($post->link == $entry->url || $post->image_url == $entry->image_url): ?>
            <?php if (!empty($post->sent_at)): ?>
                <div class="ribbon-wrapper">
                    <div class="ribbon ribbon-green">
                        <a href="<?= URL::route('pinterest-pin', array($post->pin_id)) ?>" target="_blank">
                            Published
                        </a>
                    </div>
                </div>
            <?php elseif ($post->status == Publisher\Post::STATUS_AWAITING_APPROVAL): ?>
                <div class="ribbon-wrapper">
                    <div class="ribbon ribbon-blue">
                        <a href="<?= URL::route('publisher-posts', array('pending')) ?>">Submitted</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="ribbon-wrapper">
                    <div class="ribbon ribbon-blue">
                        <a href="<?= URL::route('publisher-posts', array('scheduled')) ?>">Scheduled</a>
                    </div>
                </div>
            <?php endif ?>

            <?php $post_found = true; ?>
            <?php break ?>
        <?php endif ?>
    <?php endforeach ?>

    <div class="image" data-id="<?= $entry->id ?>">
        <img src="<?= $entry->image_url ?>" />
        
        <?php if (!empty($entry->social_score)): ?>
            <span class="meta popularity" data-entry-id="<?= $entry->id ?>">
                <i class="icon-fire"></i> <?= number_format($entry->social_score) ?>
            </span>

            <?= View::make('analytics.pages.content.score', array('entry' => $entry)) ?>
        <?php endif ?>

        <?php if (!$post_found && $can_schedule): ?>
            <?php
                $entry_post_data = array(
                    'link'        => $entry->url,
                    'image_url'   => $entry->image_url,
                    'description' => strip_tags($entry->title),
                    'options'     => array(
                        'source'          => 'content-feed',
                        'close_btn_text'  => 'Cancel',
                        'submit_btn_text' => 'Schedule',
                    ),
                );
            ?>
            <button class="btn btn-schedule js-track-click js-schedule-it" data-entry-id="<?= $entry->id ?>" data-entry-data='<?= json_encode($entry_post_data) ?>' data-component="Entry" data-element="Schedule It Button">
                <i class="icon-tailwind"></i> Schedule
            </button>
        <?php elseif (!$post_found): ?>
            <div class="btn-pin-it js-track-click" data-component="Entry" data-element="Pin It Button">
                <a href="<?= $entry->pinItUrl($entry->image_url) ?>" data-pin-do="buttonPin" data-pin-config="none" data-pin-height="28">
                    <img src="//assets.pinterest.com/images/pidgets/pinit_fg_en_rect_gray_28.png" />
                </a>
            </div>
        <?php endif ?>

        <?php if ($can_flag): ?>
            <button class="btn btn-warning btn-flag js-flag-it" data-toggle="tooltip" title="Flag this content as innappriate." data-url="<?= URL::route('content-flag-entry', array($entry->id)) ?>">
                <i class="icon-flag"></i>
            </button>
        <?php endif ?>
    </div>
    <div class="title"><?= $entry->title ?></div>
    <div class="domain">
        <img src="http://www.google.com/s2/favicons?domain=<?= $entry->domain ?>" />
        <a href="<?= $entry->url ?>" target="_blank">
            <?= $entry->domain ?>
        </a>
    </div>
    <div class="description hidden"><?= $entry->description ?></div>
    <div class="date-source hidden">
        <i class="icon-calendar"></i>
        Posted <?= \Carbon\Carbon::createFromTimeStamp($entry->published_at)->diffForHumans() ?> on
        <a href="<?= $entry->url ?>" target="_blank">
            <?= $entry->domain ?>
        </a>
    </div>
</div>