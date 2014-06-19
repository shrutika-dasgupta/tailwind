<?= $report_overlay ?>
<?= $navigation ?>

<div class="row-fluid">
    <div class="listening-dashboard <?= empty($right_navigation) ? 'span12' : 'span9' ?>" id="listening-feed">
        <div class="accordion">
            <?= $header ?>

            <div class="accordion-body collapse in">
                <div class="accordion-inner">
                    <div class="row no-margin">
                        <?php if (empty($pins)): ?>
                            <div class="alert alert-info">
                                <?php if (in_array($type, array('most-clicked'))): ?>
                                    No pins driving traffic <?= $timeframe ?>.
                                    If you just recently synced your Google Analytics profile, it may
                                    take up to 24 hours to create this report.  Check back soon!
                                <?php elseif (in_array($type, array('most-repinned', 'most-liked', 'most-commented'))): ?>
                                    We haven't found any pins from <?= $query_string ?>
                                    with <?= $engagement_type ?> <?= $timeframe ?>.
                                    <?php if (in_array(Input::get('date', 'week'), array('week','2weeks','month'))): ?>
                                        Perhaps try a longer date range like
                                        <a href="<?= URL::route('domain-feed', array($type, $query_string)) ?>?date=2months">
                                            the last 60 days
                                        </a>?
                                    <?php endif; ?>
                                <?php else: ?>
                                    We're collecting pin data
                                    <?php if (!empty($keywords)): ?>
                                        for "<?= implode('", "', $keywords) ?>"
                                    <?php endif ?>

                                    <?php if (!empty($domains)): ?>
                                        from "<?= implode('", "', $domains) ?>"
                                    <?php endif ?>
                                    . Check back soon!
                                <?php endif ?>
                            </div>
                        <?php else: ?>
                            <?php if (!in_array($type, array('latest', 'snapshot')) && !empty($keywords) && !empty($domains)): ?>
                                <div class="alert alert-info">
                                    Popular Feeds contain a mix of pins that match the selected keyword(s) <em>or</em> domain(s).
                                </div>
                            <?php endif ?>

                            <table cellpadding="0" cellspacing="0" border="0" class="table table-striped table-hover table-bordered dt-keywords">
                                <thead class="datatable-header">
                                    <tr>
                                        <th class="datatable_pin_col">Pin</th>
                                        <?php if (in_array($type, array('most-visits','most-clicked','most-pageviews','most-transactions','most-revenue'))): ?>
                                            <th class="datatable_pin_col sorting_desc"><?= $order_by ?></th>
                                        <?php endif ?>
                                        <th class="datatable_pin_col">Pin Description</th>
                                        <th class="datatable_pin_col">Engage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pins as $pin): ?>
                                        <tr>
                                            <td class="datatable_pin_col <?= ($type == "most-commented" ? "comments" : "") ?>">
                                                <div class="datatable_pin_image">
                                                    <a href="http://pinterest.com/pin/<?= $pin->pin_id ?>" target="_blank" class="pin-image-<?= $type ?>">
                                                        <img src="<?= $pin->image_url ?>" />
                                                    </a>
                                                </div>
                                                <div class="datatable_pin_actions">
                                                    <?php if (!empty($pin->repin_count)): ?>
                                                        <span class="pin-action" data-toggle="popover" data-content="<?= number_format($pin->repin_count) ?> Repins">
                                                            <i class="icon-repin"></i> <?= number_format($pin->repin_count) ?>
                                                        </span>
                                                    <?php endif ?>
                                                    <?php if (!empty($pin->like_count)): ?>
                                                        <span class="pin-action" data-toggle="popover" data-content="<?= number_format($pin->like_count) ?> Likes">
                                                            <i style="font-size:10px;" class="icon-heart"></i> <?= number_format($pin->like_count) ?>
                                                        </span>
                                                    <?php endif ?>
                                                    <?php if (!empty($pin->comment_count)): ?>
                                                        <span class="pin-action" data-toggle="popover" data-content="<?= number_format($pin->comment_count) ?> Comments">
                                                            <i class="icon-comments"></i> <?= number_format($pin->comment_count) ?>
                                                        </span>
                                                    <?php endif ?>
                                                </div>
                                            </td>

                                            <?php if (in_array($type, array('most-clicked','most-visits','most-pageviews','most-transactions','most-revenue'))): ?>
                                                <td class="datatable_value_col">
                                                    <div class="datatable_actions_wrapper">
                                                        <div class="action-buttons">
                                                            <div class="value-metric"><?= ($order_by == "revenue" ? "$" : "") ?><?= $pin->$order_by ?></div>
                                                            <div class="value-metric-label"><?= $order_by ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                            <?php endif ?>

                                            <td class="datatable_pin_description <?= ($type == "most-commented" ? 'comments" style="position:relative;"' : "") ?>">
                                                <div class="datatable_pin_description_wrapper row margin-fix">
                                                    <?php
                                                    $pin_description = $pin->description;
                                                    foreach ($keywords as $keyword) {
                                                        $pin_description = str_ireplace($keywords, "<span class='text-success'><strong><u>{$keyword}</u></strong></span>", $pin_description);
                                                    }
                                                    ?>
                                                    <div class="pin_description">
                                                        <?= $pin_description ?>
                                                        <?php if (strlen($pin->description) >= 255): ?>
                                                            ...
                                                            <a href="http://pinterest.com/pin/<?= $pin->pin_id ?>" target="_blank">
                                                                (read more)
                                                            </a>
                                                        <?php endif ?>
                                                    </div>

                                                    <div class="datatable-pin-meta">
                                                        <?php if ($pin->domain): ?>
                                                            <div class="datatable-pin-source pull-left">
                                                                <i class="icon-earth"></i>
                                                                <a href="<?= $pin->link ?>" target="_blank" class="pin-source-link-<?= $type ?>">
                                                                    <span data-toggle="popover" data-content="<?= $pin->link ?>" data-placement="top">
                                                                        <?= $pin->domain ?> <i class="icon-new-tab"></i>
                                                                    </span>
                                                                </a>
                                                            </div>
                                                        <?php endif ?>

                                                        <div class="datatable-pin-createdat pull-right" style="opacity:0.6; font-size:12px;">
                                                            <?= ($pin->is_repin) ? 'Repinned' : 'Pinned' ?>
                                                            <?php echo \Carbon\Carbon::createFromTimeStamp($pin->created_at)->diffForHumans() ?>
                                                            <?php if (!empty($pin->method)): ?>
                                                                via
                                                                <?php if (in_array($pin->method, array('api_sdk', 'api_other', 'scraped', 'extension'))): ?>
                                                                    widget
                                                                <?php elseif ($pin->method == 'button'): ?>
                                                                    pin it button
                                                                <?php else: ?>
                                                                    <?= $pin->method ?>
                                                                <?php endif ?>
                                                            <?php endif ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <?= View::make('analytics.pages.domain.profile', array('profile' => $pin->pinner(), 'component' => 'Pin Feed')) ?>

                                                <?php if ($type == "most-commented"): ?>
                                                    <div class="comment-count">
                                                        <div class="comment-count-inner highlight-imp-grey">
                                                            <?= $pin->comments()->count() ?> Comments
                                                        </div>
                                                    </div>
                                                <?php endif ?>
                                            </td>

                                            <td class="datatable_actions_col <?= ($type == "most-commented" ? "comments" : "") ?>">
                                                <div class="datatable_actions_wrapper">
                                                    <div class="action-buttons">
                                                        <a href="http://pinterest.com/pin/<?= $pin->pin_id ?>/repin/x/" target="_blank" class="btn btn-block btn-danger track-click" data-component="Pin Feed" data-element="Repin Button">
                                                            Repin!
                                                        </a>
                                                        <?php if($type != "most-commented"): ?>
                                                        <a href="http://pinterest.com/pin/<?= $pin->pin_id ?>" target="_blank" class="btn btn-block track-click" data-component="Pin Feed" data-element="Comment Button">
                                                            Comment
                                                        </a>
                                                        <?php endif ?>
                                                        <a class="btn btn-block save-button track-click" data-component="Pin Feed" data-element="Save Button">
                                                            <i class="icon-star"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php if ($type == "most-commented"): ?>

                                            <tr>
                                                <td colspan="3">
                                                    <div class="comments-outer-container" style="position:relative;">
                                                    <?php if ($pin->comments()->count() > 2): ?>
                                                        <div class="collapse-comments hidden">
                                                            Click to Collapse <i class="icon-arrow-up"></i>
                                                        </div>
                                                    <?php endif ?>
                                                        <div class="comments-middle-container <?= ($pin->comments()->count() > 2 ? "collapsed" : "") ?>">

                                                    <?php foreach ($pin->comments() as $comment): ?>

                                                            <div class="row margin-fix comments-inner-container">
                                                                <div class="span5">
                                                                    <?= View::make('analytics.pages.domain.profile', array('profile' => $comment->commenter, 'component' => 'Pin Feed', 'commenter' => true)) ?>
                                                                </div>
                                                                <div class="span1">
                                                                    <div class="comment-datetime">
                                                                        <?= date("M d", $comment->created_at) ?>
                                                                        <br><?= date("ga", $comment->created_at) ?>
                                                                    </div>
                                                                </div>
                                                                <div class="span4">
                                                                    <div class="comment-description">
                                                                        <?= $comment->comment_text ?>
                                                                    </div>
                                                                </div>
                                                                <div class="span2">
                                                                    <div class="comment-cta">
                                                                        <a target="_blank" href="http://www.pinterest.com/pin/<?= $pin->pin_id?>">
                                                                            <button class="btn btn-success">Respond »</button>
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                    <?php endforeach ?>
                                                            <div class="post-comment-spacer"></div>
                                                        </div>
                                                    <?php if ($pin->comments()->count() > 2): ?>
                                                        <div class="fadeout-bottom" style="text-align:center;">
                                                            <div class="expand-comments">
                                                                Click to Expand <i class="icon-arrow-down"></i>
                                                            </div>
                                                        </div>
                                                    <?php endif ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="3">
                                                    <div class="row margin-fix">
                                                        <div class="most-commented-pin-spacer span12">
                                                            &nbsp;
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>

                                        <?php endif ?>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                            <?php if (count($pins) >= 50): ?>
                                <?php if ($next_page_link): ?>
                                    <div class="pull-right">
                                        <a href="<?= $next_page_link ?>" class="btn btn-mini next-btn-<?= $type ?>">Next 50 →</a>
                                    </div>
                                <?php endif ?>
                            <?php endif ?>

                            <?php if ($prev_page_link): ?>
                                <div class="pull-right">
                                    <a href="<?= $prev_page_link ?>" class="btn btn-mini prev-btn-<?= $type ?>">← Prev 50</a>
                                </div>
                            <?php endif ?>

                        <?php endif ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($right_navigation)): ?>
        <div class="listening-control span3">
            <?= $right_navigation; ?>
        </div>
    <?php endif ?>
</div>