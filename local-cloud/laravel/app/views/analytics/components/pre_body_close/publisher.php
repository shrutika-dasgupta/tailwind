<link href="/css/bootstrap-tagsinput.css" rel="stylesheet">
<script src="/js/bootstrap-tagsinput.js"></script>

<link href="/css/bootstrap-switch.min.css" rel="stylesheet">
<script src="/js/bootstrap-switch.min.js"></script>

<link rel="stylesheet" href="/css/calendario.css">
<script src="/js/jquery.calendario.js"></script>

<script type="text/javascript">
    var boards = [
        <?php foreach ($boards as $board): ?>
        {
            id: '<?= $board->board_id ?>',
            name: "<?= addslashes($board->name) ?>",
            collaborator: <?= $board->is_collaborator ?>
        }<?= ($board->board_id == $boards->last()->board_id) ? '' : ',' ?>
        <?php endforeach ?>
    ];

    var publisher_admin_user = <?= (int) $admin_user ?>;

    var postsCalendar = {
        calendario: null,

        init: function () {
            postsCalendar.calendario = $('#posts-calendar').calendario({
                onDayClick: postsCalendar.handleDateSelection,
                displayWeekAbbr : true
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

            var date_parts = $('#post-date').val().split('-');
            var date = new Date(date_parts[0], date_parts[1] - 1, date_parts[2]);
            if (postsCalendar.calendario.getMonth() == date.getMonth() + 1) {
                postsCalendar.toggleSelectedDay(date.getDate() + 1);
            }
        },

        selectDate: function(date_string) {
            var date_parts = date_string.split('-');
            var date = new Date(date_parts[0], date_parts[1] - 1, date_parts[2]);

            postsCalendar.calendario.gotoMonth(date.getMonth() + 1, date.getFullYear(), function() {
                postsCalendar.updateCalendarHeader();
                postsCalendar.highlightPostDays();
                postsCalendar.toggleSelectedDay(date.getDate());
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

            $('#post-date').val(date_string).trigger('change', dateProperties);

            postsCalendar.toggleSelectedDay(dateProperties.day);

            return false;
        }
    };

    var postModalView = {
        init: function() {
            $('#lg-post-backdrop, #lg-post-close-btn').on('click', postModalView.hide);

            $('#lg-board').on('itemAdded', function(event) {
                var post_id = $('#lg-post-container').attr('data-post-id');
                $('#board-' + post_id).tagsinput('add', event.item);
            });

            $('#lg-board').on('itemRemoved', function(event) {
                var post_id = $('#lg-post-container').attr('data-post-id');
                $('#board-' + post_id).tagsinput('remove', event.item);
            });
        },

        calculateHeight: function()
        {
            var height = 0;

            $('#lg-post-container').children(':visible').each(function() {
                height += $(this).outerHeight(true);
            });

            return height;
        },

        replaceLoadingImage: function()
        {
            $('#lg-post-container .post-image-loading').hide();
            $('#lg-post-image').fadeIn(400, function() {
                var new_height = postModalView.calculateHeight();
                if (new_height != $('#lg-post-container').height()) {
                    $('#lg-post-container').animate({
                        height: new_height
                    });
                }
            });
        },

        show: function(target) {
            var post_wrapper = $(target).parents('.post-image-wrapper, .post-wrapper');
            var target_image = $(target);
            if (target_image.prop('tagName') != 'IMG') {
                target_image = target_image.find('img');
            }

            $('#lg-post-container').attr('data-post-id', post_wrapper.attr('data-post-id'));
            $('#lg-post-container').css({
                position: 'absolute',
                top: $(target).parents('#main-content-scroll').scrollTop() + post_wrapper.position().top,
                left: post_wrapper.position().left,
                height: 0,
                width: '60%'
            });

            $('#lg-post-container .post-image-loading').show();

            $('#lg-post-image').hide()
                .attr({
                    'src': target_image.attr('src'),
                    'alt': target_image.attr('alt'),
                    'title': target_image.attr('title'),
                    'data-source-url': target_image.attr('data-source-url')
                })
                .on('load', postModalView.replaceLoadingImage)
                .each(function() {
                    // If image has already loaded, show it.
                    if (this.complete) {
                        postModalView.replaceLoadingImage();
                    }
                });

            var description = post_wrapper.find('.description-input').val();
            $('#lg-description-placeholder').text(description);
            $('#lg-description').val(description);

            if (description == '') {
                $('#lg-description-placeholder').hide();
                $('#lg-description').show();
            } else {
                $('#lg-description-placeholder').show();
                $('#lg-description').hide();
            }

            var is_repin = post_wrapper.find('.post-repin-meta').length > 0;
            $('#lg-post-container .post-domain-meta').toggleClass('post-repin-meta', is_repin);
            $('#lg-post-container .repin-indicator').toggleClass('hidden', !is_repin);

            $('#lg-domain-placeholder .domain-icon').attr('src', post_wrapper.find('.domain-icon').attr('src'));
            $('#lg-domain-placeholder .domain-text').text(post_wrapper.find('.domain-text').text());
            $('#lg-link').val(post_wrapper.find('.link-input').val());

            $('#lg-board').tagsinput('removeAll');
            var boards = post_wrapper.find('.board-input').tagsinput('items');
            for (var i=0; i < boards.length; i++) {
                $('#lg-board').tagsinput('add', boards[i]);
            }

            $('#lg-post-backdrop').removeClass('hidden').animate({
                opacity: 0.8
            });

            var schedule_type = post_wrapper.find('.schedule-type-input').val();
            var date_string = post_wrapper.find('.date-input').val();
            var hours = post_wrapper.find('.hour-input').val();
            var minutes = post_wrapper.find('.minute-input').val();
            var am_pm = post_wrapper.find('.am-pm-input').val();

            $('#post-date').val(date_string);
            $('#post-hour').val(hours);
            $('#post-minute').val(minutes);
            $('#post-am-pm').val(am_pm);

            if (am_pm == 'PM') {
                $('#am-btn').removeClass('active');
                $('#pm-btn').addClass('active');
            } else {
                $('#pm-btn').removeClass('active');
                $('#am-btn').addClass('active');
            }

            postsCalendar.selectDate(date_string);

            $('#lg-post-container .date-placeholder').html(post_wrapper.find('.date-placeholder').html());
            $('#lg-post-container .hour-placeholder').html(post_wrapper.find('.hour-placeholder').html());
            $('#lg-post-container .minute-placeholder').html(post_wrapper.find('.minute-placeholder').html());

            if (schedule_type == 'auto') {
                $('#lg-post-container .post-manual-meta').hide();
                $('#lg-post-container .post-auto-meta').show();
            } else {
                $('#lg-post-container .post-auto-meta').hide();
                $('#lg-post-container .post-manual-meta').show();
            }

            $('#lg-post-container .btn-update-post').toggleClass('hidden', post_wrapper.find('.btn-update-post').filter(':first').hasClass('hidden'));

            $('#lg-post-calendar-container').css({
                width: '35%',
                position: 'absolute',
                right: 10,
                top: $('#main-content-scroll').scrollTop() + 50
            }).html($('#custom-time-options').show());

            $('#lg-post-close-btn').css('top', $('#main-content-scroll').scrollTop() + 15);

            // Show the container so that we can calculate dynamic height. Container height at this point is 0.
            $('#lg-post-container').show();

            var modal_height = postModalView.calculateHeight();

            // Reset container width to 0 for animation.
            $('#lg-post-container').css('width', 0);

            $('#lg-post-container').animate({
                left: $('#posts-body').position().left,
                top: $('#main-content-scroll').scrollTop() + 50,
                width: '60%',
                height: modal_height,
                opacity: 1
            }, function() {
                $('#lg-post-close-btn, #lg-post-calendar-container').fadeIn();
            });
        },

        hide: function(event) {
            if ($('#lg-post-container:visible').length == 0) {
                return;
            }

            $('#lg-post-close-btn, #lg-post-container, #lg-post-calendar-container').fadeOut();

            var time_options = $('#lg-post-calendar-container').find('#custom-time-options');
            $('body').append(time_options);
            time_options.hide();

            $('#lg-post-backdrop').animate({opacity: 0}, function() {
                $(this).addClass('hidden');
            });
        }
    };

    var postDateSelector = {
        show : function(event) {
            var element = $(event.target);
            if (element.hasClass('btn') || element.parents('.btn').length) {
                return;
            }

            if (element.parents('#lg-post-container').length) {
                return;
            }

            var meta_element = element;
            if (!meta_element.hasClass('post-date-time-meta')) {
                meta_element = meta_element.parents('.post-date-time-meta');
            }

            if (meta_element.next('div.popover:visible').length) {
                return;
            }

            postDateSelector.hide();

            var date_string = meta_element.find('.date-input').val();
            var hours = meta_element.find('.hour-input').val();
            var minutes = meta_element.find('.minute-input').val();
            var am_pm = meta_element.find('.am-pm-input').val();

            $('#post-date').val(date_string);
            $('#post-hour').val(hours);
            $('#post-minute').val(minutes);
            $('#post-am-pm').val(am_pm);

            if (am_pm == 'PM') {
                $('#am-btn').removeClass('active');
                $('#pm-btn').addClass('active');
            } else {
                $('#pm-btn').removeClass('active');
                $('#am-btn').addClass('active');
            }

            postsCalendar.selectDate(date_string);

            meta_element.parents('.post-image-wrapper, .post-wrapper').find('.schedule-type-input').val('manual');

            meta_element.addClass('post-meta-focus');

            meta_element.find('.post-auto-meta').hide();
            meta_element.find('.post-manual-meta').show();

            meta_element.popover({
                trigger: 'manual',
                placement: 'bottom',
                html : true,
                content: function () {
                    return $('#custom-time-options').show();
                }
            }).popover('show');

            $('#main-content-scroll').animate({
                scrollTop: $('#main-content-scroll').scrollTop() + meta_element.next('div.popover:visible').outerHeight()
            });
        },

        hide: function() {
            $('.post-date-time-meta').each(function() {
                var popover = $(this).next('div.popover:visible');
                if (popover.length) {
                    var time_options = popover.find('#custom-time-options');
                    $('body').append(time_options);
                    time_options.hide();
                    $(this).popover('destroy');
                    $(this).removeClass('post-meta-focus');
                }
            });
        }
    };

    var postFields = {
        init: function() {
            $('.post-image-wrapper, .post-image').hover(
                function() {
                    $(this).children('.post-actions').css('opacity', 1);
                },
                function() {
                    $(this).children('.post-actions').css('opacity', 0);
                }
            );

            $('.post-image, .post-image-preview-wrapper').on('click', function() {
                var post_wrapper = $(this).parents('.post-image-wrapper, .post-wrapper');
                if (post_wrapper.attr('id') == 'lg-post-container') {
                    window.open($(this).attr('data-source-url'));
                    return;
                }

                postModalView.show(this);
            });

            $('.post-image-meta').on('click', function() {
                $(this).addClass('post-meta-focus');
                $(this).children('.description-placeholder').hide();
                $(this).children('.description-input')
                    .fadeIn()
                    .select();
            });

            $('.description-input').on('blur', function(event) {
                $(this).parents('.post-meta-focus').removeClass('post-meta-focus');
                postFields.updateDescription(event);
            });

            $('.post-domain-meta').on('click', function() {
                if (!$(this).hasClass('post-repin-meta')) {
                    $(this).addClass('post-meta-focus');
                    $(this).children('.domain-placeholder').hide();
                    $(this).children('.input-prepend').fadeIn();
                    $(this).find('.link-input').select();
                }
            });

            $('.link-input').on('blur', postFields.updateLink);

            $('.board-input').tagsinput({
                typeahead: {
                    source: boards,
                    freeInput: false
                },
                itemValue: 'id',
                itemText: 'name',
                confirmKeys: [13, 44],
                tagClass: function(item) {
                    return (item.collaborator ? 'label label-success label-collaborator-board' : 'label label-info');
                }
            });

            // Initialize all of the board tagsinputs.
            $('.board-input').each(function() {
                var board_id = $(this).val();
                if (!board_id) {
                    return;
                }

                var i, board_object = null;
                for (i = 0; i < boards.length; i++) {
                    if (boards[i].id == board_id) {
                        board_object = boards[i];
                        break;
                    }
                }

                $(this).tagsinput('add', board_object);
            });

            $('.board-input').on('itemAdded itemRemoved', function() {
                $('#lg-post-container .btn-update-post, #' + $(this).parents('.post-image-wrapper, .post-wrapper').attr('id') + ' .btn-update-post').removeClass('hidden');
            });

            $('.post-board-meta').on('click', function() {
                $(this).find('.board-input').tagsinput('focus');
            });

            $('.post-board-meta .bootstrap-tagsinput input').on('focus', function() {
                $(this).parents('.post-meta').addClass('post-meta-focus');
            });

            $('.post-board-meta .bootstrap-tagsinput input').on('blur', function() {
                $(this).parents('.post-meta-focus').removeClass('post-meta-focus');
            });

            $('.post-date-time-meta').on('click', postDateSelector.show);

            $('.btn-auto-schedule').on('click', postFields.setAutoScheduleType);

            $('#post-date').on('change', postFields.updateDate);

            $('#post-hour').on('change', postFields.updateHour);

            $('#post-minute').on('change', postFields.updateMinute);

            $('#am-btn').on('click', postFields.setAM);

            $('#pm-btn').on('click', postFields.setPM);

            $('body').on('click', function(event) {
                var element = $(event.target);

                if ($('.popover:visible')
                    && !element.hasClass('.post-date-time-meta')
                    && element.parents('.post-date-time-meta').length == 0
                    && !element.hasClass('popover')
                    && element.parents('.popover').length == 0
                ) {
                    postDateSelector.hide();
                }
            });
        },

        updateDescription: function(event) {
            var element = $(event.target);

            if (element.val() == '') {
                return;
            }

            element.hide();
            element.siblings('.description-placeholder')
                .html(element.val())
                .fadeIn();

            element.parents('.post-image-wrapper, .post-wrapper').find('.btn-update-post').removeClass('hidden');

            if (element.parents('#lg-post-container').length) {
                var post_id = element.parents('#lg-post-container').attr('data-post-id');
                $('#description-' + post_id).val(element.val())
                    .hide();
                $('#description-placeholder-' + post_id).html(element.val())
                    .fadeIn();

                $('#lg-post-container .btn-update-post').removeClass('hidden');
            }
        },

        updateLink: function(event) {
            var element = $(event.target);

            var url_pattern = /(http|https):\/\/[\w-]+(\.[\w-]+)+([\w.,@?^=%&amp;:\/~+#-]*[\w@?^=%&amp;\/~+#-])?/;
            if (!url_pattern.test(element.val())) {
                return;
            }

            var link_url = element.val();
            var a = $('<a>', {href:link_url})[0];
            var domain = a.hostname.replace('www.', '');

            element.parent().hide();

            var post_wrapper = element.parents('.post-image-wrapper, .post-wrapper');
            post_wrapper.find('.post-image').attr('data-source-url', link_url);
            post_wrapper.find('.domain-icon').attr('src', 'http://www.google.com/s2/favicons?domain=' + domain);
            post_wrapper.find('.domain-text').text(domain);
            post_wrapper.find('.domain-placeholder').fadeIn();

            element.parents('.post-meta-focus').removeClass('post-meta-focus');

            post_wrapper.find('.btn-update-post').removeClass('hidden');

            if (post_wrapper.attr('id') == 'lg-post-container') {
                var original_post_wrapper = $('#image-' + post_wrapper.attr('data-post-id'));
                original_post_wrapper.find('.link-input').val(link_url);
                original_post_wrapper.find('.post-image').attr('data-source-url', link_url);
                original_post_wrapper.find('.domain-icon').attr('src', 'http://www.google.com/s2/favicons?domain=' + domain);
                original_post_wrapper.find('.domain-text').text(domain);
                original_post_wrapper.find('.domain-placeholder').fadeIn();
                original_post_wrapper.find('.btn-update-post').removeClass('hidden');
            }
        },

        setAutoScheduleType: function(event) {
            postFields.updateScheduleType(event, 'auto');
        },

        setManualScheduleType: function(event) {
            postFields.updateScheduleType(event, 'manual');
        },

        updateScheduleType: function(event, schedule_type) {
            var element = $(event.target);

            var post_wrapper = element.parents('.post-image-wrapper, .post-wrapper');
            if (!post_wrapper.length) {
                post_wrapper = $('#image-' + $('#lg-post-container').attr('data-post-id'));
            }

            post_wrapper.find('.schedule-type-input').val(schedule_type);

            if (schedule_type == 'manual') {
                $('#lg-post-container .post-auto-meta, #' + post_wrapper.attr('id') + ' .post-auto-meta').hide();
                $('#lg-post-container .post-manual-meta, #' + post_wrapper.attr('id') + ' .post-manual-meta').show();
            } else {
                $('#lg-post-container .post-manual-meta, #' + post_wrapper.attr('id') + ' .post-manual-meta').hide();
                $('#lg-post-container .post-auto-meta, #' + post_wrapper.attr('id') + ' .post-auto-meta').show();
                postDateSelector.hide();
            }

            $('#lg-post-container .btn-update-post, #' + post_wrapper.attr('id') + ' .btn-update-post').removeClass('hidden');
        },

        updateDate: function(event, dateProperties) {
            var element = $(event.target);

            var post_wrapper = element.parents('.post-image-wrapper, .post-wrapper');
            if (!post_wrapper.length) {
                post_wrapper = $('#image-' + $('#lg-post-container').attr('data-post-id'));
                $('#lg-post-container').find('.date-placeholder').text(
                    dateProperties.monthname + ' ' + dateProperties.day + ', ' + dateProperties.year
                );

                postFields.setManualScheduleType(event);
            }

            post_wrapper.find('.date-input').val(element.val());
            post_wrapper.find('.date-placeholder').text(
                dateProperties.monthname + ' ' + dateProperties.day + ', ' + dateProperties.year
            );

            $('#lg-post-container .btn-update-post, #' + post_wrapper.attr('id') + ' .btn-update-post').removeClass('hidden');
        },

        updateHour: function(event) {
            var element = $(event.target);

            var post_wrapper = element.parents('.post-image-wrapper, .post-wrapper');
            if (!post_wrapper.length) {
                post_wrapper = $('#image-' + $('#lg-post-container').attr('data-post-id'));
                $('#lg-post-container').find('.hour-placeholder').text(element.val());

                post_wrapper.find('.schedule-type-input').val('manual');
                $('#lg-post-container .post-auto-meta, #' + post_wrapper.attr('id') + ' .post-auto-meta').hide();
                $('#lg-post-container .post-manual-meta, #' + post_wrapper.attr('id') + ' .post-manual-meta').show();
            }

            post_wrapper.find('.hour-input').val(element.val());
            post_wrapper.find('.hour-placeholder').text(element.val());

            $('#lg-post-container .btn-update-post, #' + post_wrapper.attr('id') + ' .btn-update-post').removeClass('hidden');
        },

        updateMinute: function(event) {
            var element = $(event.target);

            var post_wrapper = element.parents('.post-image-wrapper, .post-wrapper');
            if (!post_wrapper.length) {
                post_wrapper = $('#image-' + $('#lg-post-container').attr('data-post-id'));
                $('#lg-post-container').find('.minute-placeholder').text(element.val());

                post_wrapper.find('.schedule-type-input').val('manual');
                $('#lg-post-container .post-auto-meta, #' + post_wrapper.attr('id') + ' .post-auto-meta').hide();
                $('#lg-post-container .post-manual-meta, #' + post_wrapper.attr('id') + ' .post-manual-meta').show();
            }

            post_wrapper.find('.minute-input').val(element.val());
            post_wrapper.find('.minute-placeholder').text(element.val());

            $('#lg-post-container .btn-update-post, #' + post_wrapper.attr('id') + ' .btn-update-post').removeClass('hidden');
        },

        setAM: function(event) {
            postFields.updateAmPm(event, 'AM');
        },

        setPM: function(event) {
            postFields.updateAmPm(event, 'PM');
        },

        updateAmPm: function(event, value) {
            var element = $(event.target);

            var post_wrapper = element.parents('.post-image-wrapper, .post-wrapper');
            if (!post_wrapper.length) {
                post_wrapper = $('#image-' + $('#lg-post-container').attr('data-post-id'));
                $('#lg-post-container').find('.am-pm-placeholder').text(value);

                post_wrapper.find('.schedule-type-input').val('manual');
                $('#lg-post-container .post-auto-meta, #' + post_wrapper.attr('id') + ' .post-auto-meta').hide();
                $('#lg-post-container .post-manual-meta, #' + post_wrapper.attr('id') + ' .post-manual-meta').show();
            }

            post_wrapper.find('.am-pm-input').val(value);
            post_wrapper.find('.am-pm-placeholder').text(value);

            $('#lg-post-container .btn-update-post, #' + post_wrapper.attr('id') + ' .btn-update-post').removeClass('hidden');
        }
    };

    var draftPosts = {
        delete: function(elem) {
            var id = $(elem).attr('data-post-id');
            if (confirm('Are you sure you want to delete this draft?')) {
                $.ajax({
                    type: 'POST',
                    data: {image_url: $('#image-url-' + id).val()},
                    dataType: 'json',
                    url: $(elem).attr('href'),
                    cache: false,
                    success: function (response) {
                        if (response.success == false) {
                            alert(response.message);
                            return;
                        }

                        if ($('#draft-posts .post-image').length == 1) {
                            location.reload();
                        } else {
                            // Slide up first because Chrome is weird and shows a ghost duplicate of the next draft.
                            $('#image-' + id).slideUp().remove();

                            $('#upcoming-timeslot-list>li:last').remove();

                            $('.badge-drafts-count').html(parseInt($('.badge-drafts-count').html()) - 1);
                        }
                    }
                });
            }
        },

        createPost: function(event) {
            event.preventDefault();

            var post_wrapper = $(this).parents('.post-image-wrapper');
            if (post_wrapper.attr('id') == 'lg-post-container') {
                post_wrapper = $('#image-' + $('#lg-post-container').attr('data-post-id'));
            }

            var post_data = {
                total: 1,
                id: post_wrapper.attr('data-post-id'),
                parent_pin: post_wrapper.find('.parent-pin-input').val(),
                link: post_wrapper.find('.link-input').val(),
                image_url: post_wrapper.find('.image-input').val(),
                description: post_wrapper.find('.description-input').val(),
                board: post_wrapper.find('.board-input').val(),
                schedule_type: post_wrapper.find('.schedule-type-input').val(),
                date: post_wrapper.find('.date-input').val(),
                hour: post_wrapper.find('.hour-input').val(),
                minute: post_wrapper.find('.minute-input').val(),
                am_pm: post_wrapper.find('.am-pm-input').val()
            };

            $.ajax({
                type: 'POST',
                data: post_data,
                dataType: 'json',
                url: $(this).attr('href'),
                cache: false,
                success: function (response) {
                    if (response.success != true) {
                        alert(response.message + '\n\n' + response.errors.join('\n'));
                        return;
                    }

                    response.post_data = post_data;
                    draftPosts.handleQueuedPost(response);
                }
            });
        },

        handleQueuedPost: function(response) {
            if ($('#draft-posts .post-image-wrapper').length == 1 && response.redirect) {
                location.href = response.redirect;
                return;
            }

            var post = response.post_data;

            postModalView.hide();

            $('#image-' + post.id).remove();

            if (post.schedule_type == 'auto') {
                $('#upcoming-timeslot-list>li:first').remove();
            } else {
                $('#upcoming-timeslot-list>li:last').remove();
            }

            var drafts_count = parseInt($('.badge-drafts-count').html()) - 1;
            $('.badge-drafts-count').html(drafts_count)
                .toggleClass('badge-important', drafts_count > 0);

            if (publisher_admin_user) {
                var scheduled_count = parseInt($('.badge-scheduled-count').html()) + 1;
                $('.badge-scheduled-count').html(scheduled_count)
                    .removeClass('badge-important')
                    .addClass('badge-success');
            } else {
                var pending_count = parseInt($('.badge-pending-count').html()) + 1;
                $('.badge-pending-count').html(pending_count)
                    .toggleClass('badge-important', pending_count > 0);
            }
        },

        validateAllPosts: function() {
            if ($('#draft-posts .board-input:empty').length) {
                alert('Please select a board for all of your posts.');
                return false;
            }

            return true;
        }
    };

    var scheduledPosts = {
        deletePost: function(event) {
            event.preventDefault();

            if (confirm('Are you sure you want to delete this post?')) {
                $.ajax({
                    type: 'GET',
                    dataType: 'json',
                    url: $(this).attr('href'),
                    cache: false,
                    success: function (response) {
                        if (response.success == false) {
                            alert(response.message);
                            return;
                        }

                        location.reload();
                    }
                });
            }
        },

        publishPost: function(event) {
            event.preventDefault();

            var post_wrapper = $(this).parents('.post-image-wrapper, .post-wrapper');
            if (post_wrapper.attr('id') == 'lg-post-container') {
                post_wrapper = $('#image-' + $('#lg-post-container').attr('data-post-id'));
            }

            post_wrapper.append($(
                '<div class="publishing-post-mask">' +
                    '<h4><strong>Publishing Post</strong></h4>' +
                '</div>'
            ));

            $('.publishing-alert, .published-alert').fadeOut().remove();

            $('.navbar-publisher').before($(
                '<div class="alert publishing-alert hidden">' +
                    '<button data-dismiss="alert" class="close">×</button>' +
                    '<h4>Preparing for Takeoff!</h4>' +
                    'Your post will be sent in a moment. We are performing final checks to ensure successful departure.' +
                '</div>'
            ));

            $('.publishing-alert').fadeIn();

            var post_data = {
                id: post_wrapper.attr('data-post-id'),
                link: post_wrapper.find('.link-input').val(),
                description: post_wrapper.find('.description-input').val(),
                board: post_wrapper.find('.board-input').val()
            };

            $.ajax({
                type: 'POST',
                data: post_data,
                dataType: 'json',
                url: $(this).attr('href'),
                cache: false,
                success: function (response) {
                    response.post_data = post_data;
                    scheduledPosts.handlePublishedPost(response);
                }
            });
        },

        updatePost: function(event) {
            event.preventDefault();

            var post_wrapper = $(this).parents('.post-image-wrapper, .post-wrapper');
            if (post_wrapper.attr('id') == 'lg-post-container') {
                post_wrapper = $('#image-' + $('#lg-post-container').attr('data-post-id'));
            }

            var post_data = {
                id: post_wrapper.attr('data-post-id'),
                link: post_wrapper.find('.link-input').val(),
                description: post_wrapper.find('.description-input').val(),
                board: post_wrapper.find('.board-input').val(),
                schedule_type: post_wrapper.find('.schedule-type-input').val(),
                date: post_wrapper.find('.date-input').val(),
                hour: post_wrapper.find('.hour-input').val(),
                minute: post_wrapper.find('.minute-input').val(),
                am_pm: post_wrapper.find('.am-pm-input').val()
            };

            $.ajax({
                type: 'POST',
                data: post_data,
                dataType: 'json',
                url: $(this).attr('href'),
                cache: false,
                success: function (response) {
                    if (response.success != true) {
                        alert(response.message + '\n\n' + response.errors.join('\n'));
                        return;
                    }

                    response.post_data = post_data;
                    scheduledPosts.handleUpdatedPost(response);
                }
            });
        },

        handleUpdatedPost: function(response) {
            // TODO: Display confirmation and update DOM as needed.
            location.reload();
        },

        handlePublishedPost: function(response) {
            $('#post-row-' + response.post_data.id + ' .publishing-post-mask').remove();

            if (response.success) {
                $('#post-row-' + response.post_data.id).slideUp(400, function() {
                    $(this).remove();
                });
            }

            // Update sorted post positions.
            sortablePosts.finishSort(false);

            $('.publishing-alert, .published-alert').fadeOut().remove();

            if (response.success) {
                $('.navbar-publisher').before($(
                    '<div class="alert alert-success published-alert hidden">' +
                        '<button data-dismiss="alert" class="close">×</button>' +
                        '<h4>Success!</h4>' +
                        'Your post has reached its destination.' +
                    '</div>'
                ));
            } else {
                var error_list = '<ul>';
                for (var i = 0; i < response.errors.length; i++) {
                    error_list += '<li>' + response.errors[i] + '</li>';
                }
                error_list += '</ul>';

                $('.navbar-publisher').before($(
                    '<div class="alert alert-error published-alert hidden">' +
                        '<button data-dismiss="alert" class="close">×</button>' +
                        '<h4>Uh oh!</h4>' +
                        '<p>It looks like your post couldn\'t be published.</p>' +
                        error_list +
                    '</div>'
                ));
            }

            $('.published-alert').fadeIn();
        }
    };

    var pendingPosts = {
        deletePost: function(event) {
            event.preventDefault();

            if (confirm('Are you sure you want to delete this post?')) {
                $.ajax({
                    type: 'GET',
                    dataType: 'json',
                    url: $(this).attr('href'),
                    cache: false,
                    success: function (response) {
                        if (response.success == false) {
                            alert(response.message);
                            return;
                        }

                        location.reload();
                    }
                });
            }
        },

        approvePost: function(event) {
            event.preventDefault();

            var post_wrapper = $(this).parents('.post-image-wrapper, .post-wrapper');
            if (post_wrapper.attr('id') == 'lg-post-container') {
                post_wrapper = $('#image-' + $('#lg-post-container').attr('data-post-id'));
            }

            var post_data = {
                id: post_wrapper.attr('data-post-id'),
                link: post_wrapper.find('.link-input').val(),
                description: post_wrapper.find('.description-input').val(),
                board: post_wrapper.find('.board-input').val(),
                schedule_type: post_wrapper.find('.schedule-type-input').val(),
                date: post_wrapper.find('.date-input').val(),
                hour: post_wrapper.find('.hour-input').val(),
                minute: post_wrapper.find('.minute-input').val(),
                am_pm: post_wrapper.find('.am-pm-input').val()
            };

            $.ajax({
                type: 'POST',
                data: post_data,
                dataType: 'json',
                url: $(this).attr('href'),
                cache: false,
                success: function (response) {
                    if (response.success != true) {
                        alert(response.message + '\n\n' + response.errors.join('\n'));
                        return;
                    }

                    response.post_data = post_data;
                    pendingPosts.handleApprovedPost(response);
                }
            });
        },

        handleApprovedPost: function(response) {
            if ($('#pending-posts .post-image-wrapper, #pending-posts .post-wrapper').length == 1 && response.redirect) {
                location.href = response.redirect;
                return;
            }

            var post = response.post_data;

            postModalView.hide();

            $('#image-' + post.id).remove();

            var pending_count = parseInt($('.badge-pending-count').html()) - 1;
            var scheduled_count = parseInt($('.badge-scheduled-count').html()) + 1;

            $('.badge-pending-count').html(pending_count)
                .toggleClass('badge-important', pending_count > 0);
            $('.badge-scheduled-count').html(scheduled_count)
                .removeClass('badge-important')
                .addClass('badge-success');
        },

        approveAll: function(event) {
            event.preventDefault();

            var i = 1;
            var post_data = {
                total: 0,
                id: [],
                link: [],
                description: [],
                board: [],
                schedule_type: [],
                date: [],
                hour: [],
                minute: [],
                am_pm: []
            };

            $('#pending-posts .post-image-wrapper, #pending-posts .post-wrapper').each(function() {
                post_data.id[i] = $(this).attr('data-post-id');
                post_data.link[i] = $(this).find('.link-input').val();
                post_data.description[i] = $(this).find('.description-input').val();
                post_data.board[i] = $(this).find('.board-input').val();
                post_data.schedule_type[i] = $(this).find('.schedule-type-input').val();
                post_data.date[i] = $(this).find('.date-input').val();
                post_data.hour[i] = $(this).find('.hour-input').val();
                post_data.minute[i] = $(this).find('.minute-input').val();
                post_data.am_pm[i] = $(this).find('.am-pm-input').val();

                post_data.total++;
                i++;
            });

            $.ajax({
                type: 'POST',
                data: post_data,
                dataType: 'json',
                url: $(this).attr('href'),
                cache: false,
                success: function (response) {
                    if (response.success != true) {
                        alert(response.message + '\n\n' + response.errors.join('\n'));
                        return;
                    }

                    if (response.redirect) {
                        location.href = response.redirect;
                    }
                }
            });
        }
    };

    var sortablePosts = {
        timeslots: [
            <? $num_queued_timeslots = count($queued_timeslots) ?>
            <?php for ($i = 0; $i < $num_queued_timeslots; $i++): ?>
                <? $carbon_date = \Carbon\Carbon::createFromTimestamp($queued_timeslots[$i], $user_timezone); ?>
                {
                    timestamp: <?= $queued_timeslots[$i] ?>,
                    label: '<?= $carbon_date->format('F j, Y @ g:i A (T)') ?>',
                    date: '<?= $carbon_date->format('Y-m-d') ?>',
                    date_placeholder: '<?= $carbon_date->format('F j, Y') ?>',
                    hour: '<?= $carbon_date->format('g') ?>',
                    minute: '<?= $carbon_date->format('i') ?>',
                    am_pm: '<?= $carbon_date->format('A') ?>'
                }<?= ($i < $num_queued_timeslots - 1) ? ',' : '' ?>
            <?php endfor ?>
        ],

        end_of_today: <?= \Carbon\Carbon::today($user_timezone)->endOfDay()->timestamp ?>,

        end_of_tomorrow: <?= \Carbon\Carbon::tomorrow($user_timezone)->endOfDay()->timestamp ?>,

        end_of_week: <?= \Carbon\Carbon::today($user_timezone)->endOfWeek()->timestamp ?>,

        end_of_next_week: <?= \Carbon\Carbon::today($user_timezone)->addDays(7)->endOfWeek()->timestamp ?>,

        init: function() {
            $('#scheduled-posts-list').sortable({
                cursor: 'move',
                items: '.auto-post-list-item',
                placeholder: 'sortable-post-placeholder',
                forcePlaceholderSize: true,
                start: sortablePosts.startHandler,
                sort: sortablePosts.sortHandler,
                stop: sortablePosts.finishSort
            });

            $('.auto-post-list-item').on('mouseenter', function() {
                $(this).find('.icon-move').removeClass('hidden');
            });

            $('.auto-post-list-item').on('mouseleave', function() {
                $(this).find('.icon-move').addClass('hidden');
            });

            $('.btn-move-top:first').hide();
            // $('.btn-move-bottom:last').hide();

            $('.btn-move-top').on('click', function() {
                $(this).parents('.posts-list-row').detach().insertBefore('.posts-list-row:first');
                sortablePosts.finishSort(true);
            });

            /*
            $('.btn-move-bottom').on('click', function() {
                $(this).parents('.posts-list-row').detach().insertAfter('.posts-list-row:last');
                sortablePosts.finishSort(true);
            });
            */
        },

        startHandler: function() {
            $('.manual-post-list-item .post-wrapper').append($(
                '<div class="manual-post-mask">' +
                    '<h4><i class="icon-clock"> <strong>Manually Scheduled</strong></h4>' +
                '</div>'
            ));
            $('.manual-post-mask').each(function() {
                $(this).height($(this).parents('.manual-post-item').height());
            });
        },

        sortHandler: function(event, ui) {
            var index = Number($('.auto-post-list-item, .sortable-post-placeholder').not(ui.item).index(ui.placeholder));
            var timeslot = sortablePosts.timeslots[index];
            $(ui.placeholder).html(
                '<div class="post-wrapper"><h4><strong>' + timeslot.label + '</strong></h4></div>'
            );
        },

        finishSort: function(show_message) {
            $('.manual-post-mask').remove();

            var timeslot;

            // Update each post's date/time information.
            $.each($('.auto-post-list-item'), function(index, element) {
                timeslot = sortablePosts.timeslots[index];

                $(element).attr('data-timestamp', timeslot.timestamp);

                $(element).find('.label-auto-queue span').text(timeslot.label);
                $(element).find('.date-placeholder').text(timeslot.date_placeholder);
                $(element).find('.hour-placeholder').text(timeslot.hour);
                $(element).find('.minute-placeholder').text(timeslot.minute);
                $(element).find('.am-pm-placeholder').text(timeslot.am_pm);
                $(element).find('.date-input').val(timeslot.date);
                $(element).find('.hour-input').val(timeslot.hour);
                $(element).find('.minute-input').val(timeslot.minute);
                $(element).find('.am-pm-input').val(timeslot.am_pm);
            });

            // Make sure all posts are in the right date group list.
            $('.posts-list-row').each(function() {
                timestamp = parseInt($(this).attr('data-timestamp'));

                if (timestamp < sortablePosts.end_of_today) {
                    if ($(this).parents('#today-posts').length == 0) {
                        $(this).appendTo('#today-posts');
                    }
                } else if (timestamp < sortablePosts.end_of_tomorrow) {
                    if ($(this).parents('#tomorrow-posts').length == 0) {
                        $(this).appendTo('#tomorrow-posts');
                    }
                } else if (timestamp < sortablePosts.end_of_week) {
                    if ($(this).parents('#this-week-posts').length == 0) {
                        $(this).appendTo('#this-week-posts');
                    }
                } else if (timestamp < sortablePosts.end_of_next_week) {
                    if ($(this).parents('#next-week-posts').length == 0) {
                        $(this).appendTo('#next-week-posts');
                    }
                } else if ($(this).parents('#later-posts').length == 0) {
                    $(this).appendTo('#later-posts');
                }
            });

            // Sort each date group list's posts.
            var post_rows, timestamp_a, timestamp_b;
            $('.post-group').each(function() {
                post_rows = $(this).find('.posts-list-row');
                post_rows.sort(function(a, b){
                    timestamp_a = a.getAttribute('data-timestamp');
                    timestamp_b = b.getAttribute('data-timestamp');

                    if (timestamp_a > timestamp_b) {
                        return 1;
                    }

                    if (timestamp_a < timestamp_b) {
                        return -1;
                    }

                    return 0;
                });

                post_rows.detach().appendTo($(this));
            });

            $('.btn-move-top:hidden').show();
            // $('.btn-move-bottom:hidden').show();

            $('.btn-move-top:first').hide();
            // $('.btn-move-bottom:last').hide();

            sortablePosts.saveOrder(show_message);
        },

        saveOrder: function(show_message) {
            $.ajax({
                type: 'POST',
                data: $('#scheduled-posts-list').sortable('serialize', {key: 'post_ids[]'}),
                dataType: 'json',
                url: '<?= URL::route('publisher-order-posts') ?>',
                cache: false,
                success: function (response) {
                    if (response.message && show_message) {
                        $('.reorder-alert').fadeOut().remove();

                        $('.navbar-publisher').before($(
                            '<div class="alert alert-success reorder-alert hidden">' +
                                '<button data-dismiss="alert" class="close">×</button>' +
                                response.message +
                            '</div>'
                        ));

                        $('.reorder-alert').fadeIn();
                    }
                }
            });
        }
    };
</script>

<?php if ($page == 'publisher-posts-published'): ?>

    <script type="text/javascript">
        $(function() {
            $('.pinned-post').on('click', function(event) {
                if ($(event.target).prop('tagName') == 'A') {
                    return;
                }

                window.open($(this).attr('data-pin-url'));
            });
        });
    </script>

<?php elseif ($page == 'publisher-posts-scheduled' && $admin_user): ?>

    <script type="text/javascript">
        $(function() {
            if ($('.post-image-wrapper, .post-wrapper').length == 0) {
                return;
            }

            postsCalendar.init();
            postModalView.init();
            postFields.init();
            sortablePosts.init();

            $('.btn-publish-now').on('click', scheduledPosts.publishPost);

            $('.btn-update-post').on('click', scheduledPosts.updatePost);

            $('.delete-post-btn').on('click', scheduledPosts.deletePost);
        });
    </script>

<?php elseif ($page == 'publisher-posts-pending' && $admin_user): ?>

    <script type="text/javascript">
        $(function() {
            if ($('.post-image-wrapper, .post-wrapper').length == 0) {
                return;
            }

            postsCalendar.init();
            postModalView.init();
            postFields.init();

            $('.btn-approve-post').on('click', pendingPosts.approvePost);
            $('.btn-approve-all').on('click', pendingPosts.approveAll);
            $('.delete-post-btn').on('click', pendingPosts.deletePost);
        });
    </script>

<?php elseif ($page == 'publisher-posts-drafts'): ?>

    <script type="text/javascript">
        $(function() {
            if ($('.post-image-wrapper, .post-wrapper').length == 0) {
                return;
            }

            postsCalendar.init();
            postModalView.init();
            postFields.init();

            $('#global-board').tagsinput({
                typeahead: {
                    source: boards,
                    freeInput: false
                },
                itemValue: 'id',
                itemText: 'name',
                confirmKeys: [13, 44],
                tagClass: function(item) {
                    return (item.collaborator ? 'label label-success label-collaborator-board' : 'label label-info');
                }
            });

            $('#global-board').on('itemAdded', function(event) {
                $(".post-image-wrapper select[id|='board']").each(function(){
                    $(this).tagsinput('add', event.item);
                });
            });

            $('#global-board').on('itemRemoved', function(event) {
                $(".post-image-wrapper select[id|='board']").each(function(){
                    $(this).tagsinput('remove', event.item);
                });
            });

            $('#all-drafts-form').on('submit', draftPosts.validateAllPosts);

            $('.btn-queue-post, .btn-schedule-post').on('click', draftPosts.createPost);

            $('.delete-post-btn[data-post-id]').on('click', function(event) {
                event.preventDefault();
                draftPosts.delete($(this));
            });
        });
    </script>

<?php elseif ($page == 'publisher-permissions'): ?>

    <script type="text/javascript">
        $(function() {
            $('.publisher-role-switch').bootstrapSwitch({
                onText: 'Admin',
                offText: 'Draft Editor',
                onColor: 'info'
            });
        });
    </script>

<?php endif ?>