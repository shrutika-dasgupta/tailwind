<?php
// Feature ACL.
$query_topics_max = $customer->maxAllowed('listening_query_topics_max');
?>

<div id="topic-bar" class="row-fluid hidden">
    <div class="span12">
        <div id="tags-dropdown" class="dropdown"><?= $tags ?></div>
        <form action="<?= URL::route('discover') ?>" method="POST" id="topic-bar-form">
            <?php $placeholder = ($query_topics_max == 1) ? 'Enter a Keyword or Domain...' : 'Enter Keywords or Domains...'; ?>
            <select id="topic-bar-input" placeholder="<?= $placeholder ?>" multiple></select>

            <div id="topic-bar-view-dropdown" class="btn-group">
                <input type="hidden" name="view" value="<?= $type ?>" id="topic-bar-view" />
                <button type="button" tabindex="-1" class="btn btn-info dropdown-toggle track-click topic-bar-btn" id="topic-bar-view-btn"
                        data-toggle="dropdown" data-component="Topic Bar" data-element="Go To Button"
                >
                    <i class="icon-list"></i>
                </button>
                <ul class="dropdown-menu btn-dropdown-menu">
                    <li class="dropdown-menu-title">Go To...</li>
                    <li>
                        <a href="javascript:void(0)" class="view" data-view="insights">
                            <?= ($type == 'insights') ? '<i class="icon-checkmark"></i>' : '<i></i>' ?>
                            Insights
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0)" class="view" data-view="trending">
                            <?= ($type == 'trending') ? '<i class="icon-checkmark"></i>' : '<i></i>' ?>
                            Trending
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0)" class="view" data-view="most-repinned">
                            <?= ($type == 'most-repinned') ? '<i class="icon-checkmark"></i>' : '<i></i>' ?>
                            Most Repinned
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0)" class="view" data-view="most-liked">
                            <?= ($type == 'most-liked') ? '<i class="icon-checkmark"></i>' : '<i></i>' ?>
                            Most Liked
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0)" class="view" data-view="most-commented">
                            <?= ($type == 'most-commented') ? '<i class="icon-checkmark"></i>' : '<i></i>' ?>
                            Most Commented
                        </a>
                    </li>
                </ul>
            </div>

            <input type="submit" value="Go" class="btn btn-info topic-bar-btn" id="topic-bar-go-btn" />
        </form>
    </div>
</div>

<div id="manageTopicsModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="manageTopicsModalLabel" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="manageTopicsModalLabel">Manage Topics</h3>
    </div>
    <div class="modal-body">
        <form id="add-topic-form" class="form-inline" action="<?= URL::route('discover-add-topic') ?>" method="POST">
            <ul class="nav nav-tabs" id="nav-tabs-topics">
                <li class="active">
                    <a href="#manage-keyword-topics" id="topic-keywords-tab" data-toggle="tab">Keywords</a>
                </li>
                <li>
                    <a href="#manage-domain-topics" id="topic-domains-tab" data-toggle="tab">Domains</a>
                </li>
            </ul>
            <div id="tab-topic-content" class="tab-content">
                <div class="tab-pane active" id="manage-keyword-topics">
                    <div class="tab-pane-top">
                        <div class="account-topic-add">
                            <div class="input-append">
                                <input type        = "text"
                                       name        = "topic"
                                       id          = "new-keyword-input"
                                       placeholder = "Enter a New Keyword..."
                                       pattern     = "^[a-zA-Z0-9 #&!',_\-\.]+$"
                                       title       = "Keywords should only contain letters, numbers, spaces and these special characters: . _ - ' # & !"
                                       required
                                />
                                <button type="submit" class="btn btn-primary">Add</button>
                            </div>
                            <div class="alert hidden"></div>
                        </div>

                        <?php if (!empty($keyword_limit)): ?>
                            <div id="account-keyword-usage" class="account-topic-usage">
                                <div class="progress progress-info">
                                    <?php $keyword_usage = number_format($keyword_count / $keyword_limit * 100) ?>
                                    <div class="bar" style="width: <?= $keyword_usage ?>%"></div>
                                </div>
                                <small class="bar-text muted">(Using <?= $keyword_count ?> / <?= $keyword_limit ?>)</small>
                                <div class="muted">
                                    <small>Need more keywords? <a href="/upgrade">Upgrade your account</a>.</small>
                                </div>
                            </div>
                        <?php endif ?>
                    </div>
                    <div class="page-header">
                        <h2><small>Remove Keywords</small></h2>
                    </div>
                    <?php if (empty($keywords)): ?>
                        You're not currently following any keywords.
                    <?php else: ?>
                        <ul class="nav nav-keywords">
                            <?php foreach ($keywords as $keyword): ?>
                                <li id="remove-tag-<?= preg_replace('/[^a-zA-Z0-9]+/', '-', $keyword) ?>" class="remove-tag" data-topic="<?= $keyword ?>">
                                    <a href="javascript:void(0)">
                                        <span class="label label-important label-tag">
                                            &times; <?= $keyword ?>
                                        </span>
                                    </a>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    <?php endif ?>
                </div>
                <div class="tab-pane" id="manage-domain-topics">
                    <div class="tab-pane-top">
                        <div class="account-topic-add">
                            <div class="input-append">
                                <input type        = "text"
                                       name        = "topic"
                                       id          = "new-domain-input"
                                       placeholder = "Enter a New Domain..."
                                       pattern     = "^[a-zA-Z0-9 #&!',_\-\.]+$"
                                       title       = "Domains should only contain letters, numbers and these special characters: . - "
                                />
                                <button type="submit" class="btn btn-primary">Add</button>
                            </div>
                            <div class="alert hidden"></div>
                        </div>

                        <?php if (!empty($domain_limit)): ?>
                            <div id="account-domain-usage" class="account-topic-usage">
                                <div class="progress progress-info">
                                    <?php $domain_usage = number_format($domain_count / $domain_limit * 100) ?>
                                    <div class="bar" style="width: <?= $domain_usage ?>%"></div>
                                </div>
                                <small class="bar-text muted">(Using <?= $domain_count ?> / <?= $domain_limit ?>)</small>
                                <div class="muted">
                                    <small>Need more domains? <a href="/upgrade">Upgrade your account</a>.</small>
                                </div>
                            </div>
                        <?php endif ?>
                    </div>
                    <div class="page-header">
                        <h2><small>Remove Domains</small></h2>
                    </div>
                    <?php if (empty($domains)): ?>
                        You're not currently following any domains.
                    <?php else: ?>
                        <ul class="nav nav-domains">
                            <?php foreach ($domains as $domain): ?>
                                <li id="remove-tag-<?= str_replace('.', '-', $domain) ?>" class="remove-tag" data-topic="<?= $domain ?>">
                                    <a href="javascript:void(0)">
                                        <span class="label label-important label-tag">
                                            &times; <?= $domain ?>
                                        </span>
                                    </a>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    <?php endif ?>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="tagTopicsModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="tagTopicsModalLabel" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="tagTopicsModalLabel">Save Topics to a Collection</h3>
    </div>
    <div class="modal-body">
        <form id="add-tag-form" action="<?= URL::route('discover-add-tag') ?>" method="POST">
            <div class="input-append">
                <input type        = "text"
                       name        = "name"
                       id          = "tag-name"
                       placeholder = "Name Your Collection..."
                       required
                />
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
        <div id="add-tag-topics" class="bootstrap-tagsinput"></div>
    </div>
</div>

<script type="text/javascript">
$(function() {
    // Initialize the tag bar plugin.
    loadTopicBar();

    // Listen to changes to the hidden select menu to determine if selected topics are already tagged.
    $('#topic-bar-input').on('change', function () {
        selectedTopics = $('#topic-bar-input').map(function() { return $(this).val(); });
        selectedTopics = $.makeArray(selectedTopics).sort().toString();

        $.each(accountTags, function (index, tag) {
            if (tag['topics'].toString() == selectedTopics) {
                $('.favorite i').addClass('icon-tag-highlighted');
                return false;
            } else {
                $('.favorite i').removeClass('icon-tag-highlighted');
            }
        });
    });

    // Build and submit a new topic query.
    $('#topic-bar-form').on('submit', function (event) {
        event.preventDefault();

        topics = [];
        $('#topic-bar-input option:selected').each(function () {
            topics.push(encodeURIComponent($(this).val()));
        });

        view = $('#topic-bar-view').val();

        window.location = $(this).attr('action') + '/' + view + '/' + topics.join('+');
    });

    // Manage Topics modal.
    $('#topic-keywords-tab').on('shown', function () {
        $('#new-keyword-input').attr('required', 'required').focus();
        $('#new-domain-input').removeAttr('required');
    });

    $('#topic-domains-tab').on('shown', function () {
        $('#new-domain-input').attr('required', 'required').focus();
        $('#new-keyword-input').removeAttr('required');
    });

    $('#manageTopicsModal').on('hidden', function () {
        $('#new-keyword-input').val('');
        $('#new-domain-input').val('');

        $('#manage-keyword-topics').find('.alert').attr('class', 'alert').hide();
        $('#manage-domain-topics').find('.alert').attr('class', 'alert').hide();
    });

    $('#topic-bar-view-dropdown .view').on('click', function () {
        $('#topic-bar-view-dropdown li a i').each(function () {
            $(this).attr('class', '');
        });

        $(this).find('i').addClass('icon-checkmark');
        $('#topic-bar-view').val($(this).data('view'));
        $('#topic-bar-form').submit();
    });

    $('#add-topic-form').on('submit', function (event) {
        event.preventDefault();

        keywordInput = $('#new-keyword-input');
        domainInput  = $('#new-domain-input');

        topic = '', alert = '';
        if (keywordInput.val() != '') {
            topic = keywordInput.val();
            type  = 'keyword';
        } else if (domainInput.val() != '') {
            topic = domainInput.val();
            type  = 'domain';
        }

        alert = $('#manage-' + type + '-topics').find('.alert');
        alert.addClass('alert-info').html('Adding New Topic...').slideDown();

        data = {
            'event_data': {
                'view': appView,
                'component': 'Topics Modal'
            }
        };

        addTopic(topic, data, function (response) {
            if (response.success) {
                alert.html('New Topic Added!').removeClass('alert-info').addClass('alert-success');
                setTimeout(function () {
                    $('#new-' + type + '-input').val('');
                    $('#manage-' + type + '-topics').find('.alert').slideUp();
                }, 1000);
            } else {
                alert.html('Error Adding New Topic!').removeClass('alert-info').addClass('alert-error');
                setTimeout(function () {
                    $('#manageTopicsModal').modal('hide');
                }, 1000);
            }
        });
    });

    /// Show Manage Topics modal and toggle the Keywords tab.
    $('body').on('click', '.manage-keyword-topics', function () {
        $('#topic-keywords-tab').tab('show');
        $('#manageTopicsModal').modal('show');
    });

    // Show Manage Topics modal and toggle the Domains tab.
    $('body').on('click', '.manage-domain-topics', function () {
        $('#topic-domains-tab').tab('show');
        $('#manageTopicsModal').modal('show');
    });

    // Add selected tag's topics to Topic Bar.
    $('#tags-dropdown').on('click', '.tag a', function () {
        tagName = $(this).parent('li').data('tag-name');
        tagTopics  = accountTags[tagName]['topic_bar'];

        $('#topic-bar-input').tagsinput('removeAll');
        $('#topic-bar-input').tagsinput('addMany', tagTopics);
        $('#topic-bar-input').tagsinput('focus');
    });

    // Show/hide delete icon on tag list item hover.
    $('#tags-dropdown').on({
        mouseenter: function () {
            $(this).find('i').show();
        },
        mouseleave: function () {
            $(this).find('i').hide();
        }
    }, '#tags-dropdown-menu li');

    // Delete tag upon confirmation.
    $('#topic-bar').on('click', '#tags-dropdown-menu li i', function () {
        if (confirm('Are you sure you want to delete this tag?')) {
            element = $(this);
            element.parent('li').remove();

            $.ajax({
                type: 'DELETE',
                url: element.data('url'),
                cache: false
            });
        }
    });

    // Show Add Tag modal when "Favorite" icon is clicked.
    $('#topic-bar').on('click', '.favorite', function () {
        if ($(this).find('i').hasClass('icon-tag-highlighted')) {
            return;
        }

        $('#tagTopicsModal').modal('show');

        $('#tagTopicsModal').on('shown', function () {
            $('#tag-name').focus();
        });

        tagTopicLabels = $('#add-tag-topics').html('');
        $('#topic-bar-input option').each(function () {
            if ($(this).val().indexOf('.') > -1) {
                // Domain topic.
                topicLabel = $('<span class="tag label label-success">' + $(this).val() + '</span>')
            } else {
                // Keyword topic.
                topicLabel = $('<span class="tag label label-info">' + $(this).val() + '</span>');
            }

            topicLabel.appendTo(tagTopicLabels);
        });
    });

    // Create a tag.
    $('#add-tag-form').on('submit', function (event) {
        event.preventDefault();

        topics = $('#topic-bar-input').map(function() { return $(this).val(); });

        $.ajax({
            type: 'POST',
            data: {
                name   : $('#tag-name').val(),
                topics : $.makeArray(topics)
            },
            dataType: 'json',
            url: $(this).attr('action'),
            cache: false,
            success: function () {
                loadTags();
                $('.favorite i').addClass('icon-tag-highlighted');
            }
        });

        $('#tagTopicsModal').modal('hide');
        $('#tag-name').val('');
    });

    // Delete a Topic (via Manage Topics modal).
    $('.remove-tag a').on('click', function () {
        element = $(this).parent();
        topic   = element.data('topic');

        $('#remove-tag-' + topic.replace(/[^a-zA-Z0-9]/g, "-")).remove();

        removeTopic(topic, {
            'event_data': {
                'view': appView,
                'component': 'Topics Modal'
            }
        });
    });
});

var accountKeywordCount = <?= $keyword_count ?>;
var accountDomainCount  = <?= $domain_count ?>;
var accountKeywordLimit = <?= $keyword_limit ?>;
var accountDomainLimit  = <?= $domain_limit ?>;

/**
 * Loads (or reloads) the Topic Bar.
 *
 * @return void
 */
function loadTopicBar()
{
    $.ajax({
        type: 'GET',
        url: '<?= URL::route("discover-topic-bar") ?>',
        cache: false,
        success: function (data) {
            // Clear any existing TopicBar tagsinput instance.
            $('#topic-bar-input').tagsinput('removeAll');
            $('#topic-bar-input').tagsinput('destroy');

            $('#topic-bar-input').tagsinput({
                maxTags: <?= $query_topics_max ?>,
                itemText: 'text',
                itemValue: 'value',
                tagClass: function (item) {
                    switch (item.type) {
                        case 'keyword' : return 'label label-info';
                        case 'domain'  : return 'label label-success';
                    }
                },
                typeahead: {
                    source: data,
                    sourceTags: true
                }
            });

            // Populate the tag bar with any selected topics.
            <?php foreach ($query_data as $data): ?>
                $('#topic-bar-input').tagsinput('add', <?= json_encode($data) ?>);
            <?php endforeach ?>

            // Fade in Topic Bar to avoid odd renderings during page load.
            if ($('#topic-bar').hasClass('hidden')) {
                $('#topic-bar').fadeIn(400, function () {
                    $('#topic-bar-input').tagsinput('focus');
                }).removeClass('hidden');
            }
        }
    });
}

/**
 * Requests and displays an account's tags.
 *
 * @return void
 */
function loadTags()
{
    $.ajax({
        type: 'GET',
        url: '<?= URL::route("discover-tags") ?>',
        cache: false,
        success: function (html) {
            $('#tags-dropdown').html(html);
        }
    });
}

/**
 * Updates an account's topic counts (client-side).
 *
 * @param string action
 * @param string topicType
 *
 * @return void
 */
function updateAccountTopicUsage(action, topicType)
{
    if (topicType == 'keyword') {
        if (action == 'add') {
            accountKeywordCount++;
        }
        else if (action == 'remove') {
            accountKeywordCount--;
        }

        usage = Math.round(accountKeywordCount / accountKeywordLimit * 100);
        $('#account-keyword-usage .bar').attr('style', 'width:' + usage + '%');
        $('#account-keyword-usage .bar-text').html(
            '(Using ' + accountKeywordCount + ' / ' + accountKeywordLimit + ')'
        );
    }
    else if (topicType == 'domain') {
        if (action == 'add') {
            accountDomainCount++;
        }
        else if (action == 'remove') {
            accountDomainCount--;
        }

        usage = Math.round(accountDomainCount / accountDomainLimit * 100);
        $('#account-domain-usage .bar').attr('style', 'width:' + usage + '%');
        $('#account-domain-usage .bar-text').html(
            '(Using ' + accountDomainCount + ' / ' + accountDomainLimit + ')'
        );
    }
}
</script>