<?= $navigation ?>

<div class="row-fluid" id="publisher-schedule">
    <div class="span8">
        <h3>
            Scheduled Times

            <a class="publisher-schedule-help"
               data-toggle="popover"
               data-container="body"
               data-placement="bottom"
               data-original-title="&lt;strong&gt;Your Schedule&lt;/strong&gt;"
               data-content="
                       &lt;p&gt;
                           Your schedule is a list of times that you want posts to publish
                           automatically. Any posts in your queue that do not have a custom date and
                           time will be sent according to this schedule. Only one post will be sent
                           at each time slot.
                       &lt;/p&gt;
                   "
                >
                <i class="icon-help"></i>
            </a>
        </h3>

        <?php if ($admin_user): ?>
            <form id="new-timeslot-form" class="form-inline hidden" action="<?= URL::route('api-publisher-create-time-slot') ?>" method="POST">
                <select id="new-timeslot-day" name="day" class="input-small">
                    <?php foreach ($days as $key => $day): ?>
                        <option value="<?= $key ?>">
                            <?= $day ?>
                        </option>
                    <?php endforeach ?>
                </select>
                at
                <select id="new-timeslot-hour" name="hour" class="input-mini">
                    <?php foreach ($hours as $hour): ?>
                        <option value="<?= $hour ?>"><?= $hour ?></option>
                    <?php endforeach ?>
                </select>

                <select id="new-timeslot-minute" name="minute" class="input-mini">
                    <?php foreach ($minutes as $minute): ?>
                        <option value="<?= sprintf('%02d', $minute) ?>">
                            <?= sprintf('%02d', $minute) ?>
                        </option>
                    <?php endforeach ?>
                </select>

                <select id="new-timeslot-meridian" name="meridian" class="input-mini">
                    <option value="AM">AM</option>
                    <option value="PM">PM</option>
                </select>

                <input id="save-timeslot-btn" class="btn btn-info" type="submit" value="Save" />
            </form>

            <button id="new-timeslot-btn" class="btn btn-info" type="button">Schedule New Time</button>
        <?php endif ?>

        <table id="timeslots" class="table table-striped">
            <tbody>
                <?php if (empty($timeslots)): ?>
                    <tr id="no-timeslots-row"><td>No times have been scheduled yet.</td></tr>
                <?php endif ?>

                <tr id="timeslot-blank-row"
                    class="hidden"
                    data-day-preference="0"
                    data-time-preference="0"
                >
                    <td>
                        <a class    = "pull-right hidden timeslot-delete"
                           href     = "javascript:void(0)"
                           data-url = "<?= URL::route('api-publisher-delete-time-slot', array(0)) ?>"
                        ><i class="icon-cancel"></i></a>
                        <span></span>
                    </td>
                </tr>

                <?php foreach ($timeslots as $timeslot): ?>
                    <tr class="timeslot-row"
                        data-day-preference="<?= $timeslot->day_preference ?>"
                        data-time-preference="<?= $timeslot->time_preference ?>"
                    >
                        <td>
                            <?php if ($admin_user): ?>
                                <a class    = "pull-right hidden timeslot-delete"
                                   href     = "javascript:void(0)"
                                   data-url = "<?= URL::route('api-publisher-delete-time-slot', array($timeslot->id)) ?>"
                                ><i class="icon-cancel"></i></a>
                            <?php endif ?>
                            <span>
                                <?= $timeslot->getPrettyDay() ?>
                                at
                                <?= \Carbon\Carbon::createFromTimestamp(
                                        strtotime($timeslot->getPrettyTime() . ' ' . $timeslot->timezone),
                                        $user_timezone
                                    )->format('g:i A');
                                ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>

    <div class="span4">
        <div class="well">
            <h4>Your Timezone</h4>
            <?= $timezone_selector ?>
        </div>

        <?php if ($admin_user): ?>
            <div class="well">
                <h4>
                    Suggested Post Times
                    <a class="publisher-schedule-help"
                       data-toggle="popover"
                       data-container="body"
                       data-placement="left"
                       data-original-title="&lt;strong&gt;Suggested Post Times&lt;/strong&gt;"
                       data-content="
                           &lt;p&gt;
                               Below are some suggested time slots based on when your pins receive the
                               most engagement.
                           &lt;/p&gt;
                       "
                        >
                        <i class="icon-help"></i>
                    </a>
                </h4>

                <ul class="unstyled suggested-times-list">
                    <?php foreach ($suggested_times as $suggested_time): ?>
                        <li>
                            <a href="javascript:void(0);"
                               class="btn btn-mini btn-success btn-add-suggested-time"
                               title="Add to Schedule"
                               data-toggle="tooltip"
                               data-placement="left"
                               data-day="<?= $suggested_time['day'] ?>"
                               data-time="<?= $suggested_time['time'] ?>"
                               data-pretty-day="<?= $suggested_time['pretty_day'] ?>"
                               data-pretty-time="<?= $suggested_time['pretty_time'] ?>"
                            >
                                <i class="icon-plus"></i>
                            </a>
                            <strong><?= $suggested_time['pretty_day'] ?> at <?= $suggested_time['pretty_time'] ?></strong>
                            <small>(<?= $suggested_time['repins_per_pin'] ?> repins/pin)</small>
                        </li>
                    <?php endforeach ?>
                </ul>
            </div>
        <?php endif ?>

        <div class="well">
            <h4>
                Your Weekly Time Slots
                <span class="badge <?= (count($timeslots) > 0) ? 'badge-info' : '' ?> badge-weekly-count">
                    <?= count($timeslots) ?>
                </span>
            </h4>

            <ul class="unstyled daily-timeslot-list">
                <?php foreach ($daily_counts as $day => $count): ?>
                    <li>
                        <span class="badge <?= ($count > 0) ? 'badge-info' : '' ?> badge-daily-count badge-<?= strtolower($day) ?>-count">
                            <?= $count ?>
                        </span>
                        <span><?= $day ?></span>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    </div>
</div>

<script type="text/javascript">
var publisherOptionsDays = <?= json_encode($days) ?>;

$(function() {
    setTimeSlotDefaults();

    // Show/hide delete icon on timeslot row hover.
    $(document).on({
        mouseenter: function () {
            $(this).find('a.timeslot-delete').show();
        },
        mouseleave: function () {
            $(this).find('a.timeslot-delete').hide();
        }
    }, '#timeslots tr');

    $('#timeslots a.timeslot-delete').on('click', function () {
        if (confirm('Are you sure you want to delete this schedule?')) {
            element = $(this);

            var displayDay = $('#new-timeslot-day option[value="' + element.parents('tr').attr('data-day-preference') + '"]').text().trim().toLowerCase();

            $('.badge-weekly-count').html(parseInt($('.badge-weekly-count').html()) -1);
            $('.badge-' + displayDay + '-count').html(parseInt($('.badge-' + displayDay + '-count').html()) - 1);

            $('.badge-weekly-count').toggleClass('badge-info', parseInt($('.badge-weekly-count').html()) > 0);
            $('.badge-' + displayDay + '-count').toggleClass('badge-info', parseInt($('.badge-' + displayDay + '-count').html()) > 0);

            element.parents('tr').remove();

            $.ajax({
                url: element.data('url'),
                cache: false
            });
        }
    });

    $('#new-timeslot-btn').on('click', function() {
        $(this).fadeOut(300, function() {
            $('#new-timeslot-form').slideDown();
        });
    });

    $('#new-timeslot-form').on('submit', function(event) {
        event.preventDefault();

        $('#new-timeslot-form').slideUp();

        var days = $("#new-timeslot-form input:checkbox:checked").map(function() {
            return $(this).val();
        }).get();

        var day      = $('#new-timeslot-day').val();
        var hour     = $('#new-timeslot-hour').val();
        var minute   = $('#new-timeslot-minute').val();
        var meridian = $('#new-timeslot-meridian').val().toUpperCase();

        var displayDay  = publisherOptionsDays[day];
        var displayTime = hour + ':' + minute + ' ' + meridian;

        hour = parseInt(hour);
        if (meridian == 'PM' && hour != 12) {
            hour += 12;
        } else if (meridian == 'AM' && hour == 12) {
            hour = '00';
        }

        var time = (hour.toString().length == 1 ? '0' : '') + hour + minute;

        addTimeSlot(day, time, displayDay, displayTime);
    });

    $('.btn-add-suggested-time').on('click', function(event) {
        event.preventDefault();

        addTimeSlot(
            $(this).attr('data-day'),
            $(this).attr('data-time'),
            $(this).attr('data-pretty-day'),
            $(this).attr('data-pretty-time')
        );
    });

    function addTimeSlot(day, time, displayDay, displayTime) {
        $.ajax({
            type: 'POST',
            data: {
                day: day,
                time: time,
            },
            dataType: 'json',
            url: $('#new-timeslot-form').attr('action'),
            cache: false,
            success: function (response) {
                if (response.success) {
                    handleTimeSlotCreated(response.id, day, time, displayDay, displayTime);
                }
            }
        });
    }

    /**
     * Handles front-end interactions when a new time slot is created.
     *
     * @param string day
     * @param string time
     *
     * @return void
     */
    function handleTimeSlotCreated(id, day, time, displayDay, displayTime)
    {
        setTimeSlotDefaults();

        // Ensure that the empty result messaging is removed.
        $('#no-timeslots-row').remove();

        var new_row = $('#timeslot-blank-row').clone(true);
        new_row.attr('id', '')
            .addClass('timeslot-row')
            .attr('data-day-preference', day)
            .attr('data-time-preference', time);

        $('a.timeslot-delete', new_row).attr(
            'data-url',
            $('a.timeslot-delete', new_row).attr('data-url').replace('0', id)
        );

        $('span', new_row).html(displayDay + ' at ' + displayTime);

        var next_timeslot;
        $('.timeslot-row').each(function() {
            if (parseInt($(this).attr('data-day-preference')) * 10000 + parseInt($(this).attr('data-time-preference')) >= parseInt(day) * 10000 + parseInt(time)) {
                next_timeslot = $(this);
                return false;
            }
        });

        if (next_timeslot) {
            new_row.insertBefore(next_timeslot);
        } else {
            new_row.appendTo('#timeslots tbody');
        }

        new_row.show('highlight', 1000);
        $('#new-timeslot-btn').slideDown();

        $('.badge-weekly-count').html(parseInt($('.badge-weekly-count').html()) + 1);
        $('.badge-' + displayDay.toLowerCase() + '-count').html(parseInt($('.badge-' + displayDay.toLowerCase() + '-count').html()) + 1);

        $('.badge-weekly-count').toggleClass('badge-info', parseInt($('.badge-weekly-count').html()) > 0);
        $('.badge-' + displayDay.toLowerCase() + '-count').toggleClass('badge-info', parseInt($('.badge-' + displayDay.toLowerCase() + '-count').html()) > 0);
    }

    /**
     * Sets the defaults for the time fields.
     */
    function setTimeSlotDefaults()
    {
        $('#new-timeslot-day').val('1');
        $('#new-timeslot-hour').val('12');
        $('#new-timeslot-minute').val('00');
        $('#new-timeslot-meridian').val('PM');
    }
});

var TimezoneHandler = {

    updateLocation : function(result)
    {
        $.ajax({
            type: 'POST',
            data: {
                timezone: result.timezone,
                city: result.city,
                region: result.region,
                country: result.country
            },
            dataType: 'json',
            url: '<?= URL::route('settings-profile-update-location') ?>',
            cache: false,
            success: TimezoneHandler.handleUpdateSuccess,
            error: TimezoneHandler.handleUpdateError
        });
    },

    handleUpdateSuccess : function(result)
    {
        if (result.success != true) {
            self.handleUpdateError(result);
            return;
        }

        $('#account-timezone-control-group').addClass('success');
        $('#account-timezone-help-block')
            .html('Your timezone has been updated!')
            .removeClass('hidden');

        location.reload();
    },

    handleUpdateError : function(result)
    {
        $('#account-timezone-local-time').addClass('hidden');
        $('#account-timezone-control-group').addClass('error');
        $('#account-timezone-help-block')
            .html('Error updating timezone.')
            .removeClass('hidden');
    }
}
</script>
