<?php
/**
 * @author Alex
 *         Date: 8/29/13 1:25 AM
 *
 */
?>

<div id="main-top-toolbar">
    <div class="navbar navbar-fixed-top">
        <div class="navbar-inner">
            <h1 class="nav-title"><?= $top_nav_title; ?></h1>

            <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </a>

            <div class="nav-collapse">
                <ul class="nav pull-right">
                    <?= $admin_dropdown; ?>

                    <li class="<?= $go_pro_class; ?>">
                        <a <?= $go_pro_link; ?>
                           data-toggle="tooltip"
                           data-placement="bottom"
                           data-container="body"
                           data-title="<?= $go_pro_tooltip; ?>">
                        <?= $go_pro_text; ?>
                        </a>
                    </li>

                    <?php if ($top_nav_title == 'Publisher'): ?>
                        <li>
                            <button id="Intercom" class="btn btn-success">
                                <strong>Feedback</strong>
                            </button>
                        </li>
                    <?php endif ?>

                    <li class="divider-vertical"></li>

                    <?= $add_dropdown; ?>

                    <li class="divider-vertical"></li>
                    <li class="menu-chat">
                        <a href="javascript:void(0);"
                           style="padding-bottom: 3px;"
                           onclick="olark('api.box.expand')"
                           data-toggle="popover" data-content="Chat with us!"
                           data-placement="bottom"
                           data-container="body">
                            <i class="icon-comments"></i>
                        </a>
                    </li>

                    <li class="divider-vertical"></li>
                    <?= $help_dropdown; ?>
                    
                    <li class="divider-vertical"></li>
                    <?= $settings_dropdown; ?>

                    <li class="divider-vertical"></li>
                    <li style="font-size: 20px;">
                        <a data-toggle="popover"
                           data-content="See  ya :("
                           data-placement="bottom"
                           data-container="body"
                           href="/logout">
                            <i class="icon-signout"></i>
                            <span aria-hidden="true" class="icon-exit">
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <?= $notice; ?>
    </div>
</div>

<script type="text/javascript">
    $(window).ready(function() {
        $('#main-top-toolbar ul.nav li.dropdown').hover(function() {
            $(this).addClass('open');
        }, function() {
            $(this).removeClass('open');
        });
    });
</script>
