<div class="accordion">
    <div class="accordion-heading">
        <div class="title"><?= ($type == "most-commented" ? "Comment Word Cloud" : "Word Cloud") ?></div>
    </div>
    <div class="accordion-body collapse in">
        <div class="accordion-inner">
            <div id="key-wordcloud" class="wordcloud-wrapper"></div>
        </div>
    </div>
</div>

<?php if ($wordcloud_data): ?>
<script type="text/javascript">
$(function() {
    var rightNavWidth = $('#key-wordcloud').width();
    $('#key-wordcloud').jQCloud(
        <?= $wordcloud_data ?>,
        {'width': rightNavWidth+25, 'height':500, 'delayedMode':true, 'removeOverflowing':false,
        'afterCloudRender': function(){
            $("[data-toggle='popover']").popover({html: true, delay: { show: 0, hide: 0 }, animation: false, trigger: 'hover'});
        }}
    );
});
</script>
<?php endif ?>