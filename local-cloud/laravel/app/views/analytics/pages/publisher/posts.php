<?php
    $editable = false;
    if (($view == 'scheduled' || $view == 'pending') && $admin_user) {
        $editable = true;
    }
?>

<?= $navigation; ?>

<h3 class="pull-left">
    <?php if ($view == 'pending'): ?>
        <?php if ($admin_user): ?>
            Posts Awaiting Approval
        <?php else: ?>
            Posts Ready for Approval
        <?php endif ?>
    <?php else: ?>
        <?= ucfirst($view) ?> Posts
    <?php endif ?>
</h3>

<div class="pull-right publisher-layout-options">
    <strong>Layout:</strong>
    <div class="btn-group" id="posts-layout-toggle" data-toggle="buttons-radio">
        <a class="btn <?= ($layout == 'feed') ? 'active' : '' ?>"
           id="posts-feed-btn"
           title="Feed"
           href="<?= URL::route('publisher-posts', array($view, 'feed')) ?>"
        >
            <i class="icon-pin-feed"></i>
        </a>
        <a class="btn <?= ($layout == 'list') ? 'active' : '' ?>"
           id="posts-list-btn"
           title="List"
           href="<?= URL::route('publisher-posts', array($view, 'list')) ?>"
        >
            <i class="icon-list"></i>
        </a>
    </div>
</div>

<div class="clearfix"></div>

<?php if ($posts->isEmpty()): ?>

    <?php if ($view == 'pending'): ?>
        <p>You don't have any posts waiting for approval!</p>
    <?php else: ?>
        <p>You haven't <?= $view ?> any posts!</p>
    <?php endif ?>

    <?= View::make('analytics.components.publisher.bookmarklet_cta'); ?>

<?php elseif ($layout == 'feed'): ?>

    <div class="row row-fluid posts-row <?= $editable ? 'editable-posts' : '' ?>"
         id="<?= $view ?>-posts"
    >

        <div class="posts-container span12">
            <div id="posts-body">
                <div id="posts-image-list">

                    <?php
                        $post_view = 'analytics.components.publisher.post_preview';
                        if ($editable) {
                            $post_view = 'analytics.components.publisher.post_preview_editable';
                        }
                    ?>

                    <?php foreach ($posts as $post): ?>
                        <?= View::make($post_view, array(
                            'post'          => $post,
                            'user_timezone' => $user_timezone,
                        ));
                        ?>
                    <?php endforeach ?>

                </div>
            </div>
        </div>
    </div>

<?php else: ?>

    <?php
        $post_view = 'analytics.components.publisher.post_list_preview';
        if ($editable) {
            $post_view = 'analytics.components.publisher.post_list_preview_editable';
        }
    ?>

    <div id="<?= $view ?>-posts" class="row row-fluid <?= $editable ? 'editable-posts' : '' ?>">
        <div class="posts-container span12">
            <div id="posts-body">

                <ul id="<?= $view ?>-posts-list" class="unstyled span10">

                    <?php foreach ($post_groups as $group_name => $group_posts): ?>
                        <?php
                            if (empty($group_posts)) {
                                continue;
                            }
                        ?>

                        <ul id="<?= $group_name ?>-posts" class="post-group unstyled">

                            <li class="post-group-label text-center">
                                <h4><?= $post_group_names[$group_name] ?></h4>
                            </li>

                            <?php foreach ($group_posts as $post): ?>
                                <?php
                                    try {
                                        $time_slot_type_class = $post->time_slot_type . '-post-list-item';
                                        $time_slot_timestamp  = $post->time_slot_timestamp;
                                    } catch (Exception $e) {
                                        $time_slot_type_class = '';
                                        $time_slot_timestamp  = '';
                                    }
                                ?>

                                <li class="posts-list-row <?= $time_slot_type_class ?>"
                                    id="post-row-<?= $post->id ?>"
                                    data-timestamp="<?= $time_slot_timestamp ?>"
                                >

                                    <?= View::make($post_view, array(
                                        'post'          => $post,
                                        'user_timezone' => $user_timezone,
                                    ));
                                    ?>

                                    <div class="move-post-helper">
                                        <i class="icon-move hidden"
                                           data-toggle="tooltip"
                                           data-title="Drag to Reorder"
                                           data-placement="bottom"
                                        ></i>
                                    </div>

                                    <div class="clearfix"></div>
                                </li>

                            <?php endforeach ?>

                        </ul>

                    <?php endforeach ?>

                </ul>

            </div>
        </div>
    </div>

<?php endif ?>

<?php if ($editable && !$posts->isEmpty()): ?>

    <?php if ($view == 'pending'): ?>
        <div class="form-actions">
            <a href="<?= URL::route('publisher-approve-post') ?>"
               class="btn btn-success btn-large btn-approve-all"
            >
                Approve and Schedule All
            </a>
        </div>
    <?php endif ?>

    <div id="custom-time-options" class="text-center hidden">
        <div class="calendar-wrapper">
            <div class="calendar-header">
                <nav>
                    <a class="calendar-previous pull-left" href="javascript:void(0);">
                        <i class="icon-arrow-left"></i>
                    </a>
                    <a class="calendar-next pull-right" href="javascript:void(0);">
                        <i class="icon-arrow-right"></i>
                    </a>
                </nav>
                <div class="calendar-title text-center"></div>
            </div>

            <div id="posts-calendar" class="fc-calendar-container"></div>
        </div>

        <input value="<?= $date ?>" id="post-date" type="hidden" name="date" />

        <div class="form-inline">
            <select name="hour" id="post-hour" class="input-mini">
                <?php foreach ($hours as $h): ?>
                    <option value="<?= $h ?>"
                        <?php if ($h == $hour): ?>
                            selected
                        <?php endif ?>
                    >
                        <?= $h ?>
                    </option>
                <?php endforeach ?>
            </select>

            <select name="minute" id="post-minute" class="input-mini">
                <?php foreach ($minutes as $m): ?>
                    <? $formatted_m = sprintf('%02d', $m); ?>
                    <option value="<?= $formatted_m ?>"
                        <?php if ($formatted_m == $minute): ?>
                            selected
                        <?php endif ?>
                    >
                        <?= $formatted_m ?>
                    </option>
                <?php endforeach ?>
            </select>

            <div class="btn-group" data-toggle="buttons-radio">
                <button type="button" id="am-btn" class="btn <?= ($am_pm == 'AM') ? 'active' : ''?>">
                    AM
                </button>
                <button type="button" id="pm-btn" class="btn <?= ($am_pm == 'PM') ? 'active' : ''?>">
                    PM
                </button>
            </div>
            <input type="hidden" name="am_pm" id="post-am-pm" value="<?= $am_pm ?>" />
        </div>

        <p>OR</p>

        <button type="button" class="btn btn-primary btn-auto-schedule text-center">Auto-Queue</button>
    </div>

    <div id="lg-post-backdrop" class="hidden"></div>

    <button id="lg-post-close-btn"
            class="btn btn-medium hidden"
            title="Close"
        >
        <i class="icon-cancel"></i>
    </button>

    <div id="lg-post-container" class="post-image-wrapper">
        <div class="post-image-loading"></div>
        
        <img class="post-image" id="lg-post-image" nopin="nopin">

        <div class="post-meta post-image-meta" id="lg-post-image-meta">
                <span class="description-placeholder"
                      id="lg-description-placeholder"
                      data-toggle="tooltip"
                      data-title="Change Description"
                >
                </span>
            <textarea class="description-input hidden"
                      id="lg-description"
                      type="text"
                      rows="4"
                      placeholder="Enter a description"
            ></textarea>
        </div>

        <div class="post-meta post-domain-meta">
            <span class="repin-indicator hidden">
                <i class="icon-repin"></i>
                Repin from
            </span>

            <span class="domain-placeholder" id="lg-domain-placeholder" data-toggle="tooltip" data-title="Change Source URL">
                <img class="domain-icon" src="http://www.google.com/s2/favicons?domain=">
                <span class="domain-text"></span>
            </span>

            <div class="input-prepend hidden">
                    <span class="add-on">
                        <i class="icon-globe"></i>
                    </span>
                <input type="url"
                       class="input-medium link-input"
                       id="lg-link"
                       value=""
                       placeholder="Enter a source url"
                />
            </div>
        </div>

        <div class="post-meta post-board-meta" id="lg-post-board-meta">
            <select class="input-medium board-input"
                    id="lg-board"
                    placeholder="Type a board name"
            ></select>
        </div>

        <div class="post-meta post-date-time-meta" id="lg-post-date-time-meta">
            <div class="post-auto-meta clearfix">
                <span class="label-auto-queue pull-left">
                    Post at next available scheduled time
                </span>

                <?php if ($post->status == Publisher\Post::STATUS_AWAITING_APPROVAL): ?>
                    <a class="btn btn-small btn-success btn-approve-post pull-right"
                       href="<?= URL::route('publisher-approve-post') ?>"
                       data-toggle="tooltip"
                       title="Approve and Schedule"
                    >
                        <i class="icon-checkmark"></i>
                    </a>
                <?php else: ?>
                    <a class="btn btn-small btn-success btn-update-post pull-right hidden"
                       href="<?= URL::route('publisher-update-post') ?>"
                       data-toggle="tooltip"
                       title="Save Changes"
                    >
                        <i class="icon-checkmark"></i>
                    </a>
                <?php endif ?>
            </div>

            <div class="post-manual-meta clearfix hidden">
                <? $carbon_date = \Carbon\Carbon::createFromFormat('Y-m-d', $date, $user_timezone); ?>
                <span class="label-schedule-time pull-left" data-toggle="tooltip" data-title="Change Post Time">
                    <span class="date-placeholder"><?= $carbon_date->format('F j, Y') ?></span> @
                    <span class="hour-placeholder"><?= $hour ?></span>:<span class="minute-placeholder"><?= $minute ?></span>
                    <span class="am-pm-placeholder"><?= $am_pm ?></span>
                    <span class="timezone-placeholder">(<?= $carbon_date->format('T') ?>)</span>
                </span>

                <?php if ($post->status == Publisher\Post::STATUS_AWAITING_APPROVAL): ?>
                    <a class="btn btn-small btn-success btn-approve-post pull-right"
                       href="<?= URL::route('publisher-approve-post') ?>"
                       data-toggle="tooltip"
                       title="Approve and Schedule"
                    >
                        <i class="icon-checkmark"></i>
                    </a>
                <?php else: ?>
                    <a class="btn btn-small btn-success btn-update-post pull-right hidden"
                       href="<?= URL::route('publisher-update-post') ?>"
                       data-toggle="tooltip"
                       title="Save Changes"
                    >
                        <i class="icon-checkmark"></i>
                    </a>
                <?php endif ?>
            </div>
        </div>
    </div>
    <div id="lg-post-calendar-container" class="hidden"></div>
<?php endif ?>