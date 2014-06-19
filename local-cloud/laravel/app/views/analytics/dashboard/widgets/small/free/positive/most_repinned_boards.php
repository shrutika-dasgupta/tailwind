<div class="module widget">

    <a href="/boards" class="row-fluid out-of-face">

    <span class="pull-right"><i class="icon-board"></i></span>
    <h4 class="title">
        Most Repinned boards
    </h4>
    </a>

    <p class="blurb">
        Your most repinned board is in
        the  <a href="/categories"><?= $top_board_category; ?> category</a>,
        where the virality of your pins were <?= $top_board_virality; ?>.
        Of the board's <?= $top_total_metric; ?>
        repins, <?= number_format($top_board_metric); ?> came in
        the last 7 days.
    </p>

    <?php foreach($boards as $board) { ?>
        <a class="row-fluid out-of-face board" href="/pins/owned?b=<?= substr($board->board_id, -8);?>">
           <div class="span4">
               <img src="<?= $board->image_cover_url;?>"
                    title="<?= $board->name;?>" />
           </div>
            <div class="span7 offset1">
                <h5 class="board-title">
                    <?= $board->name;?>
                </h5>
                <div class="<?= sentiment($board->new_repins_count);?>">
                    <?= arrow($board->new_repins_count);?> <?= number_format($board->new_repins_count); ?> repins
                </div>
                <div class="metric-change">
                    <?= $board->viralityScore(); ?> virality score.
                </div>
            </div>
        </a>
        <div class="row-fluid">
            <hr style="margin:10px 0 10px">
        </div>
    <?php } ?>


    <div class="row-fluid">
        <a href="/boards?ref=dashboard" class="btn btn-info btn-small pull-right">
            Explore All Boards
        </a>
    </div>

</div>






