<div class="module widget">
    <div class="row-fluid">
        <a href="/settings/tasks">
            <div class="span8">
                <h4 class="title">Complete your account</h4>
            </div>
            <div class="span4 text-right sub-title">
                <div class="progress">
                    <div class="bar"
                         style="width: <?= $completeness_percentage; ?>%;"></div>
                </div>
            </div>
        </a>
    </div>

    <div class="row-fluid">
        <div class="span12">
            <div class="<?= $class; ?> task">
                <?= $content; ?>
            </div>
        </div>
    </div>
    <div class="row-fluid">
        <a href="/settings/tasks" class="small-text pull-right text-right">See All Suggestions→</a>
    </div>
</div>
