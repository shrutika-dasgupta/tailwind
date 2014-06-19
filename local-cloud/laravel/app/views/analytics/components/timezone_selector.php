<div class="control-group" id="<?= $id ?>-control-group">
    <input type="text"
           name="<?= $name ?>"
           id="<?= $id ?>"
           class="<?= $class ?>"
           value="<?= $display_value ?>"
           placeholder="Type a city"
    >

    <input type="hidden" name="<?= $name ?>-timezone-hidden" id="<?= $id ?>-timezone-hidden" value="<?= $timezone ?>">
    <input type="hidden" name="<?= $name ?>-city-hidden" id="<?= $id ?>-city-hidden" value="<?= $city ?>">
    <input type="hidden" name="<?= $name ?>-region-hidden" id="<?= $id ?>-region-hidden" value="<?= $region ?>">
    <input type="hidden" name="<?= $name ?>-country-hidden" id="<?= $id ?>-country-hidden" value="<?= $country ?>">

    <div id='<?= $id ?>-help-block' class="help-block hidden"></div>

    <?php if ($local_time): ?>

        <p id="<?= $id ?>-local-time" class="muted">Local time: <span><?= $local_time ?></span></p>

    <?php endif ?>
</div>

<script type="text/javascript">
$(function() {
    var selector_id = '<?= $id ?>';

    $('#' + selector_id).on('focus', function () {
        $(this).prop('value', '');

        $('#' + selector_id + '-control-group')
            .removeClass('error')
            .removeClass('success');
        $('#' + selector_id + '-help-block').addClass('hidden');
        $('#' + selector_id + '-local-time').addClass('hidden');
    });

    $('#' + selector_id).autocomplete({
        source: function (request, response) {
            $.getJSON(
                'http://gd.geobytes.com/AutoCompleteCity?callback=?&q=' + request.term,
                function (data) {
                    response(data);
                }
            );
        },
        minLength: 3,
        select: function (event, ui) {
            var selectedObj = ui.item;

            $.ajax({
                type: 'GET',
                data: {
                    city: selectedObj.value,
                    return_current_time: true
                },
                dataType: 'json',
                url: '<?= URL::route('geo-city-timezone') ?>',
                cache: false,
                success: handleCityResult,
                error: handleCityError
            });

            return false;
        }
    });
    $('#' + selector_id).autocomplete('option', 'delay', 100);

    function handleCityResult(result)
    {
        if (result.success != true) {
            handleCityError(result);
            return;
        }

        $('#' + selector_id).val(result.city_formatted);
        $('#' + selector_id + '-timezone-hidden').val(result.timezone);
        $('#' + selector_id + '-city-hidden').val(result.city);
        $('#' + selector_id + '-region-hidden').val(result.region);
        $('#' + selector_id + '-country-hidden').val(result.country);
        $('#' + selector_id + '-local-time > span').html(result.current_time);

        $('#' + selector_id + '-local-time').removeClass('hidden');

        $('#' + selector_id).blur();

        <?php if ($success_callback): ?>
            $.Callbacks().add(<?= $success_callback ?>).fire(result).remove(<?= $success_callback ?>);
        <?php endif ?>
    }

    function handleCityError(result)
    {
        $('#' + selector_id + '-local-time').addClass('hidden');
        $('#' + selector_id + '-control-group').addClass('error');
        $('#' + selector_id + '-help-block')
            .html('Please enter a valid city.')
            .removeClass('hidden');

        <?php if ($error_callback): ?>
            $.Callbacks().add(<?= $error_callback ?>).fire(result).remove(<?= $error_callback ?>);
        <?php endif ?>
    }
});
</script>