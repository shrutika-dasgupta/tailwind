<script type="text/javascript" src="/js/discover-bootstrap-tagsinput.js"></script>

<script type="text/javascript">
$(document).ready(function () {
    $('.save-button').click(function(){
        alert('Saving pins for later is still in the works - stay tuned!');
    });

    // Track clicks on pin source link.
    $('a:regex(class,pin-source-link-.)').click(function(){
        var view = $(this).attr('class').substring($(this).attr('class').lastIndexOf("-")+1);
        analytics.track('Click', {'Via':'Listening', 'Target':'Pin Source Link', 'View':view});
    });

    // Track clicks on pin image link.
    $('a:regex(class,pin-image-.)').click(function(){
        var view = $(this).attr('class').substring($(this).attr('class').lastIndexOf("-")+1);
        analytics.track('Click', {'Via':'Listening', 'Target':'Pin Image Link', 'View':view});
    });

    // Track clicks on pinner profile.
    $('a:regex(class,pinner-profile-.)').click(function(){
        var view = $(this).attr('class').substring($(this).attr('class').lastIndexOf("-")+1);
        analytics.track('Click', {'Via':'Listening', 'Target':'Pinner Profile Link', 'View':view});
    });

    // Track clicks on pinner Facebook link.
    $('a:regex(class,pinner-fb-.)').click(function(){
        var view = $(this).attr('class').substring($(this).attr('class').lastIndexOf("-")+1);
        analytics.track('Click', {'Via':'Listening', 'Target':'Pinner Facebook Link', 'View':view});
    });

    // Track clicks on pinner Twitter Follow button.
    $('a:regex(class,pinner-tw-.)').click(function(){
        var view = $(this).attr('class').substring($(this).attr('class').lastIndexOf("-")+1);
        analytics.track('Click', {'Via':'Listening', 'Target':'Pinner Twitter Follow Button', 'View':view});
    });

    // Track clicks on pinner Pinterest Follow button.
    $('a:regex(class,pinner-pn-.)').click(function(){
        var view = $(this).attr('class').substring($(this).attr('class').lastIndexOf("-")+1);
        analytics.track('Click', {'Via':'Listening', 'Target':'Pinner Pinterest Follow Button', 'View':view});
    });

    // Track clicks on word cloud.
    $('.wordcloud-wrapper').on('click', '.wordcloud-word a', function () {
        addTopic($(this).parent().data('word'), {
            'event_data': {
                'view': appView,
                'component': 'Word Cloud'
            }
        });
    });

    // Track inline click interactions.
    $('body').on('click', '.track-click', function () {
        element = $(this);
        if (!element.data('component') || !element.data('element')) {
            return;
        }

        $.ajax({
            type: 'POST',
            url: '<?= URL::route("api-record-event") ?>',
            data: {
                event: 'Discover Click',
                data: {
                    view: appView,
                    component: element.data('component'),
                    element: element.data('element')
                }
            },
            cache: false
        });
    });
});

var appView = "Discover <?= $page ? Str::title($page) : '' ?>";

/**
 * Displays a flash message.
 *
 * @todo Move this to core.
 *
 * @param string   msg
 * @param string   type
 * @param int|bool timeout
 *
 * @return void
 */
function flashMsg(msg, type, timeout)
{
    if (!type) {
        type = 'success';
    }

    // Remove any existing listening alert.
    $('#listening-alert').remove();

    alert = '<div id="listening-alert" class="alert alert-' + type + ' hidden">' +
                '<button data-dismiss="alert" class="close" type="button">&times;</button>' +
                '<strong>' + msg + '</strong>' +
            '</div>';

    $(alert).appendTo($('#main-top-toolbar')).fadeIn(400, function () {
        $(this).addClass('fade in');
    });

    if (timeout === undefined) {
        timeout = 5000;
    }

    if (timeout) {
        setTimeout(function () {
            $('#listening-alert').fadeOut(400, function () { $(this).remove() });
        }, timeout);
    }
}

/**
 * Adds a topic to the active account.
 *
 * @param string topic
 * @param object data     (optional)
 * @param mixed  callback (optional)
 * 
 * @return void
 */
function addTopic(topic, data, callback)
{
    $.ajax({
        type: 'POST',
        url: '<?= URL::route("discover-add-topic") ?>',
        data: {topic: topic, data: data},
        topic: topic,
        cache: false,
        success: function (response) {
            if (typeof callback == 'function') {
                callback(response);
            }

            if (response.success) {
                (loadTopicBar || Function)();

                updateAccountTopicUsage('add', response.topic_type);

                flashMsg('You\'re now following "' + this.topic + '". Pin data for this topic will be available within a few minutes.');
            } else {
                if (response.code) {
                    if (response.code == 1000) {
                        type = 'domain';
                    } else if (response.code == 2000) {
                        type = 'keyword'
                    }

                    flashMsg(
                        'You\'ve reached your ' + type + ' limit. '
                        + '<a href="javascript:void(0)" class="manage-' + type + '-topics">Manage your ' + type + 's</a> '
                        + 'or <a href="/upgrade">upgrade your account</a>.',
                        'error',
                        false
                    );
                } else {
                    flashMsg('Sorry, an error occurred. Please try again.', 'error');
                }
            }
        }
    });
}

/**
 * Removes a topic from the active account.
 *
 * @param string topic
 * @param object data  (optional)
 *
 * @return void
 */
function removeTopic(topic, data)
{
    $.ajax({
        type: 'DELETE',
        url: '<?= URL::route("discover-remove-topic") ?>/' + encodeURIComponent(topic),
        data: {data: data},
        cache: false,
        success: function (response) {
            if (response.success) {
                (loadTopicBar || Function)();

                updateAccountTopicUsage('remove', response.topic_type);
            }
        }
    });
}

// Init Pinterest social buttons.
(function(d){
    var f = d.getElementsByTagName('SCRIPT')[0], p = d.createElement('SCRIPT');
    p.type = 'text/javascript';
    p.async = true;
    p.src = '//assets.pinterest.com/js/pinit.js';
    f.parentNode.insertBefore(p, f);
}(document));

// Init Twitter social buttons.
!function(d,s,id){
    var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';
    if(!d.getElementById(id)){
        js=d.createElement(s);js.id=id;
        js.src=p+'://platform.twitter.com/widgets.js';
        fjs.parentNode.insertBefore(js,fjs);
    }
}(document, 'script', 'twitter-wjs');
</script>
