<div class="accordion widget">
    <div class="accordion-heading">
        <div class="title">
            Most Followed Boards
        </div>
    </div>
    <div class="accordion-body collapse in boards">
        <div class="accordion-inner">

            <? /** @var $board Board */ ?>
            <?php foreach ($boards as $board) { ?>
                <div class="row-fluid board">
                    <div class="span3">
                        <img src="<?=$board['image']; ?>" />
                    </div>
                    <div class="span9">
                        <h4 class="board_name"><?= $board['title']; ?></h4>
                    <span class="<?= $board['metric_class']; ?>">
                    <i class="icon-arrow-<?= $board['arrow']; ?>"></i><span><?= $board['metric']; ?> followers</span>
                    </span>
                    </div>
                </div>
            <? } ?>
            <hr />
            <a href="/boards" class="btn btn-info pull-right ">Analyze All Boards</a>
        </div>
    </div>
</div>

<div class="clearfix"></div>

