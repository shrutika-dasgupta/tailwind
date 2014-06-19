<div class="row-fluid">
    <div class="span12">
        <a href="http://www.pinterest.com<?= $board_url; ?>" target="_blank"
           class="input-block-level btn btn-info task-action">
            Set Category
        </a>

        <p>
            Your board <span class="board-name"><?= $board_name; ?></span> does
            not have a category set. The most viral and engaging boards are
            categorized.
        </p>
    </div>
</div>
<hr id="source-divider"/>
<div class="row-fluid source">
    <div class="span3">
        <img src="<?= $image_source; ?>"/>
    </div>
    <div class="span9 name">
        <?= $board_url; ?>
    </div>
</div>