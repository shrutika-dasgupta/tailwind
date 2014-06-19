<?php if ($type == 'snapshot'): ?>
    <div class="accordion-heading">
        <ul class="nav nav-pills">
            <li class="date-label">
                <?php if ($is_hashtag): ?>
                    <span class="date-label">Pins with <?= $hashtag ?> (<?= date('F jS, Y', $last_date) . " - " . date('F jS, Y', $current_date) ?>)</span>
                <?php else: ?>
                    <span class="date-label">Most Repinned Pins from <?= date('F jS, Y', $date) ?></span>
                <?php endif ?>
            </li>
            <li class="pull-right">
                <a href="<?= strpos(URL::previous(), 'domain') !== false ? URL::previous() : URL::route('domain-insights', array($query_string)) ?>">
                    <i class="icon-arrow-left"></i> Return to Insights
                </a>
            </li>
        </ul>
    </div>
    <?php return ?>
<?php endif ?>