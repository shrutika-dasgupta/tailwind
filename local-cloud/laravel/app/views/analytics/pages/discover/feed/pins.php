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
                                We're collecting pin data
                                <?php if (!empty($keywords)): ?>
                                    for "<?= implode('", "', $keywords) ?>"
                                <?php endif ?>
                                
                                <?php if (!empty($domains)): ?>
                                    from "<?= implode('", "', $domains) ?>"
                                <?php endif ?>
                                . Check back soon!
                            </div>
                        <?php else: ?>
                            <?php if (!in_array($type, array('trending', 'snapshot')) && !empty($keywords) && !empty($domains)): ?>
                                <div class="alert alert-info">
                                    Popular Feeds contain a mix of pins that match the selected keyword(s) <em>or</em> domain(s).
                                </div>
                            <?php endif ?>

                            <table cellpadding="0" cellspacing="0" border="0" class="table table-striped table-hover table-bordered dt-keywords">
                                <thead class="datatable-header">
                                    <tr>
                                        <th class="datatable_pin_col">Pin</th>
                                        <th class="datatable_pin_col">Pin Description</th>
                                        <th class="datatable_pin_col">Engage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pins as $pin): ?>
                                        <tr>
                                            <td class="datatable_pin_col">
                                                <div class="datatable_pin_image">
                                                    <a href="http://pinterest.com/pin/<?= $pin->pin_id ?>" target="_blank" class="pin-image-<?= $type ?>">
                                                        <img src="<?= $pin->image_url ?>" />
                                                    </a>
                                                </div>
                                                <div class="datatable_pin_actions">
                                                    <?php if (!empty($pin->repin_count)): ?>
                                                        <span class="pin-action" data-toggle="popover" data-content="<?= number_format($pin->repin_count) ?> Repins">
                                                            <i class="icon-pin"></i> <?= number_format($pin->repin_count) ?>
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
                                            <td class="datatable_pin_description">
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

                                                <?= View::make('analytics.pages.discover.profile', array('profile' => $pin->pinner(), 'component' => 'Pin Feed')) ?>
                                            </td>
                                            <td class="datatable_actions_col">
                                                <div class="datatable_actions_wrapper">
                                                    <div class="action-buttons">
                                                        <a href="http://pinterest.com/pin/<?= $pin->pin_id ?>/repin/x/" target="_blank" class="btn btn-block btn-danger track-click" data-component="Pin Feed" data-element="Repin Button">
                                                            Repin!
                                                        </a>
                                                        <a href="http://pinterest.com/pin/<?= $pin->pin_id ?>" target="_blank" class="btn btn-block track-click" data-component="Pin Feed" data-element="Comment Button">
                                                            Comment
                                                        </a>
                                                        <a class="btn btn-block save-button track-click" data-component="Pin Feed" data-element="Save Button">
                                                            <i class="icon-star"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>

                            <?php if ($next_page_link): ?>
                                <div class="pull-right">
                                    <a href="<?= $next_page_link ?>" class="btn btn-mini next-btn-<?= $type ?>">Next 50 →</a>
                                </div>
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