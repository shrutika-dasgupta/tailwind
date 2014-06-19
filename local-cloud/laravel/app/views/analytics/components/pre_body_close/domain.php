<script type="text/javascript" src="/js/bootstrap-tagsinput-domain.js"></script>

<script type="text/javascript">
$(document).ready(function () {

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

    //Dashboard / Chart toggles
    $("#site-pins-toggle-dash").on('click', function () {
        $("#pins-toggle2").trigger('click');
        $("#pins-toggle2").trigger('click');
        $("#pins-toggle").trigger('click');
        $("#pins-toggle").trigger('click');
        $("#site-pins-toggle-dash").addClass('active');
        $("#pinners-toggle-dash").removeClass('active');
        $("#reach-toggle-dash").removeClass('active');
        analytics.track('Clicked on pins box on domain page');
    });
    $("#pinners-toggle-dash").on('click', function () {
        $("#pinners-toggle2").trigger('click');
        $("#pinners-toggle2").trigger('click');
        $("#pinners-toggle").trigger('click');
        $("#pinners-toggle").trigger('click');
        $("#pinners-toggle-dash").addClass('active');
        $("#site-pins-toggle-dash").removeClass('active');
        $("#reach-toggle-dash").removeClass('active');
        analytics.track('Clicked on pinners box on domain page');
    });
    $("#reach-toggle-dash").on('click', function () {
        $("#reach-toggle2").trigger('click');
        $("#reach-toggle2").trigger('click');
        $("#reach-toggle").trigger('click');
        $("#reach-toggle").trigger('click');
        $("#site-pins-toggle-dash").removeClass('active');
        $("#pinners-toggle-dash").removeClass('active');
        $("#reach-toggle-dash").addClass('active');
        analytics.track('Clicked on reach box on domain page');
    });

    $('.expand-comments').on('click', function() {
        $(this).parent().prev('div.comments-middle-container').removeClass('collapsed');
        $(this).parent().prevAll('div.collapse-comments:first').removeClass('hidden');
        $(this).parent().hide();
    });

    $('.expand-comments').hover(
        function() {
            $(this).parent().prev('div.comments-middle-container').animate({
                height: "230px"
            }, 300);
        },
        function() {
            if($(this).parent().prev('div.comments-middle-container').hasClass('collapsed')){
                $(this).parent().prev('div.comments-middle-container').animate({
                    height: "200px"
                }, 100);
            }
        }
    );

    $('.collapse-comments').on('click', function() {
        $(this).next('div.comments-middle-container').addClass('collapsed');
        $(this).nextAll('div.fadeout-bottom:first').show();
        $(this).addClass('hidden');
    });

    $('.value-metric').fitText(0.5, { minFontSize: '25px', maxFontSize: '70px' });



    var trendingImagesWidth = $('.module-trending-images').width();
    var influencersWidth    = $('.module-influencers').width();
    var hashtagsWidth       = $('.module-hashtags').width();

    $('.module-trending-images .sticky-module-title').css('width',trendingImagesWidth-2);
    $('.module-influencers .sticky-module-title').css('width',influencersWidth-2);
    $('.module-hashtags .sticky-module-title').css('width',hashtagsWidth-2);


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
                event: 'Domain Click',
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

var appView = "Domain <?= $page ? Str::title($page) : '' ?>";

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
        url: '<?= URL::route("domain-add-topic") ?>',
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
        url: '<?= URL::route("domain-remove-topic") ?>/' + encodeURIComponent(topic),
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
$('.pinner-connect button').click(function(){
    !function(d,s,id){
        var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';
        if(!d.getElementById(id)){
            js=d.createElement(s);js.id=id;
            js.src=p+'://platform.twitter.com/widgets.js';
            fjs.parentNode.insertBefore(js,fjs);
        }
    }(document, 'script', 'twitter-wjs');
});

</script>
