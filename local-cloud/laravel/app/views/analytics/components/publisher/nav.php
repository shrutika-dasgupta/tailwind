<div class="navbar navbar-publisher">
    <div class="navbar-inner">
        <ul class="nav">

            <li<?= ($view == 'drafts') ? ' class="active"' : '' ?>>
                <a href="<?= URL::route('publisher-posts', array('drafts')) ?>">
                    <strong>Drafts</strong>
                    <span class="badge <?= ($drafts_count > 0) ? 'badge-important' : '' ?> badge-drafts-count">
                        <?= $drafts_count;?>
                    </span>
                </a>
            </li>

            <li class="divider-vertical"></li>

            <li<?= ($view == 'scheduled') ? ' class="active"' : '' ?>>
                <a href="<?= URL::route('publisher-posts', array('scheduled')) ?>">
                    <strong>Scheduled</strong>
                    <span class="badge <?= ($scheduled_count == 0) ? 'badge-important' : 'badge-success' ?> badge-scheduled-count">
                        <?= $scheduled_count;?>
                    </span>
                </a>
            </li>

            <li class="divider-vertical"></li>

            <?php if ($approval_queue): ?>
                <li<?= ($view == 'pending') ? ' class="active"' : '' ?>>
                    <a href="<?= URL::route('publisher-posts', array('pending')) ?>">
                        <strong>
                            <?php if ($admin_user): ?>
                                Awaiting Approval
                            <?php else: ?>
                                Ready for Approval
                            <?php endif ?>
                        </strong>
                        <span class="badge <?= ($pending_count > 0) ? 'badge-important' : '' ?> badge-pending-count">
                            <?= $pending_count ?>
                        </span>
                    </a>
                </li>

                <li class="divider-vertical"></li>
            <?php endif ?>

            <li<?= ($view == 'published') ? ' class="active"' : '' ?>>
                <a href="<?= URL::route('publisher-posts', array('published')) ?>">
                    <strong>Published</strong>
                </a>
            </li>

            <li class="divider-vertical"></li>

        </ul>

        <ul class="nav pull-right">
            <li <?php if ($page == 'schedule'): ?>class="active"<?php endif ?>>
                <a href="<?= URL::route('publisher-schedule') ?>">
                    <i class="icon-calendar-2"></i>&nbsp;
                    <strong>
                        <?php if ($admin_user): ?>
                            Set Your Schedule
                        <?php else: ?>
                            See Your Schedule
                        <?php endif ?>
                    </strong>
                </a>
            </li>

            <li class="divider-vertical"></li>

            <li class="dropdown">
                <a href="javascript:void(0);"
                   id="drop-publisher-new-post"
                   role="button"
                   class="dropdown-toggle"
                   data-toggle="dropdown"
                    >
                    <i class="icon-plus"></i>
                    <strong>New Post</strong>
                    <b class="caret"></b>
                </a>

                <ul id="publisher-new-post-dropdown"
                    class="dropdown-menu"
                    role="menu"
                    aria-labelledby="drop-publisher-new-post"
                    >
                    <li role="presentation">
                        <a role="menuitem" tabindex="-1" href="#upload" id="new-post-upload">
                            Upload Posts
                        </a>
                    </li>
                    <li role="presentation">
                        <a role="menuitem"
                           tabindex="-1"
                           href="<?= URL::route('publisher-new-post') ?>"
                           class="new-post-link"
                            >
                            Add from a Website
                        </a>
                    </li>
                </ul>
            </li>

            <li class="divider-vertical"></li>

            <li class="dropdown <?= (in_array($page, array('permissions', 'tools'))) ? 'active' : '' ?>">
                <a href="javascript:void(0);"
                   id="drop-publisher-settings"
                   role="button"
                   class="dropdown-toggle"
                   data-toggle="dropdown"
                    >
                    <i class="icon-wrench"></i>
                    <strong>Settings and Tools</strong>
                    <b class="caret"></b>
                </a>

                <ul id="publisher-settings-dropdown"
                    class="dropdown-menu"
                    role="menu"
                    aria-labelledby="drop-publisher-settings"
                    >

                    <?php if ($admin_user): ?>
                        <li role="presentation"
                            <?php if ($page == 'permissions'): ?>
                                class="active"
                            <?php endif ?>
                            >
                            <a role="menuitem" tabindex="-1" href="<?= URL::route('publisher-permissions') ?>">
                                Team Permissions
                            </a>
                        </li>

                        <li role="presentation" class="divider"></li>
                    <?php endif ?>

                    <li role="presentation"
                        <?php if ($page == 'tools'): ?>
                            class="active"
                        <?php endif ?>
                        >
                        <a role="menuitem" tabindex="-1" href="<?= URL::route('publisher-tools') ?>">
                            Extension and Bookmarklet
                        </a>
                    </li>
                </ul>

            </li>

        </ul>
    </div>
</div>

<div id="dropzone"></div>

<div class="dropzone-previews accordion hidden" id="post-upload-progress">
    <div class="accordion-group">
        <div class="accordion-heading"
             data-toggle="collapse"
             data-parent="#post-upload-progress"
             href="#post-upload-previews"
        >
            <div class="row-fluid">
                <div class="span4">
                    <h4>
                        <i class="icon-arrow-up"></i>
                        Uploading Images
                    </h4>
                </div>
                <div class="span8">
                    <div class="progress progress-success"
                         id="total-progress"
                         role="progressbar"
                         aria-valuemin="0"
                         aria-valuemax="100"
                         aria-valuenow="0"
                    >
                        <div class="bar"></div>
                    </div>
                </div>
            </div>
        </div>

        <div id="post-upload-previews" class="accordion-body collapse in">
            <div class="accordion-inner">
                <div id="post-upload-template" class="media">
                    <a class="pull-left" href="javascript:void(0);">
                        <img class="media-object" data-dz-thumbnail>
                    </a>
                    <div class="media-body">
                        <strong class="media-heading" data-dz-name></strong>
                        <p><small data-dz-size></small></p>
                        <strong class="text-error" data-dz-errormessage></strong>
                        <div class="progress progress-striped active"
                             role="progressbar"
                             aria-valuemin="0"
                             aria-valuemax="100"
                             aria-valuenow="0"
                        >
                            <div class="bar" data-dz-uploadprogress></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/assets/packages/dropzone/downloads/dropzone.min.js"></script>

<script type="text/javascript">
    $(function() {
        $('.navbar-publisher ul.nav li.dropdown').hover(function() {
            $(this).addClass('open');
        }, function() {
            $(this).removeClass('open');
        });

        var dropzone_template = $('#post-upload-template');
        dropzone_template.attr('id', '');
        var dropzone_template_html = $('#post-upload-previews .accordion-inner').html();
        dropzone_template.remove();

        var dropzone = new Dropzone("#dropzone", {
            url: "<?= URL::route('publisher-process-upload') ?>",
            clickable: "#new-post-upload",
            addRemoveLinks: false,
            autoProcessQueue: true,
            thumbnailWidth: 70,
            thumbnailHeight: 70,
            parallelUploads: 20,
            previewTemplate: dropzone_template_html,
            previewsContainer: "#post-upload-previews .accordion-inner",
            complete: function() {
                $('#post-upload-previews').collapse('hide');
                window.location.href = "<?= URL::route('publisher-posts', array('drafts')); ?>";
                $('#total-progress .bar').text('Loading Drafts');
            }
        });

        dropzone.on('addedfile', function(file) {
            $('#post-upload-progress').show();
        });

        dropzone.on('totaluploadprogress', function(progress) {
            $('#total-progress .bar').css({width: progress + '%'});
        });

        dropzone.on('uploadprogress', function(file) {
            var progress = file.upload.progress;
            var preview = $(file.previewElement);

            if (progress == 100) {
                preview.find('.bar').removeClass('progress-striped active').addClass('progress-success');
                preview.delay(1500).slideUp();
            }
        });

        $('#post-upload-previews').on('show', function() {
            $('#post-upload-progress .icon-arrow-down').addClass('icon-arrow-up').removeClass('icon-arrow-down');
        });

        $('#post-upload-previews').on('hide', function() {
            $('#post-upload-progress .icon-arrow-up').addClass('icon-arrow-down').removeClass('icon-arrow-up');
        });

        $('.new-post-link').on('click', function(event) {
            event.preventDefault();

            $('#edit-post-modal').remove();

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

                    $('body').append(response.html);

                    editPostModal.init();
                    editPostModal.submitCallback = function(response) {
                        location.href = response.redirect;
                    };
                    editPostModal.show();
                }
            });
        });
    });
</script>