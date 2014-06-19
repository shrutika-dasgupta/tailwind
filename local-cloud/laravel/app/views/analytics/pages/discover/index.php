<?= $topic_bar ?>

<?php if (empty($account_topics)): ?>
    <div class="row-fluid">
        <div id="listening-no-topics" class="listening-dashboard span12">
            <div class="accordion">
                <div class="accordion-heading">
                    <div class="title">Getting Started</div>
                </div>

                <div class="accordion-body collapse in">
                    <div class="accordion-inner">
                        <div class="row no-margin">
                            <div class="alert alert-info">
                                You're not yet following any topics. Topics are keywords or domains that you're interested in following.
                                <button class="btn btn-primary manage-keyword-topics">Add a Topic</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php return ?>
<?php endif ?>

<div class="module module-trending-topics">
    <span class="title">Trending Topics</span>
    <?php if (empty($trending_topics)): ?>
        <div class="alert alert-info">
            None of your topics are currently trending. Add more topics to see trending pins.
            <button class="btn btn-primary manage-keyword-topics">Add a Topic</button>
        </div>
    <?php else: ?>
        <table class="table table-condensed">
            <tbody>
                <?php foreach ($trending_topics as $topic => $pins): ?>
                    <?php $encoded_topic = str_replace('+', ' ', urlencode($topic)) ?>
                    <tr>
                        <td class="topic">
                            <a href="<?= URL::route('discover-insights', array($encoded_topic)) ?>">
                                <?php if (strpos($topic, '.') !== false): ?>
                                    <span class="label label-success"><?= $topic ?></span>
                                <?php else: ?>
                                    <span class="label label-info"><?= $topic ?></span>
                                <?php endif ?>
                            </a>
                        </td>
                        <td class="pins">
                            <?php foreach ($pins as $i => $pin): ?>
                                <?php if ($i > 20) break ?>

                                <?php
                                    $pin_description = str_ireplace(
                                        $topic,
                                        "<span class='text-success'><strong><u>$topic</u></strong></span>",
                                        str_replace('"', '', strip_tags($pin->description))
                                    );
                                ?>
                                <a href="http://pinterest.com/pin/<?= $pin->pin_id ?>" target="_blank">
                                    <img src="<?= $pin->image_url ?>"
                                         <?php if ($i == 0) echo 'class="first"' ?>
                                         data-toggle    = "popover"
                                         data-placement = "top"
                                         data-container = "body"
                                         data-content   = "<?= $pin_description ?>"
                                    />
                                </a>
                            <?php endforeach ?>
                        </td>
                        <td class="cta">
                            <a href="<?= URL::route('discover-feed', array('trending', $encoded_topic)) ?>">
                                <i class="icon-fire"></i>
                                Discover More
                            </a>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php endif ?>
</div>

<div class="module module-recommended-topics">
    <span class="title">Recommended Topics</span>
    <?php if (empty($recommended_topics)): ?>
        <div class="alert alert-info">
            Add more topics to see recommendations.
            <button class="btn btn-primary manage-keyword-topics">Add a Topic</button>
        </div>
    <?php else: ?>
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Topic</th>
                    <th>
                        Recommended Topics
                        <i class          = "icon-help"
                           data-toggle    = "popover"
                           data-placement = "top"
                           data-container = "body"
                           data-content   = "Click on a recommended topic to begin following it."
                        ></i>
                        <a href="javascript:void(0)" class="pull-right manage-keyword-topics">
                            <i class="icon-plus"></i> New Topic
                        </a>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recommended_topics as $topic => $recommendations): ?>
                    <?php if (empty($recommendations)) continue ?>
                    <?php $encoded_topic = str_replace('+', ' ', urlencode($topic)) ?>
                    <tr>
                        <td>
                            <a href="<?= URL::route('discover-insights', array($encoded_topic)) ?>">
                                <?php if (strpos($topic, '.') !== false): ?>
                                    <span class="label label-success"><?= $topic ?></span>
                                <?php else: ?>
                                    <span class="label label-info"><?= $topic ?></span>
                                <?php endif ?>
                            </a>
                        </td>
                        <td>
                            <?php $i = 1 ?>
                            <?php foreach ($recommendations as $word => $weight): ?>
                                <?php if ($i >= 10) break ?>

                                <span class="label label-recommendation" data-topic="<?= $word ?>">
                                    <?= $word ?>
                                </span>

                                <?php $i++ ?>
                            <?php endforeach ?>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php endif ?>
</div>

<div class="module module-top-topics">
    <span class="title">Top Pinned Topics</span>
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Rank</th>
                <th>Topic</th>
                <th class="sorting_desc">Pins This Week</th>
                <th>Pins Last Week</th>
                <th class="change">
                    % Change
                    <i class          = "icon-help"
                       data-toggle    = "popover"
                       data-placement = "top"
                       data-container = "body"
                       data-content   = "The change in topic-matching pins from last week to this week."
                    ></i>
                </th>
                <th class="action">Discover</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; ?>
            <?php foreach ($top_topics as $topic => $data): ?>
                <?php $encoded_topic = str_replace('+', ' ', urlencode($topic)) ?>

                <tr<?= ($i >= 6) ? ' class="row-togglable hidden"' : '' ?>>
                    <td class="rank"><?= $i ?></td>
                    <td>
                        <a href="<?= URL::route('discover-insights', array($encoded_topic)) ?>">
                            <?php if (strpos($topic, '.') !== false): ?>
                                <span class="label label-success"><?= $topic ?></span>
                            <?php else: ?>
                                <span class="label label-info"><?= $topic ?></span>
                            <?php endif ?>
                        </a>
                    </td>
                    <td><?= number_format($data['current']) ?></td>
                    <td><?= number_format($data['previous']) ?></td>
                    <td>
                        <?php if ($data['previous'] == 0): ?>
                            <span class="pull-left">-</span>
                        <?php else: ?>
                            <?php $change = number_format(($data['current'] / $data['previous'] - 1) * 100) ?>
                            <?php if ($change > 0): ?>
                                <span class="graph_label_pos active pull-left">
                                    <i class="icon-arrow-up"></i> <?= $change ?>%
                                </span>
                            <?php else: ?>
                                <span class="graph_label_neg active pull-left">
                                    <i class="icon-arrow-down"></i> <?= abs($change) ?>%
                                </span>
                            <?php endif ?>
                        <?php endif ?>

                        <a href="<?= URL::route('discover-insights', array($encoded_topic)) ?>" title="View Insights" class="pull-right">
                            <i class="icon-chart-2"></i>
                        </a>
                    </td>
                    <td>
                        <a href="<?= URL::route('discover-feed', array('trending', $encoded_topic)) ?>">
                            <i class="icon-fire"></i>
                            Discover Pins
                        </a>
                    </td>
                </tr>
                <?php $i++; ?>
            <?php endforeach ?>
            <?php if (count($top_topics) > 5): ?>
                <tr class="row-toggle">
                    <td colspan="6">
                        <a href="javascript:void(0)" id="topics-row-toggle" class="track-click" data-component="Top Pinned Topics" data-element="Show More Link">
                            Show More Top Pinned Topics
                        </a>
                    </td>
                </tr>
            <?php endif ?>
        </tbody>
    </table>
</div>

<script type="text/javascript">
$(function () {
    $('.label-recommendation').on('click', function () {
        element = $(this);

        // Set the selected state.
        element.removeClass('label-recommendation');
        element.addClass('label-info');

        data = {
            'event_data': {
                'view': appView,
                'component': 'Recommended Topics'
            }
        };

        addTopic(element.data('topic'), data, function (response) {
            if (!response.success) {
                // Revert the selected state.
                element.addClass('label-recommendation');
                element.removeClass('label-info');
            }
        });
    });

    $('#topics-row-toggle').on('click', function () {
        $('.module-top-topics .row-togglable').show();
        $(this).parents('tr').hide();
    });
});
</script>