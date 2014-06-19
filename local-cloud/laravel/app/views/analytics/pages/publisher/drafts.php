<?= $navigation ?>

<?php if ($drafts->isEmpty()): ?>

    <div class="row row-fluid margin-fix">
        <div class="span12">
            <h3>Draft Posts</h3>
        </div>
    </div>

    <p>You don't have any drafts to schedule!</p>

    <?= View::make('analytics.components.publisher.bookmarklet_cta'); ?>

<?php else: ?>

    <div class="row row-fluid margin-fix">
        <div class="span8">
            <h3>
                Add Posts to Queue

                <a id="publisher-drafts-help"
                   data-toggle="popover"
                   data-container="body"
                   data-placement="bottom"
                   data-original-title="&lt;strong&gt;Scheduling Drafts&lt;/strong&gt;"
                   data-content="
                       &lt;p&gt;
                           From this page, you can edit any drafts that are currently in a
                           &lt;em&gt;pre-queue&lt;/em&gt; state. This means they have not been scheduled to
                           post yet.
                       &lt;/p&gt;
                       &lt;p&gt;
                           To the right, you'll see your next available scheduled times. These are
                           the times that your drafts will be published if you choose to use our
                           auto scheduler.
                       &lt;/p&gt;
                       &lt;strong&gt;Editing Drafts&lt;/strong&gt;
                       &lt;p&gt;
                           Simply click on a post preview to change its description, boards, and
                           post time. You may also add a board to all of your drafts
                           by using the &lt;strong&gt;Add Board to All&lt;/strong&gt; input.
                           Just start typing a board name and select it.
                       &lt;/p&gt;
                       &lt;strong&gt;Manually Scheduling Posts&lt;/strong&gt;
                       &lt;p&gt;
                           Click the post date to change your post's scheduled time. Select your preferred date
                           and time or switch back to Auto-Queue to schedule your post in the next
                           available time slot.
                       &lt;/p&gt;
                       &lt;p&gt;
                           You can pick and choose which drafts to <?= $admin_user ? 'schedule' : 'submit for approval' ?>
                           by clicking the
                           &lt;a class=&quot;btn btn-mini btn-success&quot; href=&quot;javascript:void(0);&quot;&gt;
                               &lt;i class=&quot;icon-checkmark&quot;&gt;&lt;/i&gt;
                           &lt;/a&gt;
                           when finished editing a post. If you're ready to
                           <?= $admin_user ? 'schedule' : 'submit' ?> all of your drafts, just click the
                           &lt;strong&gt;<?= $admin_user ? 'Schedule All' : 'Submit All for Approval' ?>&lt;/strong&gt;
                           button!
                       &lt;/p&gt;
                   "
                >
                    <i class="icon-help"></i>
                </a>
            </h3>

            <form class="form-horizontal">

                <?php if ($user_accounts->count() > 1): ?>
                    <div class="control-group">
                        <label class="control-label">
                            <strong>Set Account:</strong>
                        </label>

                        <div class="controls">
                            <select class="input-large" id="publisher-switch-account"
                                    onchange="location = this.options[this.selectedIndex].value;"
                            >
                                <?php foreach ($user_accounts as $i => $user_account): ?>
                                    <option id="user-account-<?= $user_account->account_id ?>"
                                            value="?account=<?= $i ?>"
                                            <?= ($user_account->account_id == $current_account->account_id) ? 'selected' : '' ?>
                                    >
                                        <?= $user_account->username ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                    </div>
                <?php endif ?>

                <div class="control-group">
                    <label class="control-label">
                        <strong>Add Board to All:</strong>
                    </label>

                    <div class="controls">
                        <select multiple class="input-large" id="global-board" placeholder="Type a board name"></select>
                    </div>
                </div>

            </form>
        </div>

        <div class="span4">
            <h4>Next Available Scheduled Times</h4>

            <ul id="upcoming-timeslot-list" class="unstyled">
                <?php foreach ($upcoming_timeslots as $timestamp => $timeslot): ?>
                    <li id="upcoming-timeslot-<?= $timestamp ?>">
                        <?= $timeslot->getPrettyDay() ?>,
                        <?= \Carbon\Carbon::createFromTimestamp($timestamp, $user_timezone)->format('F j') ?>
                        @
                        <?= \Carbon\Carbon::createFromTimestamp(
                                strtotime($timeslot->getPrettyTime() . ' ' . $timeslot->timezone),
                                $user_timezone
                            )->format('g:i A (T)');
                        ?>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    </div>

    <div class="row row-fluid posts-row editable-posts" id="draft-posts">
        <div class="posts-container span12">

            <form action="<?= URL::route('publisher-create-post') ?>" method="POST" id="all-drafts-form">
                <div id="posts-body">
                    <div id="posts-image-list">
                        <input type="hidden" name="total" value="<?= $drafts->count() ?>" />

                        <?php foreach ($drafts as $i => $draft): ?>
                            <?= View::make('analytics.components.publisher.post_fields', array(
                                    'id'            => $i + 1,
                                    'post'          => $draft,
                                    'hour'          => $hour,
                                    'minute'        => $minute,
                                    'date'          => $date,
                                    'am_pm'         => $am_pm,
                                    'user_timezone' => $user_timezone,
                                    'admin_user'    => $admin_user,
                                ));
                            ?>
                        <?php endforeach ?>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success btn-large">
                        <?= $admin_user ? 'Schedule All' : 'Submit All for Approval' ?>
                    </button>
                </div>
            </form>

        </div>
    </div>

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

        <input value="<?= $date; ?>" id="post-date" type="hidden" name="date" />

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
                    <img class="domain-icon" src="http://www.google.com/s2/favicons?domain=<?= $domain ?>">
                    <span class="domain-text"><?= $domain ?></span>
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
                <select multiple
                        class="input-medium board-input"
                        id="lg-board"
                        placeholder="Type a board name"
                    ></select>
            </div>

            <div class="post-meta post-date-time-meta" id="lg-post-date-time-meta">
                <div class="post-auto-meta clearfix">
                    <span class="label-auto-queue pull-left">
                        Post at next available scheduled time
                    </span>
                    <a class="btn btn-small btn-success btn-queue-post pull-right"
                       href="<?= URL::route('publisher-create-post') ?>"
                       data-toggle="tooltip"
                       title="<?= $admin_user ? 'Add to Queue' : 'Submit for Approval' ?>"
                    >
                        <i class="icon-checkmark"></i>
                    </a>
                </div>

                <div class="post-manual-meta clearfix hidden">
                    <? $carbon_date = \Carbon\Carbon::createFromFormat('Y-m-d', $date, $user_timezone); ?>
                    <span class="label-schedule-time pull-left" data-toggle="tooltip" data-title="Change Post Time">
                        <span class="date-placeholder"><?= $carbon_date->format('F j, Y') ?></span> @
                        <span class="hour-placeholder"><?= $hour ?></span>:<span class="minute-placeholder"><?= $minute ?></span>
                        <span class="am-pm-placeholder"><?= $am_pm ?></span>
                        <span class="timezone-placeholder">(<?= $carbon_date->format('T') ?>)</span>
                    </span>
                    <a class="btn btn-small btn-success btn-schedule-post pull-right"
                       href="<?= URL::route('publisher-create-post') ?>"
                       data-toggle="tooltip"
                       title="<?= $admin_user ? 'Schedule' : 'Submit for Approval' ?>"
                    >
                        <i class="icon-checkmark"></i>
                    </a>
                </div>
            </div>
    </div>
    <div id="lg-post-calendar-container" class="hidden"></div>

<?php endif ?>