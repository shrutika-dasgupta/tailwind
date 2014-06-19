<?php
    $carbon_date = \Carbon\Carbon::createFromTimestamp($post->time_slot_timestamp, $user_timezone);
    $date        = $carbon_date->format('Y-m-d');
    $hour        = $carbon_date->format('g');
    $minute      = $carbon_date->format('i');
    $am_pm       = $carbon_date->format('A');
?>

<div class="post-wrapper"
     id="image-<?= $post->id ?>"
     data-post-id="<?= $post->id ?>"
>

    <div class="post-image-preview-wrapper pull-left">
        <img class="post-image-preview"
             src="<?= object_get($post, 'image_url'); ?>"
             title="<?= object_get($post, 'description'); ?>"
             alt="<?= object_get($post, 'description'); ?>"
             data-source-url="<?= $post->link ?>"
             nopin="nopin"
        >
    </div>

    <div class="post-board post-meta post-board-meta">
        <select class="input-medium board-input"
                id="board-<?= $post->id ?>"
                placeholder="Type a board name"
        >
            <option value="<?= $post->board_name ?>" selected><?= $post->getBoardName() ?></option>
        </select>
    </div>

    <div class="post-description post-meta post-image-meta" data-toggle="tooltip" data-title="Change Description">
        <span class="description-placeholder" id="description-placeholder-<?= $post->id ?>">
            <?= $post->description ?>
        </span>
        <textarea class="description-input input-xxlarge hidden"
                  id="description-<?= $post->id ?>"
                  type="text"
                  rows="4"
                  placeholder="Enter a description"
                  required
        ><?= $post->description ?></textarea>
    </div>

    <div class="post-domain post-meta post-domain-meta <?= !empty($post->parent_pin) ? 'post-repin-meta' : '' ?>"
         <?php if (empty($post->parent_pin)): ?>
             data-toggle="tooltip"
             data-title="Change Source URL"
         <?php endif ?>
    >
        <?php if (!empty($post->parent_pin)): ?>
            <i class="icon-repin"></i>
            Repin from
        <?php endif ?>

        <span class="domain-placeholder">
            <img class="domain-icon" src="http://www.google.com/s2/favicons?domain=<?= $post->domain ?>">
            <span class="domain-text"><?= $post->domain ?></span>
        </span>

        <div class="input-prepend hidden">
            <span class="add-on">
                <i class="icon-globe"></i>
            </span>
            <input type="url"
                   class="input-xxlarge link-input"
                   id="link-<?= $post->id ?>"
                   value="<?= $post->link ?>"
                   placeholder="Enter a source url"
                   required
            />
        </div>
    </div>

    <div class="post-date-time post-meta post-date-time-meta" data-toggle="tooltip" data-title="Change Post Time">
        <i class="icon-clock"></i>
        <span class="label-auto-queue post-auto-meta <?= $post->time_slot_type == 'manual' ? 'hidden' : '' ?>">
            <span><?= $carbon_date->format('F j, Y @ g:i A (T)') ?></span>
        </span>

        <span class="label-schedule-time post-manual-meta <?= $post->time_slot_type == 'auto' ? 'hidden' : '' ?>">
            <span class="date-placeholder"><?= $carbon_date->format('F j, Y') ?></span> @
            <span class="hour-placeholder"><?= $hour ?></span>:<span class="minute-placeholder"><?= $minute ?></span>
            <span class="am-pm-placeholder"><?= $am_pm ?></span>
            <span class="timezone-placeholder">(<?= $carbon_date->format('T') ?>)</span>
        </span>

        <input type="hidden"
               class="schedule-type-input"
               id="schedule-type-<?= $post->id ?>"
               value="<?= $post->time_slot_type ?>"
        />
        <input type="hidden"
               class="date-input"
               id="date-<?= $post->id ?>"
               value="<?= $date ?>"
        />
        <input type="hidden"
               class="hour-input"
               id="hour-<?= $post->id ?>"
               value="<?= $hour ?>"
        />
        <input type="hidden"
               class="minute-input"
               id="minute-<?= $post->id ?>"
               value="<?= $minute ?>"
        />
        <input type="hidden"
               class="am-pm-input"
               id="am-pm-<?= $post->id ?>"
               value="<?= $am_pm ?>"
        />
    </div>

    <div class="post-actions">
        <a href="<?= URL::route('api-publisher-delete-post', array($post->id)) ?>"
           title="Delete Post"
           class="post-action-link delete-post-btn"
        >
            <i class="icon-trash"></i>
            Delete
        </a>

        <?php if ($post->status == Publisher\Post::STATUS_AWAITING_APPROVAL): ?>
            <a class="btn btn-medium btn-success btn-approve-post"
               href="<?= URL::route('publisher-approve-post') ?>"
               title="Approve and Schedule"
            >
                <i class="icon-checkmark"></i>
                Approve
            </a>
        <?php else: ?>
            <?php if ($post->time_slot_type == 'auto'): ?>
                <a class="post-action-link btn-move-top"
                   href="javascript:void(0);"
                   title="Move to Top"
                >
                    <i class="icon-arrow-up"></i>
                    Move to Top
                </a>
                <a class="post-action-link btn-move-bottom hidden"
                   href="javascript:void(0);"
                   title="Move to Bottom"
                >
                    <i class="icon-arrow-down"></i>
                    Move to Bottom
                </a>
            <?php endif ?>

            <a class="post-action-link btn-publish-now"
               href="<?= URL::route('publisher-publish-post') ?>"
               title="Post Now"
            >
                <i class="icon-paperplane"></i>
                Post Now
            </a>

            <a class="btn btn-medium btn-success btn-update-post hidden"
               href="<?= URL::route('publisher-update-post') ?>"
               title="Save Changes"
            >
                <i class="icon-checkmark"></i>
                Save
            </a>
        <?php endif ?>

    </div>

    <div class="clearfix"></div>

</div>