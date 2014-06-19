<?php
$facebook_percentage   = number_format($entry->facebook_score / $entry->social_score * 100, 2);
$googleplus_percentage = number_format($entry->googleplus_score / $entry->social_score * 100, 2);
$twitter_percentage    = number_format($entry->twitter_score / $entry->social_score * 100, 2);
$pinterest_percentage  = number_format($entry->pinterest_score / $entry->social_score * 100, 2);
?>

<div class="hidden" id="entry-score-popover-<?= $entry->id ?>">
    <div class="progress">
        <div class="bar bar-info" style="width: <?= $facebook_percentage ?>%;"></div>
        <div class="bar bar-success" style="width: <?= $googleplus_percentage ?>%;"></div>
        <div class="bar bar-warning" style="width: <?= $twitter_percentage ?>%;"></div>
        <div class="bar bar-danger" style="width: <?= $pinterest_percentage ?>%;"></div>
    </div>

    <ul>
        <li class="facebook">
            <span><strong>Facebook:</strong> <?= number_format($entry->facebook_score) ?></span>
        </li>
        <li class="googleplus">
            <span><strong>Google+:</strong> <?= number_format($entry->googleplus_score) ?></span>
        </li>
        <li class="twitter">
            <span><strong>Twitter:</strong> <?= number_format($entry->twitter_score) ?></span>
        </li>
        <li class="pinterest">
            <span><strong>Pinterest:</strong> <?= number_format($entry->pinterest_score) ?></span>
        </li>
    </ul>
</div>