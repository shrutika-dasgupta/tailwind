<?php $id = empty($id) ? 1 : $id ?>

<?php
    $image_url   = object_get($post, 'image_url');
    $link        = object_get($post, 'link');
    $description = object_get($post, 'description');
    $domain      = str_replace('www.', '', parse_url($link, PHP_URL_HOST));
    $carbon_date = \Carbon\Carbon::createFromFormat('Y-m-d', $date, $user_timezone);
    $parent_pin  = object_get($post, 'parent_pin');
?>

<div class="post-image-wrapper"
     id="image-<?= $id ?>"
     data-post-id="<?= $id ?>"
>

    <div class="post-actions">
        <a href="<?= URL::route('publisher-delete-draft') ?>"
            class="btn btn-small delete-post-btn"
            id="delete-post-btn-<?= $id ?>"
            title="Delete Post"
            data-post-id="<?= $id ?>"
            data-toggle="tooltip"
            data-placement="left"
        >
            <i class="icon-trash"></i>
        </a>
    </div>

    <img class="post-image" id="post-image-<?= $id ?>" src="<?= $image_url ?>"
         title="<?= $description ?>"
         alt="<?= $description ?>"
         data-source-url="<?= $link ?>"
         nopin="nopin"
    >

    <input type="hidden"
           class="image-input"
           id="image-url-<?= $id ?>"
           name="image_url[<?= $id ?>]"
           value="<?= $image_url ?>"
    />

    <input type="hidden"
           class="parent-pin-input"
           id="parent-pin-<?= $id ?>"
           name="parent_pin[<?= $id ?>]"
           value="<?= $parent_pin ?>"
    />

    <div class="post-meta post-image-meta" id="post-image-meta-<?= $id ?>"
         data-toggle="tooltip"
         data-title="Change Description"
    >
        <span class="description-placeholder <?= empty($description) ? 'hidden' : '' ?>"
              id="description-placeholder-<?= $id ?>"
        >
            <?= $description ?>
        </span>
        <textarea class="description-input <?= !empty($description) ? 'hidden' : '' ?>"
                  id="description-<?= $id ?>"
                  type="text"
                  rows="2"
                  name="description[<?= $id ?>]"
                  placeholder="Enter a description"
                  required
        ><?= $description ?></textarea>
    </div>

    <div class="post-meta post-domain-meta <?= !empty($parent_pin) ? 'post-repin-meta' : '' ?>"
         <?php if (empty($parent_pin)): ?>
             data-toggle="tooltip"
             data-title="Change Source URL"
         <?php endif ?>
    >
        <?php if (!empty($parent_pin)): ?>
            <i class="icon-repin"></i>
            Repin from
        <?php endif ?>

        <span class="domain-placeholder">
            <img class="domain-icon" src="http://www.google.com/s2/favicons?domain=<?= $domain ?>">
            <span class="domain-text"><?= $domain ?></span>
        </span>

        <div class="input-prepend hidden">
            <span class="add-on">
                <i class="icon-globe"></i>
            </span>
            <input type="url"
                   class="input-medium link-input"
                   id="link-<?= $id ?>"
                   name="link[<?= $id ?>]"
                   value="<?= $link ?>"
                   placeholder="Enter a source url"
                   required
            />
        </div>
    </div>

    <div class="post-meta post-board-meta" id="post-board-meta-<?= $id ?>">
        <select multiple
               class="input-medium board-input"
               id="board-<?= $id ?>"
               name="board[<?= $id ?>][]"
               placeholder="Type a board name"
        ></select>
    </div>

    <div class="post-meta post-date-time-meta"
         id="post-date-time-meta-<?= $id ?>"
         data-toggle="tooltip"
         data-title="Change Post Time"
    >
        <i class="icon-clock"></i>
        <div class="post-auto-meta clearfix">
            <span class="label-auto-queue pull-left">
                Next available scheduled time
            </span>
            <a class="btn btn-mini btn-success btn-queue-post pull-right"
               href="<?= URL::route('publisher-create-post') ?>"
               data-toggle="tooltip"
               data-placement="bottom"
               title="<?= $admin_user ? 'Add to Queue' : 'Submit for Approval' ?>"
            >
                <i class="icon-checkmark"></i>
            </a>
        </div>

        <div class="post-manual-meta clearfix hidden">
            <span class="label-schedule-time pull-left">
                <span class="date-placeholder"><?= $carbon_date->format('F j, Y') ?></span> @
                <span class="hour-placeholder"><?= $hour ?></span>:<span class="minute-placeholder"><?= $minute ?></span>
                <span class="am-pm-placeholder"><?= $am_pm ?></span>
                <span class="timezone-placeholder">(<?= $carbon_date->format('T') ?>)</span>
            </span>
            <a class="btn btn-mini btn-success btn-schedule-post pull-right"
               href="<?= URL::route('publisher-create-post') ?>"
               data-toggle="tooltip"
               data-placement="bottom"
               title="<?= $admin_user ? 'Schedule' : 'Submit for Approval' ?>"
            >
                <i class="icon-checkmark"></i>
            </a>
        </div>

        <input type="hidden"
               class="schedule-type-input"
               id="schedule-type-<?= $id ?>"
               name="schedule_type[<?= $id ?>]"
               value="auto"
        />
        <input type="hidden"
               class="date-input"
               id="date-<?= $id ?>"
               name="date[<?= $id ?>]"
               value="<?= $date ?>"
        />
        <input type="hidden"
               class="hour-input"
               id="hour-<?= $id ?>"
               name="hour[<?= $id ?>]"
               value="<?= $hour ?>"
        />
        <input type="hidden"
               class="minute-input"
               id="minute-<?= $id ?>"
               name="minute[<?= $id ?>]"
               value="<?= $minute ?>"
        />
        <input type="hidden"
               class="am-pm-input"
               id="am-pm-<?= $id ?>"
               name="am_pm[<?= $id ?>]"
               value="<?= $am_pm ?>"
        />
    </div>

</div>