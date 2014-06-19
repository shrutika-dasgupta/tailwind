<div class="accordion">
    <div class="accordion-heading">
        <div class="title">Word Cloud</div>
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
    $('#key-wordcloud').jQCloud(
        <?= $wordcloud_data ?>,
        {'width':199, 'height':500, 'delayedMode':true, 'removeOverflowing':false,
        'afterCloudRender': function(){
            $("[data-toggle='popover']").popover({html: true, delay: { show: 0, hide: 0 }, animation: false, trigger: 'hover'});
        }}
    );
});
</script>
<?php endif ?>