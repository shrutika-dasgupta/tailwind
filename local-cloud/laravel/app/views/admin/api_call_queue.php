
<div class="span11">
    <h2 style="margin-bottom: 20px">Calls left on queue</h2>
    <?= $calls_table; ?>
</div>

<div class="span11">
    <h2 style="margin-bottom: 20px">Pull Status</h2>
    <?= $pulls_table; ?>
</div>

<div class="span11">
    <a style="margin-bottom: 10px;" class="btn btn-info pull-right" href="/engines/all-clear"/>Send message to HipChat saying engines are "all clear"</a>
    <h2 style="margin-bottom: 20px">Engines Status</h2>
    <?= $status_engines; ?>
</div>