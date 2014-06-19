<div class="accordion widget divided-large">
    <div class="accordion-body collapse in top-boards">
        <div class="accordion-inner">
            <div class="row-fluid">
                <div class="span7">

                    <a class="feature-stat" href="/pins/owned?b=<?= $top_board_id; ?>">
                        <div class="row-fluid">
                            <span class="action pull-right">
                            <i class="icon-board"></i>
                        </span>
                           <?= $top_board_name; ?>

                        </div>
                        <div class="metric-change">
                            <div class="span6">
                                Most Repinned Board

                            </div>
                            <div class="span6 text-right <?= sentiment($top_board_metric); ?>">
                                <?= arrow($top_board_metric); ?> <?= number_format($top_board_metric); ?> repins

                            </div>
                        </div>
                    </a>
                    <div class="row-fluid"><hr></div>
                    <div class="clearfix"></div>
                    <p class="blurb">
                        Your most repinned board is in the  <?=$top_board_category; ?> category, where the virality of your pins were <?= $top_board_virality; ?>. Of the boards <?= $top_total_metric;?> repins, <?= number_format($top_board_metric); ?> came in the last 7 days.
                    </p>
                    <a class="btn btn-info btn-small" href="/categories?ref=dashboard">Explore Most Viral Categories</a>
                    <? /*
                    <div class="row-fluid top-board-pins">
                        <a href="/pins/owned?b=<?=$top_board_id;?>" class="span12">
                           <div class="pins">
                           <?php foreach($top_board_pins as $pin) { ?>
                               <img src="<?= $pin->image_url; ?>" />
                           <?  } ?>
                           </div>
                        </a>
                    </div>
 */ ?>

                </div>
                <div class="span5 ">
                    <div class="top-people-wrap">

                        <div class="top-people">
                            <h4>Other Top Boards This Week</h4>
                            <? /** @var $board Board */ ?>
                            <?php foreach ($boards as $board) { ?>
                                <div
                                    class="span12 board ">
                                    <a href="/pins/owned?b=<?= substr($board['id'],-8); ?>">
                                        <div class="row-fluid">
                                            <div class="span3">
                                                <img src="<?= $board['image'];?>" />
                                            </div>
                                            <div class="span8 offset-1">
                                                <h5 class="board_name">
                                                    <?= $board['title']; ?>
                                                </h5>
                                                <h4 class="<?= sentiment($board['metric']); ?>">
                                                    <?= arrow($board['metric']); ?> <?= $board['metric']; ?> repins
                                                </h4>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <? } ?>
                            <div class="row-fluid">
                                <a class="small-text pull-right text-right" href="/influencers/top-repinners/?ref=dashboard"> Explore All Boards &#8594;</a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="clearfix"></div>
</div>

