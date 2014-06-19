<?php if ($type == 'snapshot'): ?>
    <div class="accordion-heading">
        <ul class="nav nav-pills">
            <li class="date-label">
                <span class="date-label">Most Repinned Pins from <?= date('F jS, Y', $date) ?></span>
            </li>
            <li class="pull-right">
                <a href="<?= strpos(URL::previous(), 'discover') !== false ? URL::previous() : URL::route('discover-insights', array($query_string)) ?>">
                    <i class="icon-arrow-left"></i> Return to Insights
                </a>
            </li>
        </ul>
    </div>
    <?php return ?>
<?php endif ?>