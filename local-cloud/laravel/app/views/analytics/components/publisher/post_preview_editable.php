<?php
    $carbon_date = \Carbon\Carbon::createFromTimestamp($post->time_slot_timestamp, $user_timezone);
    $date        = $carbon_date->format('Y-m-d');
    $hour        = $carbon_date->format('g');
    $minute      = $carbon_date->format('i');
    $am_pm       = $carbon_date->format('A');
?>

<div class="post-image-wrapper"
     id="image-<?= $post->id ?>"
     data-post-id="<?= $post->id ?>"
>

    <div class="post-actions">
        <a href="<?= URL::route('api-publisher-delete-post', array($post->id)) ?>"
           title="Delete Post"
           class="btn btn-small delete-post-btn"
           data-toggle="tooltip"
           data-placement="left"
        >
            <i class="icon-trash"></i>
        </a>
    </div>

    <img class="post-image"
         src="<?= $post->image_url ?>"
         title="<?= $post->description ?>"
         alt="<?= $post->description ?>"
         data-source-url="<?= $post->link ?>"
         nopin="nopin"
    >

    <div class="post-meta post-image-meta"
         id="post-image-meta-<?= $id ?>"
         data-toggle="tooltip"
         data-title="Change Description"
    >
        <span class="description-placeholder" id="description-placeholder-<?= $post->id ?>">
            <?= $post->description ?>
        </span>
        <textarea class="description-input hidden"
                  id="description-<?= $post->id ?>"
                  type="text"
                  rows="2"
                  placeholder="Enter a description"
                  required
        ><?= $post->description ?></textarea>
    </div>

    <div class="post-meta post-domain-meta <?= !empty($post->parent_pin) ? 'post-repin-meta' : '' ?>"
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
                   class="input-medium link-input"
                   id="link-<?= $post->id ?>"
                   value="<?= $post->link ?>"
                   placeholder="Enter a source url"
                   required
            />
        </div>
    </div>

    <div class="post-meta post-board-meta" id="post-board-meta-<?= $post->id ?>">
        <select class="input-medium board-input"
                id="board-<?= $post->id ?>"
                placeholder="Type a board name"
        >
            <option value="<?= $post->board_name ?>" selected><?= $post->getBoardName() ?></option>
        </select>
    </div>

    <div class="post-meta post-date-time-meta"
         id="post-date-time-meta-<?= $post->id ?>"
         data-toggle="tooltip"
         data-title="Change Post Time"
    >
        <i class="icon-clock"></i>
        <div class="post-auto-meta clearfix <?= $post->time_slot_type == 'manual' ? 'hidden' : '' ?>">
            <span class="label-auto-queue pull-left">
                <?= $carbon_date->format('F j, Y @ g:i A (T)') ?>
            </span>

            <?php if ($post->status == Publisher\Post::STATUS_AWAITING_APPROVAL): ?>
                <a class="btn btn-mini btn-success btn-approve-post pull-right"
                   href="<?= URL::route('publisher-approve-post') ?>"
                   data-toggle="tooltip"
                   data-placement="bottom"
                   title="Approve and Schedule"
                >
                    <i class="icon-checkmark"></i>
                </a>
            <?php else: ?>
                <a class="btn btn-mini btn-success btn-update-post pull-right hidden"
                   href="<?= URL::route('publisher-update-post') ?>"
                   data-toggle="tooltip"
                   data-placement="bottom"
                   title="Save Changes"
                >
                    <i class="icon-checkmark"></i>
                </a>
            <?php endif ?>
        </div>

        <div class="post-manual-meta clearfix <?= $post->time_slot_type == 'auto' ? 'hidden' : '' ?>">
            <span class="label-schedule-time pull-left">
                <span class="date-placeholder"><?= $carbon_date->format('F j, Y') ?></span> @
                <span class="hour-placeholder"><?= $hour ?></span>:<span class="minute-placeholder"><?= $minute ?></span>
                <span class="am-pm-placeholder"><?= $am_pm ?></span>
                <span class="timezone-placeholder">(<?= $carbon_date->format('T') ?>)</span>
            </span>

            <?php if ($post->status == Publisher\Post::STATUS_AWAITING_APPROVAL): ?>
                <a class="btn btn-mini btn-success btn-approve-post pull-right"
                   href="<?= URL::route('publisher-approve-post') ?>"
                   data-toggle="tooltip"
                   data-placement="bottom"
                   title="Approve and Schedule"
                >
                    <i class="icon-checkmark"></i>
                </a>
            <?php else: ?>
                <a class="btn btn-mini btn-success btn-update-post pull-right hidden"
                   href="<?= URL::route('publisher-update-post') ?>"
                   data-toggle="tooltip"
                   data-placement="bottom"
                   title="Save Changes"
                >
                    <i class="icon-checkmark"></i>
                </a>
            <?php endif ?>
        </div>

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

</div>