<div class="modal hide fade" id="edit-post-modal">

    <form id='edit-post-form' class="form-horizontal" action="<?= $form_action ?>" method="POST">

        <input type="hidden" name="total" value="1" />
        <input type="hidden" id="post-id" value="<?= object_get($post, 'id'); ?>" />
        <input type="hidden" id="parent-pin" value="<?= object_get($post, 'parent_pin'); ?>" />
        <input type="hidden" id="publisher-source" name="source" value="<?= $source ?>" />

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h3>
                <?= ($post_type == 'new') ? 'New Post' : 'Edit Post'; ?>
            </h3>
        </div>

        <div class="modal-body">

            <div class="row-fluid">

                <div class="post-image-preview-wrapper span4 pull-right">
                    <img id="post-image-preview"
                         src="<?= object_get($post, 'image_url'); ?>"
                         title="<?= object_get($post, 'description'); ?>"
                         alt="<?= object_get($post, 'description'); ?>"
                    >
                </div>

                <div class="span8 pull-left margin-fix">
                    <fieldset>

                        <?php if ($post_type == 'new'): ?>
                            <div class="control-group">
                                <label class="control-label">
                                    <strong>Image URL:</strong>
                                </label>

                                <div class="controls">
                                    <div class="input-prepend">

                                        <span class="add-on">
                                            <i class="icon-pictures"></i>
                                        </span>
                                        <input type="url"
                                               id="post-image"
                                               name="image_url"
                                               class="input-xlarge"
                                               value=""
                                               required
                                        />
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <input type="hidden"
                                   id="post-image"
                                   name="image_url"
                                   value="<?= object_get($post, 'image_url'); ?>"
                            />
                        <?php endif ?>

                        <?php if ($post_type == 'new'): ?>
                            <div class="control-group">
                                <label class="control-label">
                                    <strong>Link:</strong>
                                </label>

                                <div class="controls">
                                    <div class="input-prepend">

                                        <span class="add-on">
                                            <i class="icon-globe"></i>
                                        </span>
                                        <input type="url"
                                               id="post-link"
                                               name="link"
                                               class="input-xlarge"
                                               value=""
                                               required
                                        />

                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <input type="hidden"
                                   id="post-link"
                                   name="link"
                                   value="<?= object_get($post, 'link'); ?>"
                            />
                        <?php endif ?>

                        <div class="control-group">
                            <label class="control-label">
                                <strong>Board:</strong>
                            </label>

                            <div class="controls">
                                <select class="input-xlarge" id="post-board" name="board">
                                    <?php foreach ($boards as $board): ?>
                                        <option id="board_<?= $board->board_id ?>"
                                                value="<?= $board->board_id ?>"
                                                <?php if ($post && $post->getBoardName() == $board->name): ?>
                                                    selected
                                                <?php endif ?>
                                        >
                                            <?= $board->name ?>
                                        </option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>

                        <div class="control-group">
                            <label class="control-label">
                                <strong>Description:</strong>
                            </label>

                            <div class="controls">
                                <textarea class="span"
                                          id="post-description"
                                          type="text"
                                          rows="4"
                                          name="description"
                                          required
                                ><?= object_get($post, 'description'); ?></textarea>
                            </div>
                        </div>

                        <div class="control-group">
                            <label class="control-label">
                                <button type="button"
                                        class="btn <?= ($schedule_type == 'manual') ? 'active' : '' ?>"
                                        id="calendar-toggle"
                                        data-toggle="button"
                                >
                                    <i class="icon-calendar"></i>
                                </button>
                                <input type="hidden"
                                       name="schedule_type"
                                       id="post-schedule-type"
                                       value="<?= $schedule_type ?>"
                                />
                            </label>
                        </div>

                        <div class="custom-time-options <?= ($schedule_type != 'manual') ? 'hidden' : '' ?>">
                            <div class="calendar-wrapper pull-left">
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

                            <select name="hour" id="post-hour" class="input-mini pull-left">
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

                            <select name="minute" id="post-minute" class="input-mini pull-left">
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

                            <div class="btn-group pull-left" data-toggle="buttons-radio">
                                <button type="button" id="am-btn" class="btn <?= ($am_pm == 'AM') ? 'active' : ''?>">
                                    AM
                                </button>
                                <button type="button" id="pm-btn" class="btn <?= ($am_pm == 'PM') ? 'active' : ''?>">
                                    PM
                                </button>
                            </div>
                            <input type="hidden" name="am_pm" id="post-am-pm" value="<?= $am_pm ?>" />

                            <div id="calendar-events" class="well pull-left hidden">
                                <h4 class="calendar-date text-center"></h4>
                                <div class="calendar-posts"></div>
                            </div>
                        </div>

                    </fieldset>
                </div>

            </div>

        </div>

        <div class="modal-footer">
            <input type="button"
                   class="btn"
                   id="close-btn"
                   value="<?= $close_btn_text ?>"
            />
            <input type="submit"
                   class="btn btn-primary"
                   id="submit-btn"
                   value="<?= $submit_btn_text ?>"
            />
        </div>

    </form>
</div>

<script type="text/javascript">
var upcoming_posts = {
    <?= $posts_caldata ?>
};
</script>

<link rel="stylesheet" href="/css/bootstrap-switch.min.css">
<script src="/js/bootstrap-switch.min.js"></script>

<link rel="stylesheet" href="/css/calendario.css">
<script src="/js/jquery.calendario.js"></script>

<script type="text/javascript">
    var editPostModal = {

        closeButtonCallback: null,

        submitCallback: null,

        init: function() {
            $('#post-image').on('change', function() {
                $('#post-image-preview').attr('src', $(this).val());
            });

            $('.post-image-preview-wrapper').on('click', function() {
                if ($('#post-image').val() == '') {
                    $('#post-image').focus();
                } else if ($('#post-link').val()) {
                    window.open($('#post-link').val());
                }
            });

            $('#post-description').on('change', function() {
                $('#post-image-preview').attr({
                    title: $(this).val(),
                    alt: $(this).val()
                });
            });

            $('#calendar-toggle').on('click', function() {
                var type = $(this).hasClass('active') ? 'auto' : 'manual';
                editPostModal.toggleScheduleType(type);
            });

            $('#am-btn').on('click', function() {
                $('#post-am-pm').val('AM');
            });

            $('#pm-btn').on('click', function() {
                $('#post-am-pm').val('PM');
            });

            $('#close-btn').on('click', editPostModal.close);

            $('#edit-post-form').on('submit', function(event) {
                event.preventDefault();
                editPostModal.submitForm();
            });

            postsCalendar.init();
        },

        show: function() {
            postsCalendar.selectDate($('#post-date').val());
            $('#edit-post-modal').modal('show');
        },

        hide: function() {
            $('#edit-post-modal').modal('hide');
        },

        close: function() {
            if (editPostModal.closeButtonCallback) {
                $.Callbacks()
                    .add(editPostModal.closeButtonCallback)
                    .fire(editPostModal.getPostData())
                    .remove(editPostModal.closeButtonCallback);
            } else {
                editPostModal.hide();
            }
        },

        remove: function() {
            $('#edit-post-modal').remove();
        },

        toggleScheduleType: function(type) {
            if (type == 'manual') {
                $('#post-schedule-type').val('manual');
                $('.custom-time-options').slideDown();
            } else {
                $('#post-schedule-type').val('auto');
                $('.custom-time-options').slideUp();
            }
        },

        fillPostData: function(post) {
            $('#post-image-preview').attr({
                src: post.image_url,
                title: post.description,
                alt: post.description
            });
            $('#post-image').val(post.image_url);
            $('#post-description').val(post.description);
            $('#post-link-preview').html(post.link);
            $('#post-link').val(post.link);
            $('#post-board').val(post.board);

            if (post.schedule_type == 'manual') {
                $('#calendar-toggle').addClass('active');
            } else {
                $('#calendar-toggle').removeClass('active');
            }

            editPostModal.toggleScheduleType(post.schedule_type);

            if (post.date) {
                $('#post-date').val(post.date);
            }

            $('#post-hour').val(post.hour);
            $('#post-minute').val(post.minute);

            if (post.am_pm && post.am_pm == 'PM') {
                $('#post-am-pm').val('PM');
                $('#am-btn').removeClass('active');
                $('#pm-btn').addClass('active');
            } else {
                $('#post-am-pm').val('AM');
                $('#pm-btn').removeClass('active');
                $('#am-btn').addClass('active');
            }

            $('#post-id').val(post.id);
            $('#parent-pin').val(post.parent_pin);
        },

        getPostData: function() {
            return {
                id: $('#post-id').val(),
                parent_pin: $('#parent-pin').val(),
                link: $('#post-link').val(),
                image_url: $('#post-image').val(),
                description: $('#post-description').val(),
                board: $('#post-board').val(),
                board_name: $('#post-board option:selected').text().trim(),
                schedule_type: $('#post-schedule-type').val(),
                date: $('#post-date').val(),
                hour: $('#post-hour').val(),
                minute: $('#post-minute').val(),
                am_pm: $('#post-am-pm').val(),
                source: $('#publisher-source').val()
            };
        },

        validateForm: function() {
            if ($('#post-image').val() == '') {
                $('#post-image').focus();
                return false;
            }

            if ($('#post-description').val() == '') {
                $('#post-description').focus();
                return false;
            }

            if ($('#post-link').val() == '') {
                $('#post-link').focus();
                return false;
            }

            return true;
        },

        submitForm: function() {
            var post_data = editPostModal.getPostData();
            post_data.total = 1;

            $.ajax({
                type: 'POST',
                data: post_data,
                dataType: 'json',
                url: $('#edit-post-form').attr('action'),
                cache: false,
                success: function (response) {
                    if (response.success != true) {
                        alert(response.message + '\n\n' + response.errors.join('\n'));
                        return;
                    }

                    if (editPostModal.submitCallback) {
                        response.post_data = editPostModal.getPostData();

                        $.Callbacks()
                            .add(editPostModal.submitCallback)
                            .fire(response)
                            .remove(editPostModal.submitCallback);
                    } else if (response.redirect) {
                        location.href = response.redirect;
                    } else {
                        editPostModal.hide();
                    }
                }
            });
        }
    };

    var postsCalendar = {
        calendario: null,

        init: function () {
            postsCalendar.calendario = $('#posts-calendar').calendario({
                onDayClick: postsCalendar.handleDateSelection,
                onDayHover: postsCalendar.showPosts,
                displayWeekAbbr : true,
                caldata: upcoming_posts
            });

            postsCalendar.updateCalendarHeader();

            $('.calendar-next').on('click', function() {
                postsCalendar.calendario.gotoNextMonth(postsCalendar.refreshMonth);
            });

            $('.calendar-previous').on( 'click', function() {
                postsCalendar.calendario.gotoPreviousMonth(postsCalendar.refreshMonth);
            });
        },

        updateCalendarHeader: function() {
            $('.calendar-title').html(postsCalendar.calendario.getMonthName() + ' ' + postsCalendar.calendario.getYear());
        },

        highlightPostDays: function() {
            $('.fc-calendar .fc-row > div').has('.calendar-timeslots').addClass('fc-day-with-events');
        },

        toggleSelectedDay: function(day) {
            $('.fc-selected-day').removeClass('fc-selected-day');
            $('.fc-calendar .fc-row > div').has('.fc-date:contains("' + day + '")').first().addClass('fc-selected-day');
        },

        refreshMonth: function() {
            postsCalendar.updateCalendarHeader();
            postsCalendar.highlightPostDays();

            var date = new Date($('#post-date').val());
            if (postsCalendar.calendario.getMonth() == date.getMonth() + 1) {
                postsCalendar.toggleSelectedDay(date.getDate() + 1);
            }
        },

        showPosts: function($el, $contentEl, dateProperties) {
            if ($contentEl.length == 0) {
                $('#calendar-events').addClass('hidden');
            } else {
                $('#calendar-events .calendar-date').html(
                    'Scheduled on ' + dateProperties.monthname + ' ' + dateProperties.day
                );
                $('#calendar-events .calendar-posts').html($contentEl.html());
                $('#calendar-events').removeClass('hidden');

                $('.calendar-posts').stop(true).scrollTop(0);
                postsCalendar.scrollPosts();
            }
        },

        scrollPosts: function() {
            if ($('#calendar-events').hasClass('hidden') || $('.calendar-posts .calendar-post').length <= 2) {
                return;
            }

            var last_post = $('.calendar-posts .calendar-post').last();

            var position = 0;
            var duration = 1000 * $('.calendar-posts .calendar-post').length;
            var delay = 0;
            if ($('.calendar-posts').scrollTop() == 0) {
                position = last_post.position().top + last_post.outerHeight();
                duration = 1500 * $('.calendar-posts .calendar-post').length;
                delay = 1500;
            }

            $('.calendar-posts').delay(delay).animate(
                {
                    scrollTop: position
                },
                duration,
                'swing',
                postsCalendar.scrollPosts
            );
        },

        selectDate: function(date_string) {
            var date = new Date(date_string);
            postsCalendar.calendario.gotoMonth(date.getMonth() + 1, date.getFullYear(), function() {
                postsCalendar.updateCalendarHeader();
                postsCalendar.highlightPostDays();
                postsCalendar.toggleSelectedDay(date.getDate() + 1);
            });
        },

        handleDateSelection: function($el, $content, dateProperties) {
            var date = new Date(dateProperties.year, dateProperties.month - 1, parseInt(dateProperties.day));
            var today = new Date();
            today.setHours(0, 0, 0, 0);

            if (date < today) {
                return false;
            }

            var month_string = dateProperties.month.toString();
            if (dateProperties.month < 10) {
                month_string = '0' + dateProperties.month.toString();
            }

            var day_string = dateProperties.day;
            if (parseInt(dateProperties.day) < 10) {
                day_string = '0' + dateProperties.day;
            }

            var date_string = dateProperties.year.toString() + '-' + month_string + '-' + day_string;

            $('#post-date').val(date_string);

            postsCalendar.toggleSelectedDay(dateProperties.day);

            return false;
        }
    };
</script>