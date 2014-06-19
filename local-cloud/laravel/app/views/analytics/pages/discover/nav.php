<?= $topic_bar ?>

<?php $popular_feeds_enabled = $customer->hasFeature('listening_popularfeeds') ?>

<div class="navbar navbar-listening">
    <div class="navbar-inner">
        <ul class="nav">
            <li>
                <a href="<?= URL::route('discover') ?>">
                    <strong>Pulse</strong>
                </a>
            </li>
            <li<?= ($type == 'insights') ? ' class="active"' : '' ?>>
                <a href="<?= URL::route('discover-insights', array($query_string)) ?>">
                    <strong>Insights</strong>
                </a>
            </li>
            <li<?= ($type == 'trending') ? ' class="active"' : '' ?>>
                <a href="<?= URL::route('discover-feed', array('trending', $query_string)) ?>">
                    <strong>Trending Feed</strong>
                </a>
            </li>

            <?php $popular = in_array($type, array('most-repinned', 'most-liked', 'most-commented')) ? 'active ' : '' ?>
            <li class="<?= $popular ?>dropdown">
                <a href="javascript:void(0)" data-toggle="dropdown" class="dropdown-toggle track-click" data-component="Top Nav" data-element="Popular Feed Toggle">
                    <strong>Popular Feed</strong> <b class="caret"></b>
                </a>

                <ul class="dropdown-menu">
                    <?php if ($popular_feeds_enabled): ?>
                        <li<?= ($type == 'most-repinned') ? ' class="active"' : '' ?>>
                            <a href="<?= URL::route('discover-feed', array('most-repinned', $query_string)) ?>">Most Repinned</a>
                        </li>
                        <li<?= ($type == 'most-liked') ? ' class="active"' : '' ?>>
                            <a href="<?= URL::route('discover-feed', array('most-liked', $query_string)) ?>">Most Liked</a>
                        </li>
                        <li<?= ($type == 'most-commented') ? ' class="active"' : '' ?>>
                            <a href="<?= URL::route('discover-feed', array('most-commented', $query_string)) ?>">Most Commented</a>
                        </li>
                    <?php else: ?>
                        <li class="disabled"><a href="javascript:void(0)">Most Repinned</a></li>
                        <li class="disabled"><a href="javascript:void(0)">Most Liked</a></li>
                        <li class="disabled"><a href="javascript:void(0)">Most Commented</a></li>
                    <?php endif ?>
                </ul>
            </li>
        </ul>

        <?php if ($type != 'trending'): ?>
            <ul class="nav pull-right">
                <li class="dropdown dropdown-date">
                    <?php
                        $date  = Input::get('date', 'week');
                        $dates = array(
                            'week'    => 'Last 7 Days',
                            '2weeks'  => 'Last 14 Days',
                            'month'   => 'Last 30 Days',
                            'alltime' => 'All-Time',
                        );
                    ?>
                    <a href="#" data-toggle="dropdown" class="dropdown-toggle track-click" data-component="Top Nav" data-element="Date Picker">
                        <strong><?= array_get($dates, $date) ?></strong> <b class="caret"></b>
                    </a>
                    <ul class="dropdown-menu">
                        <?php foreach ($dates as $key => $timeframe): ?>
                            <?php if (($plan->plan_id == 1 && !in_array($key, array('week')))
                                   || ($plan->plan_id == 2 && !in_array($key, array('week', '2weeks')))
                                   || ($plan->plan_id == 3 && !in_array($key, array('week', '2weeks', 'month')))
                            ): ?>
                                <li class="disabled">
                                    <a href="javascript:void(0)"><?= $timeframe ?></a>
                                </li>
                            <?php else: ?>
                                <li<?php if ($date == $key) echo ' class="active"' ?>>
                                    <a href="<?= URL::route('discover-feed', array($type, $query_string, "date=$key")) ?>">
                                        <?= $timeframe ?>
                                    </a>
                                </li>
                            <?php endif ?>
                        <?php endforeach ?>
                    </ul>
                </li>
            </ul>
        <?php endif ?>
    </div>
</div>